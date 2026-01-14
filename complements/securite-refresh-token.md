---
title: API PLatform - Sécurisation et extension du token de rafraîchissement
subtitle:
layout: tutorial
lang: fr
---

Dans le [TD4]({{site.baseurl}}/tutorials/tutorial4), nous avons utilisé le bundle [gesdinet/jwt-refresh-token-bundle](https://github.com/markitosgv/JWTRefreshTokenBundle) afin de gérer le **token de rafraîchissement** permettant de rafraîchir notre JWT d'authentification périodiquement.

Cependant, bien que populaire, ce bundle possède (actuellement) un défaut majeur : les tokens sont **stockés en base** (nécessaire pour pouvoir les invalider au besoin) mais ne sont pas **hachés** (chiffrés). Cela veut donc dire que si la base fuite et est récupérée par un utilisateur malicieux, il pourra se connecter et obtenir des JWTs et de nouveaux tokens de rafraichissement de manière illimitée pour n'importe quel utilisateur actuellement connecté ! Ce n'est pas aussi grave que de stocker en clair un mot de passe (car on peut facilement invalider tous les tokens de rafraichissement en les supprimant) mais cela reste problématique.

Il est donc fortement recommandé de :
* Ne pas stocker le token de rafraîchissement en clair dans la base de données, mais plutôt un **hash poivré** de ce token (chiffré avec l'algorithme `SHA-256` et une **clé secrète** relative à l'application).
* Lors de l'authentification ou du rafraichissement, on donne toujours le token en clair à l'utilisateur.
* Lors du rafraichissement ou de l'invalidation du token, on récupère la valeur en clair donnée par l'utilisateur, on la hache et on la poivre avec la clé secrète et on peut ainsi récupérer les données du token en base.
* Ainsi, si un attaquant récupère la base, il ne pourra pas utiliser le token de rafraichissement (car chiffré).
* On conseille aussi de renouveler les tokens (lors d'un rafraîchissement) au lieu de rallonger leur durée de vie. Avec le bundle, cela peut être fait via le paramètre `single_use`.
* Éventuellement, on peut stocker les tokens de rafraichissement dans une base de données à part.

Bref, avec le bundle que nous utilisons actuellement, il n'est pas (directement) possible de chiffrer nos tokens, et il n'y a pas vraiment de bundle alternatif que nous pourrions utiliser. Cependant, nous pouvons mettre en place diverses solutions :

* Ne pas utiliser le bundle et coder le mécanisme de rafraichissement soi-même (avec chiffrement). Cela demande un peu d'effort et quelques classes, mais n'est pas trop compliqué.
* Utiliser la puissance de symfony pour décorer et réécrire dynamiquement certaines parties du bundle pour mettre en place le chiffrement.
* Faire son propre fork du bundle et rajouter la fonctionnalité de chiffrement.

Dans cette note complémentaire, nous allons vous développer la première et la deuxième solution.

Nous allons aussi voir :
* Comment ajouter des informations au token stocké en base (facile pour la première solution, un peu plus compliqué pour la seconde)
* Comment faire en sorte que l'utilisateur puisse visualiser toutes ses connexions et supprimer certains tokens (fonctionnalité "se déconnecter d'un appareil").

## Première solution : coder soi-même le mécanisme de rafraîchissement

Cette solution n'est pas si compliquée, car nous allons adapter le code au besoin du projet. Cela va nous demander de mettre en place plusieurs **services**, mais qui seront assez légers. Aussi, nous allons continuer de mettre en application les bonnes pratiques de développement en définissant et en injectant des **interfaces** au lieu des services concrets, ainsi cette partie de l'application sera fortement modulable.

Cette solution vient donc **remplacer complètement le bundle gesdinet/jwt-refresh-token-bundle**.

Nous n'avons pas besoin de reprendre toutes les fonctionnalités proposées par le bundle `gesdinet/jwt-refresh-token-bundle`. Globalement, le code développé permettra de :

* Générer et sauvegarder (de manière **chiffrée**) un token de rafraichissement.
* Attacher le token en clair dans le corps de la réponse ou dans un cookie sécurisé (ou les deux).
* **Rafraichir le JWT** d'accès grâce au token de rafraîchissement (ce qui renouvellera le token de rafraîchissement par la même occasion, en supprimant l'ancien).
* **Invalider** le token.

Nous commencerons par définir une entité `RefreshToken`, son interface et son repository.

Ensuite, nous allons découper le travail entre plusieurs **services** :

* Un **hasher** qui se chargera de chiffrer les tokens de rafraîchissement (pour la sauvegarde en base de données).
* Un **manager** permettant de récupérer de créer et sauvegarder les tokens en base, les récupérer (à partir du token en clair), les supprimer.
* Un **générateur de tokens** qui permettra de générer un nouveau token (en clair) **unique** (pas déjà stocké dans la base).
* Un **extracteur de tokens** qui permettra de récupérer un token depuis les données d'une **requête HTTP** : soit dans les données de la requête (par exemple le query string), ou son corps (en JSON), ou bien depuis un cookie...
* Un gestionnaire d'écriture et de suppression des **cookies**.

Enfin, il ne restera plus qu'à gérer les différents **événements** :

* Renvoyer le token de rafraîchissement dans la réponse (dans le corps et/ou dans un cookie sécurisé) lors de l'événement d'authentification, en du JWT au client.
* Mettre en place le mécanisme de rafraîchissement du JWT, en l'associant à une route.
* Mettre en place le mécanisme d'invalidation du token de rafraîchissement, en l'associant à l'événement de déconnexion.

### Définir les routes

Tout d'abord, nous allons commencer par définir les deux routes (rafraichissement et invalidation) que nous utiliserons plus tard :

Nous pouvons faire cela directement dans `config/routes.yaml` :

```yaml
#config/routes.yaml
...
api_token_refresh:
    path: /api/token/refresh
    methods: ['POST']

api_token_invalidate:
    path: /api/token/invalidate
    methods: ['POST']
```

### Les paramètres

Maintenant, nous allons mettre en place un certain nombre de **paramètres** que nous injecterons dans nos services. Pour cela, il faut éditer le fichier `config/services.yaml` :

```yaml
#config/services.yaml
parameters:
    refresh_token.name: refresh_token
    refresh_token.refresh_path: api_token_refresh
    refresh_token.ttl: 2592000
    refresh_token.cookie.enabled: true
    refresh_token.cookie.remove_token_from_body: true
    refresh_token.cookie.path: /api/token

services:
    ...
```

Nous aurions pu à la place utiliser ce format (plus lisible) :

```yaml
parameters:
    refresh_token:
        name: refresh_token
        refresh_path: api_token_refresh
        ttl: 2592000
        cookie:
            enabled: true
            remove_token_from_body: true
            path: /api/token
```

Mais alors, nous ne pourrions qu'injecter un paramètre `refresh_token` sous la forme d'un tableau (contenant les sous-entrées), ce qui n'est pas forcément le plus pratique en l'état. Cela aurait néanmoins l'avantage de pouvoir faire en sorte que certains paramètres soient optionnels.

Nous allons donc nous en tenir à la première version.

Intéressons-nous donc à ces paramètres :

* `refresh_token.name` : Nom du token de rafraichissement (nom de l'entrée dans le corps de la réponse et/ou nom du cookie).
* `refresh_token.refresh_path` : Nom de la route utilisée pour le rafraîchissement.
* `refresh_token.ttl` : Durée de vie du token (en secondes).
* `refresh_token.cookie.enabled` : Active ou désactive l'écriture du token dans un cookie sécurisé.
* `refresh_token.cookie.remove_token_from_body` : Active ou désactive l'écriture du token dans le corps de la réponse si les cookies sont activés.
* `refresh_token.cookie.path` : Attribut `Path` du cookie contenant le token (pour ne l'envoyer que sur certaines routes).

Comme cité plus haut, le seul inconvénient de cette solution est que tous les paramètres sont obligatoires, même si on n'utilise pas les cookies, par exemple.

### L'entité RefreshToken et son repository

Afin de stocker le token dans la base de données, nous allons créer une entité `RefreshToken`. Mais tout d'abord, nous pouvons créer une interface générale qui définit le contrat d'un token de rafraichissement (dans `src/Entity`) :

```php
#src/Entity
<?php
namespace App\Entity;

use DateTime;

interface RefreshTokenInterface
{
    public function getRefreshToken(): ?string;

    public function setRefreshToken(string $refreshToken): static;

    public function getExpiresAt(): ?DateTime;

    public function setExpiresAt(DateTime $expiresAt): static;

    public function getUsername(): ?string;

    public function setUsername(string $username): static;

    public function hasExpired(): bool;
}
```

On définit ensuite l'entité qui implémente l'interface (et qui sera sauvegardée grâce à **Doctrine**) :

```php
#src/Entity
<?php
namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[UniqueEntity('refreshToken')] //Permet de vérifier que le token est unique (du côté application)
class RefreshToken implements RefreshTokenInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    //Le "unique: true" permet de rajouter une contrainte d'unicité sur cet attribut
    #[ApiProperty(readable: false)]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $refreshToken = null;

    #[ORM\Column]
    private ?DateTime $expiresAt = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /*
    - new DateTime() génère un objet correspondant à l'instant où ext ecécuté le code.
    - On peut comparer deux dates avec >, <, =, etc
    */
    public function hasExpired(): bool
    {
        return new DateTime() >= $this->expiresAt;
    }
}
```

Et enfin, ont créé le repository correspondant (dans `src/Repository`) :

```php
<?php
#src/Repository
namespace App\Repository;

use App\Entity\RefreshToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    //Méthode pour supprimer les tokens invalides
    public function removeExpiredTokens() : void {
        $queryBuilder = $this->createQueryBuilder('rt')
                        ->delete()
                        ->where("rt.expiresAt <= :date")
                        ->setParameter('date', new DateTime());
        $query = $queryBuilder->getQuery();
        $query->execute();
    }
}
```

### Le hasher

Le hasher est le service qui nous permettra de chiffrer le token (avec `SHA-256`, dans notre implémentation) avant de le sauvegarder dans la base de données.

Pour cela, nous allons utiliser la fonction [hash_hmac](https://www.php.net/manual/fr/function.hash-hmac.php) de PHP qui permet de chiffrer une chaîne de caractères et de lui ajouter un poivre. Dans notre cas, le poivre sera la variable d'environnement `APP_SECRET` définie lors de l'initialisation d'un projet Symfony (cela pourrait être autre chose).

Notons toutefois que dans notre cas, le poivre est assez optionnel : le token généré par l'application est une chaîne aléatoire, donc, son entropie et sa robustesse sont déjà assez élevées. Si l'on ne souhaite donc pas poivrer le token, on utilisera plutôt la fonction [hash](https://www.php.net/manual/fr/function.hash.php).

Par soucis de qualité et pour rendre notre solution modulable, nous allons aussi définir et utiliser ce service au travers d'une **interface** (pour ce service et les suivants).

```php
#src/Service/RefreshToken/Hasher
<?php
namespace App\Service\RefreshToken\Hasher;

interface RefreshTokenHasherInterface
{
    public function hashToken(string $plainTextToken) : string;
}
```

```php
#src/Service/RefreshToken/Hasher
<?php

namespace App\Service\RefreshToken\Hasher;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RefreshTokenHasher implements RefreshTokenHasherInterface
{

    public function __construct(#[Autowire(env: 'APP_SECRET')] private string $secret)
    {}

    public function hashToken(string $plainTextToken): string
    {
        return hash_hmac('sha256', $plainTextToken, $this->secret);
    }
}
```

### Le manager

Le service suivant va nous permettre de gérer les différentes opérations de création, lecture et écriture du token.

Ses fonctionnalités sont les suivantes :

* Créer (et sauvegarder) un token de rafraîchissement (l'entité) à partir d'une chaîne en clair (le token), un utilisateur et une durée de vie.
* Récupérer un token de rafraîchissement dans la base de données à partir du token en clair (transmis par l'utilisateur).
* Supprimer un token de rafraîchissement (en cas d'invalidation).

Ce service va donc utiliser divers services : notre **hasher**, le repository et l'entity manager de Symfony.

```php
#src/Service/RefreshToken/Manager
<?php

namespace App\Service\RefreshToken\Manager;

use App\Entity\RefreshTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface RefreshTokenManagerInterface
{
    public function createRefreshToken(string $plainTextToken, UserInterface $user, int $ttl) : RefreshTokenInterface;

    public function getRefreshToken(string $plainTextToken) : ?RefreshTokenInterface;

    public function removeRefreshToken(RefreshTokenInterface $refreshToken);

    public function removeExpiredRefreshTokens();
}
```

```php
#src/Service/RefreshToken/Manager
<?php

namespace App\Service\RefreshToken\Manager;

use App\Entity\RefreshToken;
use App\Entity\RefreshTokenInterface;
use App\Repository\RefreshTokenRepository;
use App\Service\RefreshToken\Hasher\RefreshTokenHasherInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class RefreshTokenManager implements RefreshTokenManagerInterface
{

    public function __construct(
        private RefreshTokenHasherInterface $hasher,
        private RefreshTokenRepository $refreshTokenRepository,
        private EntityManagerInterface $entityManager
    )
    {}

    public function createRefreshToken(string $plainTextToken, UserInterface $user, int $ttl): RefreshTokenInterface
    {
        $token = new RefreshToken();
        $token->setRefreshToken($this->hasher->hashToken($plainTextToken));
        $token->setExpiresAt(new DateTime(date('Y-m-d H:i:s', time() + $ttl)));
        $token->setUsername($user->getUserIdentifier());
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        return $token;
    }

    public function getRefreshToken(string $plainTextToken): ?RefreshTokenInterface
    {
        /**
         * @var RefreshToken|null $token
         */
        $token =  $this->refreshTokenRepository->findOneBy(["refreshToken" => $this->hasher->hashToken($plainTextToken)]);
        return $token;
    }

    public function removeRefreshToken(RefreshTokenInterface $refreshToken)
    {
        $this->entityManager->remove($refreshToken);
        $this->entityManager->flush();
    }

    public function removeExpiredRefreshTokens()
    {
        $this->refreshTokenRepository->removeExpiredTokens();
    }
}
```

### Le générateur de tokens

Le générateur va simplement nous permettre de générer une chaîne aléatoire qui constituera notre token de rafraîchissement (en clair).
Par la suite, ce token sera transmis à l'utilisateur puis utilisé pour créer (et chiffrer) le token en base de données.

Ce service utilisera notre **manager** afin de vérifier si le token généré n'existe pas déjà (et en recréer un si nécessaire).

```php
#src/Service/RefreshToken/Generator
<?php

namespace App\Service\RefreshToken\Generator;

interface RefreshTokenGeneratorInterface
{
    public function generateUniqueToken(): string;
}
```

```php
#src/Service/RefreshToken/Generator
<?php
namespace App\Service\RefreshToken\Generator;

use App\Service\RefreshToken\Manager\RefreshTokenManagerInterface;

class RefreshTokenGenerator implements RefreshTokenGeneratorInterface
{

    public function __construct(private RefreshTokenManagerInterface $refreshTokenManager)
    {}

    public function generateUniqueToken(): string
    {
        $plainTextToken = null;
        $exists = true;
        while ($exists) {
            $plainTextToken = bin2hex(random_bytes(64));
            $exists = null !== $this->refreshTokenManager->getRefreshToken($plainTextToken);
        }
        return $plainTextToken;
    }
}
```

### L'extracteur de token (depuis une requête)

L'extracteur de token est un service qui va nous permettre de récupérer le token de rafraichissement (en clair) transmis par l'utilisateur lors d'une requête.

Dans notre implémentation, nous allons chercher ce token à divers endroits : dans les données de la requête HTTP (query string ou corps), dans le **payload** JSON ou bien dans un **cookie**. Nous allons injecter le **nom du token de rafraichissement** (définit dans les paramètres de l'application) et rechercher le token dans la requête en utilisant ce nom.

```php
#src/Service/RefreshToken/Extractor
<?php

namespace App\Service\RefreshToken\Extractor;

use Symfony\Component\HttpFoundation\Request;

interface RefreshTokenExtractorInterface
{
    public function extractRefreshTokenFromRequest(Request $request) : ?string;
}
```

```php
#src/Service/RefreshToken/Extractor
<?php

namespace App\Service\RefreshToken\Extractor;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

class RefreshTokenExtractor implements RefreshTokenExtractorInterface
{

    public function __construct(
        #[Autowire(param: 'refresh_token.name')] private string $refreshTokenName,
    )
    {
    }

    public function extractRefreshTokenFromRequest(Request $request): ?string
    {
        if($request->request->has($this->refreshTokenName)) {
            return $request->request->get($this->refreshTokenName);
        }
        $data = json_decode($request->getContent(), true);
        if(isset($data[$this->refreshTokenName])) {
            return $data[$this->refreshTokenName];
        }
        if($request->cookies->has($this->refreshTokenName)) {
            return $request->cookies->get($this->refreshTokenName);
        }
        return null;
    }
}
```

### Gestion des cookies

La prochaine étape est de pouvoir créer et demander la suppression d'un cookie contenant le token de rafraîchissement (si l'option de stocker le token dans un cookie est activée) dans la réponse transmisse au client (donc, lors de l'authentification ou bien lors du rafraîchissement du token où un nouveau token de rafraîchissement est généré puis transmis). Ce cookie sera généré avec l'option `secure`, `httpOnly` et la politique **sameSite** définie sur `lax`.

Notre implémentation utilisera deux paramètres définis dans notre configuration : le nom du token de rafraîchissement et aussi le `path` configuré pour le cookie.

```php
#src/Service/RefreshToken/Cookie
<?php

namespace App\Service\RefreshToken\Cookie;

use DateTime;
use Symfony\Component\HttpFoundation\Response;

interface RefreshTokenCookieServiceInterface
{
    public function writeRefreshTokenCookieInResponse(string $plainTextToken, DateTime $expiresAt, Response $response) : void;

    public function clearRefreshTokenCookieInResponse(Response $response) : void;

}
```

```php
#src/Service/RefreshToken/Cookie
<?php

namespace App\Service\RefreshToken\Cookie;

use DateTime;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenCookieService implements RefreshTokenCookieServiceInterface
{
    public function __construct(
        #[Autowire(param: 'refresh_token.name')] private string $refreshTokenName,
        #[Autowire(param: 'refresh_token.cookie.path')] private string $path
    )
    {}

    public function writeRefreshTokenCookieInResponse(string $plainTextToken, DateTime $expiresAt, Response $response): void
    {
        $cookie = new Cookie(
            name: $this->refreshTokenName,
            value: $plainTextToken,
            expire: $expiresAt,
            path: $this->path,
            secure: true,
            httpOnly: true,
            sameSite: 'lax'
        );
        $response->headers->setCookie($cookie);
    }

    public function clearRefreshTokenCookieInResponse(Response $response): void
    {
        $response->headers->clearCookie(
            name: $this->refreshTokenName,
            path: $this->path,
            secure: true,
            httpOnly: true,
            sameSite: 'lax'
        );
    }
}
```

### Délivrer le token lors de l'authentification

Maintenant que tous nos services utilitaires sont en place, nous allons pouvoir faire en sorte de générer et transmettre le token de rafraîchissement lors de l'authentification de l'utilisateur.

Pour cela, nous allons créer un **event listener** qui écoutera l'événement `AuthenticationSuccessEvent` (émis par le bundle `LexikJWTAuthenticationBundle`) et ajouter notre logique :
* Générer un token unique (en clair)
* Créer une nouvelle instance de `RefreshTokenInterface` afin de chiffrer et sauvegarder le token en base.
* Si les cookies ne sont pas activés (ou bien s'ils le sont mais que l'on souhaite conserver le token dans le corps de la réponse) ont écrit le token (en clair) dans le corps `JSON` de la réponse.
* Si les cookies sont activés, demander l'écriture d'un ocokie contenant le token dans la réponse.

Cet **event listener** va donc utiliser les divers services et paramètres définis dans notre solution.

```php
<?php
#src/EventListener
namespace App\EventListener;

use App\Service\RefreshToken\Cookie\RefreshTokenCookieServiceInterface;
use App\Service\RefreshToken\Generator\RefreshTokenGeneratorInterface;
use App\Service\RefreshToken\Manager\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class AuthenticationSuccessEventListener
{
    public function __construct(
        private RefreshTokenGeneratorInterface                                  $refreshTokenGenerator,
        private RefreshTokenManagerInterface                                    $refreshTokenManager,
        private RefreshTokenCookieServiceInterface                              $refreshTokenCookieService,
        #[Autowire(param: 'refresh_token.name')] private string                 $refreshTokenName,
        #[Autowire(param: 'refresh_token.ttl')] private int                     $ttl,
        #[Autowire(param: 'refresh_token.cookie.enabled')] private bool         $cookieEnabled,
        #[Autowire(param: 'refresh_token.cookie.remove_token_from_body')] private bool $removeTokenFromBody,
    )
    {
    }

    #[AsEventListener('lexik_jwt_authentication.on_authentication_success')]
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event) : void
    {
        $uniqueToken = $this->refreshTokenGenerator->generateUniqueToken();
        $refreshToken = $this->refreshTokenManager->createRefreshToken($uniqueToken, $event->getUser(), $this->ttl);
        if(!($this->cookieEnabled && $this->removeTokenFromBody)) {
            $data = $event->getData();
            $data[$this->refreshTokenName] = $uniqueToken;
            $event->setData($data);
        }
        if($this->cookieEnabled) {
            $response = $event->getResponse();
            $this->refreshTokenCookieService->writeRefreshTokenCookieInResponse($uniqueToken, $refreshToken->getExpiresAt(), $response);
        }
    }
}
``` 

### Rafraîchir le JWT grâce au token

Nous allons maintenant gérer la route `/api/token/refresh` permettant de rafraîchir le `JWT` d'authentification (et de générer un nouveau token de rafraichissement).

Pour cela, nous allons avoir besoin de créer un `Authenticator`. Ce type de service permet d'authentifier un utilisateur (à travers une ou plusieurs routes) et de charger ses données globalement (pour le temps de la requête) afin qu'elles soient accessibles dans les diverses actions.

L'action de rafraichissement est une action d'authentification où le token de rafraîchissement est utilisé pour authentifier l'utilisateur. En comparaison, le bundle `LexikJWTAuthenticationBundle` utilise son propre authenticator pour gérer l'authentification via le `JWT` lors de chaque requête sécurisée.

Ici, notre objectif est simple : si le token de rafraichissement donné par l'utilisateur est valide, on appellera la méthode `onAuthenticationSuccess` du gestionnaire d'authentification du bundle `LexikJWTAuthenticationBundle` qui déclenchera l'événement `AuthenticationSuccessEvent` permettant ainsi de générer à la fois un nouveau `JWT` d'authentification ainsi qu'un nouveau token de rafraichissement (grâce à ce que nous avons codé dans la section précédente).

On devra aussi gérer les divers cas d'erreurs à travers cette classe et renvoyer une réponse d'erreur (au format `JSON`) adéquate.

Ce service va utiliser divers autres services et paramètres :

* Le manager de tokens.
* L'extracteur de token.
* Le service `HttpUtils` qui nous permettra de vérifier que le chemin de la requête courante est celui que nous avons défini pour le rafraichissement (normalement, `/api/token/refresh`). On peut soit lui donner un chemin, soit un nom de route (à privilégier).
* Le nom de la route de rafraichissement (configurée dans `services.yaml`) qu'on utilisera avec le service `HttpUtils`.
* Le service `AuthenticationSuccessHandlerInterface` qui nous permettra de déclencher l'événement `AuthenticationSuccessEvent`.

```php
#src/Security/RefreshTokenAuthenticator
<?php
namespace App\Security;

use App\Service\RefreshToken\Extractor\RefreshTokenExtractorInterface;
use App\Service\RefreshToken\Manager\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

class RefreshTokenAuthenticator extends AbstractAuthenticator
{

    public function __construct(
        private RefreshTokenManagerInterface $manager,
        private RefreshTokenExtractorInterface $extractor,

        #[Autowire(service: 'lexik_jwt_authentication.handler.authentication_success')]
        private readonly AuthenticationSuccessHandlerInterface $lexikAuthenticationSuccessHandler,
        private readonly HttpUtils $httpUtils,

        #[Autowire(param: 'refresh_token.refresh_path')]
        private string $refreshPath
    )
    {
    }

    //Si la méthode renvoie false, l'authenticator ne sera pas utilisé pour traiter la route demandée par la requête courante.
    public function supports(Request $request): ?bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->refreshPath);
    }

    public function authenticate(Request $request): Passport
    {
        $plainTextToken = $this->extractor->extractRefreshTokenFromRequest($request);
        if(!$plainTextToken) {
            throw new AuthenticationException("Refresh token not found.");
        }
        $refreshToken = $this->manager->getRefreshToken($plainTextToken);
        if(!$refreshToken) {
            throw new AuthenticationException("Invalid refresh token.");
        }
        if($refreshToken->hasExpired()) {
            $this->manager->removeRefreshToken($refreshToken);
            throw new AuthenticationException("The refresh token has expired.");
        }
        $identifier = $refreshToken->getUsername();
        $this->manager->removeRefreshToken($refreshToken);

        //On délivre un "passeport" authentifiant l'utilisateur pour la requête en cours.
        return new SelfValidatingPassport(new UserBadge($identifier));
    }

    //Méthode automatiquement déclenchée si authenticate termine et délivre le passeport.
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        //On délenche appelle la méthode onAuthenticationSuccess du bundle Lexik, permettant de gérer les JWT qui permettra de déclencher l'événement AuthenticationSuccessEvent permettant ainsi de générer un nouveau JWT et un nouveau token de rafraîchissement.
        return $this->lexikAuthenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    //Méthode automatiquement déclenchée si authenticate échoue (si elle lève une AuthenticationException).
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            "error" => true,
            'message' => $exception->getMessage()
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
```

Ensuite, il faut déclarer qu'on souhaite utiliser cet `Authenticator` au niveau du fichier de configuration `security.yaml` :

```yaml
#config/packages/security.yaml
security:
    firewalls:
        ...
        main:
            ...
            custom_authenticators:
                - App\Security\RefreshTokenAuthenticator
```

### Invalider le token

Enfin, il ne reste plus qu'à gérer l'invalidation du token. Pour cela, nous allons :

* Associer la route d'invalidation à l'événement de déconnexion de Symfony.
* Créer un **event listener** qui invalidera le token (en base) et demandera la suppression du cookie (si nécessaire).

Notre implémentation utilisera donc :

* Notre service d'extraction de token (pour récupérer le token de rafraîchissement envoyé dans la requête).
* Notre manager (pour demander la suppression du token en base).
* Notre service de gestion des cookies (pour demander la suppression du cookie contenant le token, si nécessaire).

On commence donc par éditer encore une fois `security.yaml` :

```yaml
#config/packages/security.yaml
security:
    firewalls:
        ...
        main:
            ...
            logout:
                path: api_token_invalidate
                delete_cookies: ['BEARER'] #Afin de supprimer le cookie contenant le JWT d'uathentification
```

**Note importante** : on ne peut pas juste préciser le nom du cookie contenant le token de rafraichissement car il est définit avec un **path**. Il faut indiquer le **path** en question lors de la suppression (ce que fait notre service de gestion des cookies).

On code ensuite l'event listener captant et traitant l'événement de déconnexion :

```php
#src/EventListener
<?php

namespace App\EventListener;

use App\Service\RefreshToken\Cookie\RefreshTokenCookieServiceInterface;
use App\Service\RefreshToken\Extractor\RefreshTokenExtractorInterface;
use App\Service\RefreshToken\Manager\RefreshTokenManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutEventListener
{

    public function __construct(
        private RefreshTokenExtractorInterface $refreshTokenExtractor,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private RefreshTokenCookieServiceInterface $refreshTokenCookieService,
    )
    {
    }

    #[AsEventListener]
    public function onLogout(LogoutEvent $event) : void {
        $response = new JsonResponse();
        $this->refreshTokenCookieService->clearRefreshTokenCookieInResponse($response);
        $plainTextToken = $this->refreshTokenExtractor->extractRefreshTokenFromRequest($event->getRequest());
        if(!$plainTextToken) {
            $response->setData(["message" => "Refresh token not found"]);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $event->setResponse($response);
            return;
        }

        $refreshToken = $this->refreshTokenManager->getRefreshToken($plainTextToken);
        if(!$refreshToken) {
            $response->setData(["message" => "Refresh token not found"]);
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $event->setResponse($response);
            return;
        }
        $this->refreshTokenManager->removeRefreshToken($refreshToken);
        if($refreshToken->hasExpired()) {
            $response->setData(["message" => "The refresh token has already expired"]);
            $response->setStatusCode(Response::HTTP_OK);
            $event->setResponse($response);
            return;
        }
        $response->setData(["message" => "Tokens have been invalidated"]);
        $response->setStatusCode(Response::HTTP_OK);
        $event->setResponse($response);
    }
}
```

À ce stade, notre système est complètement prêt et fonctionnel ! Il n'y a plus qu'à mettre à jour la base de données. Le token stocké en base est maintenant chiffré et donc, un attaquant ne pourra pas "hacker" les clients connectés en cas de fuite de la base de données.

### Ajouter des informations au token

Actuellement, notre token (stocké en base) contient des informations limitées. Dans certains cas, il peut être judicieux d'ajouter des informations complémentaires :

* Date de création.
* Adresse IP du client.
* Type de client (navigateur, mobile, etc).

Notre implémentation nous permet facilement d'ajouter ces informations :
* En ajoutant de nouveaux champs dans la classe `RefreshToken` (sans nécessairement les ajouter dans l'interface, si on ne les utilise pas dans la logique métier).
* En récupérant les données à travers la requête en cours (soit en modifiant la signature de `createRefreshToken` dans `RefreshTokenManagerInterface` et `RefreshTokenManager` afin d'ajouter un paramètre de type `Request`, soit en injectant le service `RequestStack` dans `RefreshTokenManager`).

Par exemple :

```php
#src/Entity
<?php
namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[UniqueEntity('refreshToken')]
class RefreshToken implements RefreshTokenInterface
{
    ...

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?DateTime $createdAt = null;

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
```

```php
<?php

namespace App\Service\RefreshToken\Manager;

use App\Entity\RefreshToken;
use App\Entity\RefreshTokenInterface;
use App\Repository\RefreshTokenRepository;
use App\Service\RefreshToken\Hasher\RefreshTokenHasherInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class RefreshTokenManager implements RefreshTokenManagerInterface
{

    public function __construct(
        private RefreshTokenHasherInterface $hasher,
        private RefreshTokenRepository $refreshTokenRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    )
    {}

    public function createRefreshToken(string $plainTextToken, UserInterface $user, int $ttl): RefreshTokenInterface
    {
        $token = new RefreshToken();
        $token->setRefreshToken($this->hasher->hashToken($plainTextToken));
        $token->setExpiresAt(new DateTime(date('Y-m-d H:i:s', time() + $ttl)));
        $token->setUsername($user->getUserIdentifier());

        //Écriture des nouvelles propriétés
        $request = $this->requestStack->getCurrentRequest();
        $token->setIp($request->getClientIp());
        $token->setUserAgent($request->headers->get('User-Agent'));
        $token->setCreatedAt(new DateTime());

        $this->entityManager->persist($token);
        $this->entityManager->flush();
        return $token;
    }
}
```

Il faudra penser à bien mettre à jour la base de données en conséquence.

### Ajouter des commandes pour supprimer les tokens invalides et révoquer des tokens

Comme dans le bundle original, on peut ajouter des **commandes** afin de supprimer les tokens invalides ou bien pour révoquer certains tokens. Cette implémentation est encore une fois très largement inspirée de celle du bundle [gesdinet/jwt-refresh-token-bundle](https://github.com/markitosgv/JWTRefreshTokenBundle).

```php
#src/Command
<?php
namespace App\Command;

use App\Service\RefreshToken\Manager\RefreshTokenManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'refresh-token:revoke',
    description: 'Révoque un token de rafraîchissement',
)]
class RefreshTokenRevoke extends Command
{
    public function __construct(private RefreshTokenManagerInterface $refreshTokenManager) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('token', InputArgument::REQUIRED, 'Le token de rafraîchissement à révoquer');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $plainTextRefreshToken = $input->getArgument('token');
        $refreshToken = $this->refreshTokenManager->getRefreshToken();
        if($refreshToken === null) {
            $io->error("Ce token n'existe pas.");
            return Command::FAILURE;
        }
        $this->refreshTokenManager->removeRefreshToken($refreshToken);
        $io->success("Le token a bien été révoqué !");
        return Command::SUCCESS;
    }
}
```

```php
#src/Command
<?php
namespace App\Command;

use App\Service\RefreshToken\Manager\RefreshTokenManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'refresh-token:clear',
    description: 'Supprime les tokens de rafraîchissement expirés',
)]
class RefreshTokenClear extends Command
{
    public function __construct(private RefreshTokenManagerInterface $refreshTokenManager) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->refreshTokenManager->removeExpiredRefreshTokens();
        $io->success("Les tokens expirés ont bien été supprimés !");
        return Command::SUCCESS;
    }
}
```

## Deuxième solution : décorer jwt-refresh-token-bundle

Dans cette deuxième approche, nous utilisons toujours le bundle de base : [gesdinet/jwt-refresh-token-bundle](https://github.com/markitosgv/JWTRefreshTokenBundle). Cependant, nous allons étendre son comportement dynamiquement grâce au design pattern **décorateur** (dont la mise en place et l'utilisation est facilité par Symfony) afin de **chiffrer le token de rafraîchissement**.

Cette solution sera développée dans la vue d'un stockage des données via un `ORM` (doctrine) mais il serait facilement possible de l'adapter et la rendre plus générique afin qu'elle fonctionne également pour `MongoDB` (SGBD **NoSQL**) car le bundle que nous utilisons permet aussi de stocker le token sur cette base.

### Ajout d'un nouveau champ au token

Tout d'abord, nous allons ajouter une nouvelle propriété à la classe `RefreshToken` générée par le bundle :

```php
#src/Entity
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[UniqueEntity('hashedRefreshToken')] //Permet de vérifier que le token est unique (du côté application)
class RefreshToken extends BaseRefreshToken
{
    //Le "unique: true" permet de rajouter une contrainte d'unicité sur cet attribut
    #[ORM\Column(length: 255, unique: true)]
    #[ApiProperty(readable: false)]
    private ?string $hashedRefreshToken = null;

    public function getHashedRefreshToken(): ?string
    {
        return $this->hashedRefreshToken;
    }

    public function setHashedRefreshToken(string $hashedRefreshToken): static
    {
        $this->hashedRefreshToken = $hashedRefreshToken;

        return $this;
    }
}
```

Cette propriété représentera notre token chiffré dans la base.

### Réécriture des fichiers de configuration de doctrine

Nous allons maintenant proposer une version mise à jour du fichier `RefreshToken.orm.xml` qui permet de définir les propriétés de base du token (de la classe qu'étend notre entité `RefreshToken`). Notre objectif est de ne plus sauvegarder le token en clair (non haché) généré par le bundle. Attention, il sera toujours disponible dans l'entité, mais pas sauvegardé : nous nous en sevrions pour écrire dans le champ du token haché lors de la sauvegarde.

On commence par placer ce fichier dans un dossier `config/doctrine/jwt-refresh-token-bundle` :

Fichier `RefreshToken.orm.xml` :

```xml
<?xml version="1.0" encoding="UTF-8"?>

<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                    http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <mapped-superclass name="Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken" table="refresh_tokens" repository-class="Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository">
        <id name="id" type="integer">
            <generator/>
        </id>
        <field name="username" length="255" column="username"/>
        <field name="valid" type="datetime"/>
    </mapped-superclass>
</doctrine-mapping>
```

Ensuite, on indique à doctrine de charger ce fichier au lieu de celui de base définit par le bundle :

```yaml
doctrine:
    orm:
        ...
        mappings:
            ...
            GesdinetJWTRefreshTokenBundle:
                type: xml
                dir: '%kernel.project_dir%/config/doctrine/jwt-refresh-token-bundle'
                prefix: 'Gesdinet\JWTRefreshTokenBundle\Entity'
                alias: GesdinetJWTRefreshTokenBundle
```

### Ajout du service hasher

Comme dans la solution précédente, on ajoute un service hasher qui nous permettra de chfifrer le token (avec `SHA-256`, dans notre implémentation) avant de le sauvegarder dans la base de données.

```php
#src/Service/RefreshToken/Hasher
<?php
namespace App\Service\RefreshToken\Hasher;

interface RefreshTokenHasherInterface
{
    public function hashToken(string $plainTextToken) : string;
}
```

```php
#src/Service/RefreshToken/Hasher
<?php

namespace App\Service\RefreshToken\Hasher;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RefreshTokenHasher implements RefreshTokenHasherInterface
{

    public function __construct(#[Autowire(env: 'APP_SECRET')] private string $secret)
    {}

    public function hashToken(string $plainTextToken): string
    {
        return hash_hmac('sha256', $plainTextToken, $this->secret);
    }
}
```

### Décoration du manager

Le service `RefreshTokenManagerInterface` du bundle permet de réaliser les différentes opérations `CRUD` liées au token.

Comme nous avons supprimé le token non haché de l'entité `RefreshToken`, il faut changer le comportement de cette classe afin de hacher le token en clair avant d'effectuer la requête de lecture en base. Cependant, le reste des opérations peut rester tel quel. Cette situation est donc adaptée à la mise en place du pattern **décorateur** en étendant l'opération de lecture du token pour ajouter le système de chiffrement.

Symfony facilite la décoration de services grâce à l'attribut `%[AsDecorator('nom_service')]` posé sur la classe décoratrice. Il faut ensuite que cette classe implémente la même interface que le service qu'elle décore. Enfin, on récupère l'instance du service décorée via le constructeur grâce à l'attribut `#[AutowireDecorated]`, ce qui nous permettra de déléguer certaines opérations au service de base.

Nous allons donc créer un service `RefreshTokenManagerDecorator` décorant le service `gesdinet.jwtrefreshtoken.refresh_token_manager` du bundle. Il n'y a pas d'autre déclaration à faire en dehors de la classe : lorsqu'une classe aura besoin d'utiliser le service `gesdinet.jwtrefreshtoken.refresh_token_manager`, nore version sera injectée à la place.

```php
#src/Service/RefreshToken/Manager
<?php
namespace App\Service\RefreshToken\Manager;

use App\Repository\RefreshTokenRepository;
use App\Service\RefreshToken\Hasher\RefreshTokenHasherInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: 'gesdinet.jwtrefreshtoken.refresh_token_manager')]
class RefreshTokenManagerDecorator implements RefreshTokenManagerInterface
{
    public function __construct(
        //On obtient le service de base que l'on décore
        #[AutowireDecorated] private RefreshTokenManagerInterface $inner,
        private RefreshTokenRepository $refreshTokenRepository,
        private RefreshTokenHasherInterface $hasher,
    )
    {}

    //On modifie le comportement de "get"
    public function get($plainTextToken)
    {
        return $this->refreshTokenRepository->findOneBy(['hashedRefreshToken' => $this->hasher->hashToken($plainTextToken)]);
    }

    //Pour les autres opérations, on délègue simplement au service de base décoré
    public function save(RefreshTokenInterface $refreshToken, $andFlush = true) {$this->inner->save($refreshToken, $andFlush);}
    public function create(){return $this->inner->create();}
    public function getLastFromUsername($username) {return $this->inner->getLastFromUsername($username);}
    public function delete(RefreshTokenInterface $refreshToken) {$this->inner->delete($refreshToken);}
    public function revokeAllInvalid($datetime = null) {return $this->inner->revokeAllInvalid($datetime);}
    public function getClass() {return $this->inner->getClass();}
}
```

### Décoration du generator

Enfin, la dernière étape consiste à décorer le service `RefreshTokenGeneratorInterface` (identifié par `gesdinet.jwtrefreshtoken.refresh_token_generator`) qui instancie le token. Nous allons laisser le service de base instancier l'entité token, lire le token en clair et remplir la propriété `hashedRefreshToken` avec le token chiffré.

```php
#src/Service/RefreshToken/Generator
<?php

namespace App\Service\RefreshToken\Generator;

use App\Entity\RefreshToken;
use App\Service\RefreshToken\Hasher\RefreshTokenHasherInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsDecorator(decorates: 'gesdinet.jwtrefreshtoken.refresh_token_generator')]
class RefreshTokenGeneratorDecorator implements RefreshTokenGeneratorInterface
{
    public function __construct(
        #[AutowireDecorated] private RefreshTokenGeneratorInterface $inner,
        private RefreshTokenHasherInterface $hasher,
        //Notre service décoré précédemment sera injecté !
        private RefreshTokenManagerInterface $refreshTokenManager
    )
    {}
    public function createForUserWithTtl(UserInterface $user, int $ttl): RefreshTokenInterface
    {
        /**
         * @var RefreshToken $token
         */
        $token = $this->inner->createForUserWithTtl($user, $ttl);
        $token->setHashedRefreshToken($this->hasher->hashToken($token->getRefreshToken()));
        return $token;
    }
}
```

À ce stade, tout est fonctionnel ! Il ne reste plus qu'à mettre à jour la base de données, et tout devrait fonctionner.

### Ajouter d'autres informations au token

Comme pour la précédente solution, il est possible d'ajouter des informations au token en modifiant `RefreshToken` puis `RefreshTokenGeneratorDecorator`.

Par exemple :

```php
#src/Entity
<?php
namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[UniqueEntity('hashedRefreshToken')]
class RefreshToken extends BaseRefreshToken
{
    ...

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?DateTime $createdAt = null;

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
```

```php
<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Service\RefreshToken\Hasher\RefreshTokenHasherInterface;
use DateTime;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsDecorator(decorates: 'gesdinet.jwtrefreshtoken.refresh_token_generator')]
class RefreshTokenGeneratorDecorator implements RefreshTokenGeneratorInterface
{
    public function __construct(
        #[AutowireDecorated] private RefreshTokenGeneratorInterface $inner,
        private RefreshTokenHasherInterface $hasher,
        private RequestStack $requestStack,
        private RefreshTokenManagerInterface $customRefreshTokenManager
    )
    {}
    public function createForUserWithTtl(UserInterface $user, int $ttl): RefreshTokenInterface
    {
        /**
         * @var RefreshToken $token
         */
        $token = $this->inner->createForUserWithTtl($user, $ttl);
        $token->setHashedRefreshToken($this->hasher->hashToken($token->getRefreshToken()));
        $token->setCreatedAt(new DateTime());
        $request = $this->requestStack->getCurrentRequest();
        $token->setIp($request->getClientIp());
        $token->setUserAgent($request->headers->get('User-Agent'));
        return $token;
    }
}
```

Il faudra penser à bien mettre à jour la base de données en conséquence.

## Gestion des tokens par l'utilisateur

Quelle que soit la solution retenue, il est possible de permettre à un utilisateur :
* Consulter ses tokens de rafraîchissement actifs.
* Supprimer certains de ses tokens de rafraîchissement.

Cela permet notamment d'ajouter des fonctionnalités du type "se déconnecter de tel appareil". Il faudra impérativement sécuriser ces routes. On pourra utiliser un [state provider]({{site.baseurl}}/complements/state-provider) pour récupérer les tokens d'un utilisateur.

```php
#src/State
<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\RefreshTokenRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RefreshTokenUserProvider implements ProviderInterface
{

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private RefreshTokenRepository $refreshTokenRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $utilisateur = $this->utilisateurRepository->find($uriVariables['idUtilisateur']);
        return $this->refreshTokenRepository->findBy(['username' => $utilisateur->getUserIdentifier()]);
    }
}
```

Ensuite, on ajoute les routes sur l'entité `RefreshToken`.

Par exemple, avec la première solution :

```php
#src/Entity
namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\RefreshTokenRepository;
use App\State\RefreshTokenUserProvider;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[UniqueEntity('refreshToken')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/utilisateurs/{idUtilisateur}/refresh_tokens',
            uriVariables: [
                "idUtilisateur" => new Link(
                    fromProperty: 'id',
                    fromClass: Utilisateur::class,
                    //On vérifie que l'utilisateur dont on récupère els tokens est bien celui connecté
                    security: "is_granted('ROLE_USER') and utilisateur === user",
                    securityObjectName: 'utilisateur',
                )
            ],
            //On utilise un provider car le token ne contient pas de référence directe vers un objet utilisateur (on pourrait changer cela cependant!)
            provider: RefreshTokenUserProvider::class
        ),
        new Delete(
            uriTemplate: '/refresh_tokens/{id}',
            security: "is_granted('ROLE_USER') and object.getUsername() === user.getUserIdentifier()"
        )
    ]
)]
class RefreshToken implements RefreshTokenInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    //On ne doit pas sérialiser le refresh token lorsqu'il est renvoyé au client
    #[ApiProperty(readable: false)]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $refreshToken = null;

    #[ORM\Column]
    private ?DateTime $expiresAt = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?DateTime $createdAt = null;

    ...
}
```

Ou bien, pour la deuxième solution :

```php
#src/Entity
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\State\RefreshTokenUserProvider;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[UniqueEntity('hashedRefreshToken')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/utilisateurs/{idUtilisateur}/refresh_tokens',
            uriVariables: [
                "idUtilisateur" => new Link(
                    fromProperty: 'id',
                    fromClass: Utilisateur::class,
                    security: "is_granted('ROLE_USER') and utilisateur === user",
                    securityObjectName: 'utilisateur',
                )
            ],
            provider: RefreshTokenUserProvider::class
        ),
        new Delete(
            uriTemplate: '/refresh_tokens/{id}',
            security: "is_granted('ROLE_USER') and object.getUsername() === user.getUserIdentifier()"
        )
    ]
)]
class RefreshToken extends BaseRefreshToken
{
    #[ORM\Column(length: 128, unique: true)]
    #[ApiProperty(readable: false)]
    private ?string $hashedRefreshToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'date')]
    private ?DateTime $createdAt = null;

    ...

}
```

## Limiter le nombre de requêtes

Vous aurez peut-être remarqué une faille dans notre application : un utilisateur peut créer autant de tokens qu'il veut, de manière illimitée. Mais de manière plus générale, cela est aussi vrai pour d'autres opérations : l'utilisateur peut créer autant de comptes utilisateur qu'il veut et dans l'api de **The Feed**, créer autant de publications qu'il veut, etc.

Un utilisateur malveillant pourrait surcharger la base de données en un rien de temps !

Pour parer cela, il serait possible de faire des vérifications du nombre de ressources créées par utilisateur du côté de l'application, mais il est plutôt conseillé d'utiliser un **rate limiter**. Dans une application web, cet outil va permettre de limiter le nombre de fois où une opération spécifique est utilisée (par exemple, `POST` sur la route `/api/utilisateurs/auth`...). Il est possible d'utiliser un rate limiter lié au type d'application (par exemple, Symfony fournit un bundle pour cela). il est aussi conseillé de configurer et d'utiliser le rate limiter du serveur (apache, nginx, caddy, etc) pour parer les attaques DoS.

Bref, dans une application professionnelle, il est **très fortement recommandé** (pour ne pas dire **obligatoire**) d'installer et de configurer correctement ce genre d'outils.

Vous pouvez trouver plus d'explication sur le rate limiter de Symfony au nvieau de [cet article](https://symfony.com/doc/current/rate_limiter.html). Vous pouvez aussi jeter un œil sur la [documentation officielle du rate limiter d'apache](https://httpd.apache.org/docs/current/mod/mod_ratelimit.html) ainsi que le module [security](https://rdr-it.com/apache2-installer-le-modsecurity-waf/) qui est plus complet.