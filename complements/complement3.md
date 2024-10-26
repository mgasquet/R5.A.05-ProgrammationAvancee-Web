---
title: API PLatform - Gestion des relations entre les entités 2/2
subtitle: Relations plusieurs-plusieurs, n-aire, porteuses
layout: tutorial
lang: fr
---

## Introduction

Dans la [première partie de ce complément]({{site.baseurl}}/complements/complement2), vous avez appris à gérer les différentes
relations **un-plusieurs** et **un-un**.

Dans cette seconde partie, nous allons étudier la gestion des relations types **plusieurs-plusieurs** (**binaires**), ou qui impliquent plus de deux entités (**ternaires**, etc), incluant ou non des données (**association porteuse**).

Ce type de relation peut s'avérer compliquée de bien des manières !

**Attention**, avant de continuer, il est fortement recommandé d'avoir bien suivi la [première partie de ce complément]({{site.baseurl}}/complements/complement2). Nous allons reprendre (et étendre) l'application développée dans la première partie.

## Les relations plusieurs-plusieurs (ManyToMany), associations porteuses et n-aire

Nous allons faire évoluer notre modélisation en incluant des **clubs** auxquels peut s'inscrire un joueur.
Un joueur peut s'inscrire à différents **clubs** et un **club** peut posséder plusieurs **membres**.

On va modéliser cela avec une association binaire **plusieurs-plusieurs** (`ManyToMany`) simple :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement3/ModeleEA3.PNG)
</div>

Ce qui donnerait alors le schéma relationnel suivant :

* **Joueur**(<u>id</u>, nom, prenom, #idVille)
* **Club**(<u>id</u>, nom)
* **Inscription**(<u>#idJoueur</u>, <u>#idClub</u>)

En effet, lors d'association n-aire, on crée une table dont la clé primaire est composée de clés étrangères référençant 
les entités qui participent à l'association.

Au niveau de la **conception**, on peut choisir d'avoir des **collections** d'un seul côté 
(liste de **clubs** dans **Joueur** ou bien liste de **joueurs** dans **Club**) ou bien des deux côtés (bidirectionnelle).

Comme **Doctrine** gère correctement les propriétés **bidirectionnelles**, nous allons décider d'avoir à la fois avoir 
une collection listant les clubs d'un joueur (dans `Joueur`) et une autre collection listant les membres d'un club (dans `Club`).

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement3/DiagrammeClasses3.PNG)
</div>

On peut aussi créer des routes spéciales pour lister les clubs d'un joueur et inversement (`/joueurs/{id}/clubs` et/ou `/clubs/{id}/membres`).

Ce qui donnera, du côté de l'application :

```php
#[ORM\Entity(repositoryClass: ClubRepository::class)]
#[ApiResource]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/clubs',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'membres',
            fromClass: Joueur::class,
        )
    ],
    normalizationContext: ["groups" => ['club:read']],
    denormalizationContext: ["groups" => ['club:write']]
)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['club:read', 'club:write'])]
    private ?string $nom = null;

    /**
     * @var Collection<int, Joueur>
     */
    #[ORM\ManyToMany(targetEntity: Joueur::class, inversedBy: 'clubs')]
    #[Groups(['club:read', 'club:write'])]
    private Collection $membres;

    //Methodes...
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read', 'ville:read', 'resultat:read']],
    denormalizationContext: ["groups" => ['joueur:write', 'resultat:write']]
)]
#[ApiResource(
    uriTemplate: '/clubs/{idClub}/membres',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'idClub' => new Link(
            toProperty: 'clubs',
            fromClass: Club::class,
        )
    ],
    normalizationContext: ["groups" => ['joueur:read']],
)]
...
class Joueur
{
    ...

    /**
     * @var Collection<int, Club>
     */
    #[ORM\ManyToMany(targetEntity: Club::class, mappedBy: 'membres')]
    #[Groups(['joueur:read', 'joueur:write'])]
    private Collection $clubs;

    ...
}
```

Grâce à l'utilisation de `ManyToMany`, doctrine va automatiquement créer (lors de la migration) 
une table correspondant à notre table `Inscription` du schéma relationnel :

**Inscription**(<u>#idJoueur</u>, <u>#idClub</u>)

Mais quels sont ces fameux problèmes que nous allons rencontrer ?

En effet, actuellement, avec cette modélisation, il est tout à fait possible :

* De créer un joueur et de fournir (éventuellement) la liste des clubs auxquels il est inscrit :

    ```
    POST https://localhost/api/joueurs
    {
        "nom": "Tarembois",
        "prenom": "Guy",
        "ville": "/api/villes/1",
        "clubs" : [
            "/api/clubs/1",
            "/api/clubs/2"
        ]
    }
    ```

* De créer un club et de fournir (éventuellement) la liste des joueurs membres :

    ```
    POST https://localhost/api/clubs
    {
        "nom": "Club Sandwich",
        "membres" : [
            "/api/joueurs/2",
            "/api/joueurs/7",
            "/api/joueurs/13",
        ]
    }
    ```

* De modifier **complètement** la liste des clubs auxquels un joueur est inscrit :

    ```
    PATCH https://localhost/api/joueurs/17
    {
        "clubs" : [
            "/api/clubs/5",
            "/api/clubs/7",
            "/api/clubs/33",
        ]
    }
    ```

* De modifier **complètement** la liste des joueurs membres d'un club.

    ```
    PATCH https://localhost/api/clubs/35
    {
        "clubs" : [
            "/api/joueurs/9",
            "/api/joueurs/13",
        ]
    }
    ```

Mais comment faire pour associer (inscrire) simplement **un joueur** à **un club** ? Ou pour supprimer cette association (désinscription) ?

Comme montré dans l'exemple avec **PATCH**, cela est techniquement possible, mais il faut renvoyer à chaque fois **la liste complète** 
des clubs (ou des membres). Cela est très contraignant (et lourd).

De plus, ici, nous avons affaire à une association binaire simple.
Gérer ces associations dans des cas plus évolués semble complexe (n-aire avec n > 2 et/ou porteuse de données).

Dans l'idéal, il faudrait trouver un moyen simple de créer ou supprimer une **inscription** (d'un **joueur** à un **club**) sans avoir à 
effectuer un `PATCH` du joueur ou du club. 

C'est donc la problématique que nous allons essayer de résoudre par la suite avec différentes **solutions** que nous allons explorer :

* Utilisation d'une "ressource virtuelle" pour gérer les relations `ManyToMany` simples (non porteuses, et pas de relations ternaires ou plus).

* Création d'une entité **Inscription** dédiée afin de diviser la relation `ManyToMany` en relations `ManyToOne` (permet de gérer tous les cas de figure):

    * Avec une **entité coordinatrice** possédant son **propre identifiant** (solution à privilégier). 
    Cela induit à une adaptation du **modèle E/A** et du **schéma relationnel** et demande aussi de gérer certaines **contraintes** afin de conserver la cohérence des données.

    * Avec une entité possédant une **clé composite** (solution déconseillée).

### Utilisation d'une ressource virtuelle pour les relations Many-To-Many simples

La mise en place de cette solution permet d'introduire des routes pour **créer** et **supprimer** des associations entre un joueur et en club.

Notre **schéma** relationnel, les tables et les relations existantes ne seront pas affectés. 

Dans ce scénario, on conserve donc la relation `ManyToMany` entre `Joueur` et `Club`.

La solution consiste à créer une classe entité "virtuelle", c'est-à-dire qui ne sera pas stockée en base de données.
Cette entité servira alors à prendre en charge des **routes** customisées dont nous traiterons la logique avec des **StateProvider** et des **StateProcessor**.

La forme des **routes** qui permettront d'agir sur cette ressource virtuelle sera composée des identifiants des deux entités en relation.

Dans notre exemple :

* `PUT /joueurs/{idJoueur}/clubs/{idClub}` : pour ajouter un joueur à un club.
* `DELETE /joueurs/{idJoueur}/clubs/{idClub}` : pour retirer un joueur d'un club.
* `GET /joueurs/{idJoueur}/clubs/{idClub}` : pour vérifier si un joueur est inscrit à un certain club.

Attention, cette forme de route peut sembler ambiguë : on pourrait croire qu'on modifie (ou qu'on supprime) 
les données d'un club (d'un joueur) : ce n'est pas le cas, nous modifions ou nous supprimons seulement l'association entre ces deux entités.

On utilise ici le verbe `PUT` au lieu de `POST`, car on ne créé pas vraiment de ressource, on fait une mise à jour en liant deux entités déjà existantes.

Nous allons commencer par créer les bases de notre entité virtuelle. Elle sera nommée `Inscription` et sera composée d'un `Joueur` et d'un `Club`.

```php
namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;

#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/clubs/{idClub}',
    operations: [
        new Put(
            description: "Inscrit un joueur à un club",
            deserialize: false,
            allowCreate: true
        ),
        new Delete(
            description: "Retire un joueur d'un club"
        ),
        new Get(
            description: "Permet de vérifier si un joueur est inscrit à un club"
        ),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            fromClass: Joueur::class
        ),
        'idClub' => new Link(
            fromClass: Club::class
        ),
    ]
)]
class Inscription
{
    #[ApiProperty(writable: false)]
    private ?Joueur $joueur = null;

    #[ApiProperty(writable: false)]
    private ?Club $club = null;

    public function getJoueur(): ?Joueur
    {
        return $this->joueur;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setJoueur(?Joueur $joueur): self
    {
        $this->joueur = $joueur;
        return $this;
    }

    public function setClub(?Club $club): self
    {
        $this->club = $club;
        return $this;
    }
}
```

Quelques commentaires sur cette classe :

* Le paramètre `uriTemplate` nous permet de définir la forme de notre route.
* Le paramétrage dans `uriVariables` ne doit pas utiliser de paramètres `toProperty` et/ou `fromPorperty`. Par contre, il est important de préciser à quelles classes correspondent ces paramètres (pour la documentation de l'API)
* Le paramètre `description` dans chaque opération permet de documenter l'API.
* Le paramètre `deserialize : false` dans l'opération `PUT` permet d'autoriser l'envoi d'un **payload** vide.
* Le paramètre `allowCreate : true` dans l'opération `PUT` permet d'autoriser la création d'une ressource avec le verbe `PUT` si elle n'existe pas au préalable.
* Il n'y a pas de relations `ManyToOne` sur `$joueur` et `$club`, car l'entité n'est pas stockée en base de données.
* On interdit l'écriture (via le `payload`) de `$joueur` et `$club`: leurs identifiants seront fournis via l'`URI`.

Maintenant, il faut coder diverses classes pour gérer la logique de notre entité virtuelle :

* Un **StateProvider** : classe qui permet de récupérer les données de l'entité (dans la base de données), à 
partir des données de la route. Elle est utilisée dans les opérations `GET`, `PUT`, `PATCH` et `DELETE`. 
Par défaut, **API Platform** utilise un **provider** par défaut, mais s'il y a besoin d'effectuer un traitement 
particulier, on peut coder et fournir son propre **StateProvider**

* Un **StateProcessor** pour l'opération `PUT`. Pour rappel, un **StateProcessor** est une classe qui permet
de traiter et modifier un objet lors d'opérations qui vont changer son état. Elle est donc utilisée dans le cadre 
des opérations `POST`, `PUT` et `PATCH`. Dans le [TD4]({{site.baseurl}}/tutorials/tutorial4), nous en avions utilisé deux pour affecter automatiquement l'auteur d'un message, mais aussi pour hacher le mot de passe de l'utilisateur.

* Un **StateProcessor** pour l'opération `DELETE`.

En résumé, un `StateProvider` permet de récupérer et traiter l'objet avant de le renvoyer au client, et 
un `StateProcessor` de traiter l'objet après l'envoi des données par client et avant sa sauvegarde.

Commençons par coder notre `StateProvider`. Le traitement effectué par cette classe est le suivant : 

* Récupérer les identifiants `idJoueur` et `idClub` dans l'URI.

* Récupérer le joueur et le club concernés.

* Lever des exceptions `NotFoundHttpException` si le joueur ou le club n'existent pas.

* Retourner `null` si le joueur concerné n'est pas inscrit au club concerné.

* Sinon, construire un objet `Inscription` en affectant le `Joueur` et le `Club` récupérés puis le retourner.

**API Platform** fournit une commande pour créer la base d'un **StateProvider** :

```bash
php bin/console make:state-provider InscriptionProvider
```

Ce qui créé la classe `InscriptionProvider` dans le dossier `src/State`.

Il ne reste plus qu'à la compléter :

```php
namespace App\State;

class InscriptionProvider implements ProviderInterface
{
    //Injections des repositories
    public function __construct(private JoueurRepository $joueurRepository, private ClubRepository $clubRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //$uriVariables contient les valeurs des variables fournies au travers de l'URI de la route
        $idJoueur = $uriVariables["idJoueur"];
        $joueur = $this->joueurRepository->find($idJoueur);
        if(!$joueur) {
            throw new NotFoundHttpException("Joueur inexistant.");
        }
        $idClub = $uriVariables["idClub"];
        $club = $this->clubRepository->find($idClub);
        if(!$club) {
            throw new NotFoundHttpException("Club inexistant.");
        }

        //On regarde si le joueur n'est pas inscrit au club
        if(!$joueur->getClubs()->contains($club)) {
            return null;
        }

        //Si le joueur est bien inscrit au club, on créé l'objet inscription, on le configure puis on le retourne
        $inscription = new Inscription();
        $inscription->setJoueur($joueur);
        $inscription->setClub($club);
        return $inscription;
    }
}
```

Ensuite, on va créer le premier `StateProcessor` permettant de gérer l'opération `PUT`, avec le traitement suivant :

* Si l'inscription (entre le joueur et le club) existe déjà, on ne fait rien et on la retourne.

* Sinon :

    * On récupère le joueur et le club concernés.

    * On lève des exceptions `NotFoundHttpException` si le joueur ou le club n'existent pas.

    * On ajoute le club au joueur, avec la méthode `addClub` (automatiquement générée dans `Joueur` par Symfony).

    * On sauvegarde la mise à jour du joueur en utilisant la méthode `flush` du service `EntityManager`.

    * On construit un objet `Inscription` en affectant le `Joueur` et le `Club` récupérés et on le retourne.

Comme notre opération `PUT` utilise au préalable notre `StateProvider`, on peut savoir si l'inscription existe déjà ou non.
Un objet `$data` est fourni au `StateProcessor` contenant les données de la ressource ciblée dans l'URL (ou `null` si elle n'existe pas).

Pour rappel, **API Platform** fournit une commande pour créer la base d'un **StateProcessor** :

```bash
php bin/console make:state-processor InscriptionPutProcessor
```

Ce qui créé la classe `InscriptionPutProcessor` dans le dossier `src/State`.

Il ne reste plus qu'à la compléter :

```php
namespace App\State;

class InscriptionPutProcessor implements ProcessorInterface
{
    //Injection des repositories et de du service EntityManager
    public function __construct(
        private JoueurRepository $joueurRepository,
        private ClubRepository $clubRepository,
        private EntityManagerInterface $entityManager,
    )
    {}

    //$data est un objet Inscription fourni par le StateProvider.
    //Dans ce contexte (PUT avec allowCreate: true), il peut être null si le joueur n'était pas déjà inscrit au club en question.
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        //Si l'inscription n'existe pas déjà (null retourné par le StateProvider)
        if(!$data) {
            //$uriVariables contient les valeurs des variables fournies au travers de l'URI de la route
            $idJoueur = $uriVariables["idJoueur"];
            $joueur = $this->joueurRepository->find($idJoueur);
            if(!$joueur) {
                throw new NotFoundHttpException("Joueur inexistant.");
            }
            $idClub = $uriVariables["idClub"];
            $club = $this->clubRepository->find($idClub);
            if(!$club) {
                throw new NotFoundHttpException("Club inexistant.");
            }

            //On créé l'objet Inscription à retourner au client
            $data = new Inscription();
            $data->setJoueur($joueur);
            $data->setClub($club);

            //On ajoute le club à la collection de clubs du joueur
            $joueur->addClub($club);

            //On sauvegarde les changements
            $this->entityManager->flush();
        }
        return $data;
    }
}
```

Enfin, il ne reste plus qu'à créer le `StateProcessor` pour gérer l'opération `DELETE`.
Celui-ci va être rapide à coder : le `StateProvider` doit déjà nous fournit un objet valide. 
Nous n'avons donc pas besoin de vérifier l'existence de l'inscription, car une exception `NotFoundHttpException` 
aura été retournée avant d'arriver au traitement de notre `StateProcessor`.

Techniquement, cela aurait dû être le cas aussi pour `PUT`. Mais comme nous avons ajouté le paramètre `allowCreate: true`, 
nous avons autorisé d'exécuter le traitement même si la ressource ciblée n'existe pas.

Ici, le traitement va être simple : retirer le club de la collection de clubs du joueur puis sauvegarder les modifications :

```bash
php bin/console make:state-processor InscriptionDeleteProcessor
```

```php
namespace App\State;

class InscriptionDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {}

    //$data est un objet Inscription fourni par le StateProvider.
    //Dans ce contexte (DELETE), il ne peut pas être null, sinon une exception NotFoundHttpException aurait été levée avant d'arriver ici.
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $data->getJoueur()->removeClub($data->getClub());
        $this->entityManager->flush();
    }
}
```

Enfin, l'étape finale est de modifier notre entité/ressource virtuelle `Inscription` afin d'y attacher notre `StateProvider` et
nos deux `StateProcessor` au niveau des opérations concernées :

```php
namespace App\Entity;

#[ApiResource(
    ...
    operations: [
        new Put(
            description: "Inscrit un joueur à un club",
            deserialize: false,
            allowCreate: true,
            processor: InscriptionPostProcessor::class
        ),
        new Delete(
            description: "Retire un joueur d'un club",
            processor: InscriptiondeleteProcessor::class
        ),
        ...
    ],
    ...
    provider: InscriptionProvider::class,
)]
class Inscription
{
    ...
}
```

À noter qu'on pourrait aussi (en plus ou à la place) ajouter des routes dans l'autres 
sens (de club vers joueur) en utilisant le même **provider** et les mêmes **processors**.

Bref, tout est prêt ! Les exemples suivants fonctionnent :

Ajout du joueur 1 au club 2 :
```
PUT /api/joueurs/1/clubs/2
```

Vérifier si le joueur 1 est inscrit club 2 :
```
GET /api/joueurs/1/clubs/2
```

Retirer le joueur 1 du club 2 :
```
DELETE /api/joueurs/1/clubs/2
```

**Avantages de cette solution**

* Permet de conserver l'utilisation de relations `ManyToMany`.

* Pas de nouvelle entité stockée.

* Permet de respecter le modèle E/A et le schéma relationnel d'origine.

**Inconvénients**

* Pas mal de code à écrire (**providers**, **processors**).

* Route un peu ambiguë.

* Ne fonctionne pas pour les ternaires (et plus) et pour les associations porteuses de données. Dans ce cas, on doit abandonner l'usage des relations `ManyToMany`.

### Utilisation d'une entité coordinatrice dédiée

Cette seconde solution est plus généraliste, car elle s'adapte facilement à tous les cas de figures : associations 
binaires simples, ternaires (ou plus), associations porteuses de données...

L'idée est de remplacer les relations `ManyToMany` (plusieurs-plusieurs) par des relations `OneToMany` vers une entité 
coordinatrice **Inscription** avec un **identifiant** (clé) numérique **simple** et deux relations (`ManyToOne`) vers 
**joueur** et **club** (clés étrangères, mais ne font pas partie de la clé primaire).

En adaptant notre modèle E/A et notre schéma relationnel, cela donne alors la modélisation suivante :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement3/ModeleEA4.PNG)
</div>

Ce qui donnerait alors le schéma relationnel suivant :

* **Joueur**(<u>id</u>, nom, prenom, #idVille)
* **Club**(<u>id</u>, nom)
* **Inscription**(<u>id</u>, #idJoueur, #idClub).

En termes de **conception**, on adapte aussi le **diagramme de classes de conception** en conséquence :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement3/DiagrammeClasses4.PNG)
</div>

À noter que la bidirectionnelle est facultative. 
On pourrait éventuellement se passer des collections **inscriptions** d'un côté ou même des deux 
(on pourra toujours créer des routes dédiées pour trouver l'information).

Notre entité `Inscription` possédera donc deux relations `ManyToOne` : une vers le `Joueur` et l'autre vers `Club`.

Du côté de `Joueur` et/ou `Club`, on a alors des **collections** d'entités `Inscription` 
(on supprime les relations `ManyToOne` entre **Club** et **Joueur**).

Avec cette modélisation, on peut alors créer, supprimer ou mettre à jour des entités **Inscription** 
en utilisant leurs identifiants propres.

Cependant, cette méthode ne respecte pas vraiment la modélisation initiale du modèle E/A, 
car elle utilise une **entité coordinatrice** au lieu d'une **association binaire** qui possède un 
identifiant naturel (composé des deux clés étrangères).

Dans ce cas, pour conserver la **cohérence** des données, il faut **interdire** le fait d'avoir plusieurs 
fois le même couple de valeurs pour les **clés étrangères** (un joueur ne doit pas pouvoir être inscrit plusieurs fois au même club).

Il faut donc explicitement gérer la **contrainte d'unicité** (et **NOT NULL**) sur le couple **(#idJoueur, #idClub)**.
Il faudra aussi spécifier que `#idJoueur` et `#idClub` ne peuvent pas être **nuls**.
Cela est très facile avec **Symfony** et **Doctrine**.

Concrètement, la mise en place de cette solution se fait en plusieurs étapes :

1. On n'associe pas `Joueur` et `Club` avec une `ManyToMany`.

2. À la place, on crée une nouvelle entité `Inscription` composée :

    * D'un identifiant (généré automatiquement quand on crée l'entité avec la commande `make:entity`).

    * D'une relation `ManyToOne` avec `Joueur` qui **ne doit pas pouvoir être nulle** qui peut être éventuellement bidirectionnelle (collection d'inscriptions dans `Joueur`).

    * D'une relation `ManyToOne` avec `Club` qui **ne doit pas pouvoir être nulle** qui peut être éventuellement bidirectionnelle (collection d'inscriptions dans `Club`).

3. On ajoute une **contrainte d'unicité pour la base de données** sur le couple de clés étrangères référençant `Joueur` et `Club` grâce à l'attribut `#[ORM\UniqueConstraint]`.

4. On ajoute une **contrainte d'unicité pour l'application** sur le couple d'attributs `joueur` et `club` grâce à l'attribut `#[ORM\UniqueEntity]`.

5. On ajoute des **assertions** `NotBlank` et `NotNull` sur `joueur` et `club`.

6. On met à jour la structure de la base de données avec doctrine.

Si l'entité évolue (porteuse de données, ternaire, etc...) il suffira de rajouter de nouveaux attributs et/ou d'adapter les contraintes d'unicité.

Tout cela donnera, du côté de l'application :

```php
#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[UniqueEntity(fields: ['joueur', 'club'], message: "Un joueur ne peut pas être inscrit plus d'une fois au même club.")]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_JOUEUR_CLUB', fields: ['joueur', 'club'])]
#[ApiResource(
    operations: [
        new Post(),
        new Delete()
    ]
    normalizationContext: ["groups" => ['inscription:read']],
    denormalizationContext: ["groups" => ['inscription:write']],
    validationContext: ["groups" => ['Default', 'inscription:write']]
)]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['inscription:read', 'joueur:read', 'club:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:write'])]
    #[Assert\NotNull(groups: ['inscription:write'])]
    #[Groups(['inscription:read', 'inscription:write', 'club:read'])]
    private ?Joueur $joueur = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:write'])]
    #[Assert\NotNull(groups: ['inscription:write'])]
    #[Groups(['inscription:read', 'inscription:write', 'joueur:read'])]
    private ?Club $club = null;
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read', 'ville:read', 'resultat:read']],
    denormalizationContext: ["groups" => ['joueur:write', 'resultat:write']],
)]
class Joueur
{
#[ORM\Id]
    ...

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'joueur', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['joueur:read'])]
    private Collection $inscriptions;
}

#[ORM\Entity(repositoryClass: ClubRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['club:read']]
)]
class Club
{

    ...

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'club', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['club:read'])]
    private Collection $inscriptions;
}
```

Avec cette implémentation, il est possible de :

* Créer des inscriptions d'un joueur à un club.
* Désinscrire un joueur d'un club.
* Récupérer les inscriptions d'un joueur (en lisant les données du joueur) ou d'un club (en lisant les données du club).

```
POST /api/inscriptions
{
    "joueur": "/api/joueurs/2",
    "club": "/api/clubs/3"
}

DELETE /api/inscriptions/5
```

**Avantages de cette solution**

* De manière générale, l'utilisation de clés primaires "techniques" simples (et donc de classes coordinatrices où la contrainte d'unicité est explicitement gérée) 
à la place d'associations "plusieurs-plusieurs" est recommandée par une partie des développeurs (débat **surogate primary keys** vs **natural primary keys**).

* Cette solution est la plus flexible en pratique, et facilement prise en charge par **Api Platform**, sans causer de soucis de performances avec **Doctrine**.

* Elle s'adapte bien si la ressource devient plus complexe.

**Inconvénients**

* Ne respecte pas le modèle E/A et le schéma relationnel d'origine, car on transforme les associations en entités.

* Nécessite de gérer les contraintes d'unicité (et not null) explicitement.

* Complique légèrement les relations entre les entités, et nécessite l'utilisation d'une clé primaire "technique" (surrogate).

#### Sérialisation

Dans notre exemple, si on lit les données d'un joueur ou d'un club, seuls les **IRIs** des clubs auxquels est inscrit le joueur seront
affichées (et pareil pour les membres d'un club). Si on souhaite afficher le détail des clubs (ou des membres), il faut utiliser
les **groupes de sérialisation** adéquatement.

Il faut faire très attention de ne pas boucler (joueur affiche club qui affiche ses membres, qui affiche les clubs des 
membres, qui affichent leurs membres...). Pour cela, il faut éviter les **relations circulaires**.

Par exemple, remanions nos entités pour afficher les détails des clubs où est inscrit un joueur et inversement :

```php
...
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['inscription:read', 'inscription:joueur:read', 'inscription:club:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:write', 'joueur:write'])]
    #[Assert\NotNull(groups: ['inscription:write', 'joueur:write'])]
    #[Groups(['inscription:read', 'inscription:write', 'inscription:joueur:read'])]
    private ?Joueur $joueur = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:write'])]
    #[Assert\NotNull(groups: ['inscription:write'])]
    #[Groups(['inscription:read', 'inscription:write', 'inscription:club:read'])]
    private ?Club $club = null;
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read', 'ville:read', 'resultat:read', 'inscription:club:read']],
    ...
)]
class Joueur
{
#[ORM\Id]
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['joueur:read', 'inscription:joueur:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['joueur:read', 'joueur:write', 'inscription:joueur:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['joueur:read', 'joueur:write', 'inscription:joueur:read'])]
    private ?string $prenom = null;

    ...

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'joueur', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['joueur:read'])]
    private Collection $inscriptions;
}

#[ORM\Entity(repositoryClass: ClubRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['club:read', 'inscription:joueur:read']],
    denormalizationContext: ["groups" => ['club:write']]
    ...
)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['club:read', 'inscription:club:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['club:read', 'club:write', 'inscription:club:read'])]
    private ?string $nom = null;

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'club', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['club:read'])]
    private Collection $inscriptions;
}
```

Ici, afin d'éviter de boucler avec des relations circulaires, nous avons inclus de nouveaux groupes 
(`inscription:joueur:read` et `inscription:club:read`) qui excluent les collections d'inscriptions (de `Joueurs` et `Club`).

Ce qui donne, par exemple :

```
GET /api/joueurs/5
{
    "id" : 5,
    "nom": "Terrieur",
    "prenom": "Alain",
    "ville": {
        "nom" : "Toulouse",
        "codePostal": 31000
    },
    "inscriptions": [
        {
            "club" : {
                "id": 2,
                "nom" : "Hello Club"
            }
        },
        {
            "club" : {
                "id": 7,
                "nom" : "UML Club"
            }
        }
    ]
}
```

Comme vous pouvez le constater, quand il y a beaucoup de relations **bidirectionnelles**, le processus de sérialisation devient plus complexe.
Il est toujours possible de trouver une solution, mais il faudra bien réfléchir à l'affectation des groupes pour de pas avoir de comportement
inattendu.

#### Routes avec sous-ressources

Comme nous l'avons fait précédemment, il serait souhaitable d'avoir les routes suivantes :

* `/api/joueurs/{idJoueur}/inscriptions/` : pour récupérer les inscriptions d'un joueur, ou en créer de nouvelles.
* `/api/joueurs/{idJoueur}/inscriptions/{idInscription}` : pour récupérer les détails d'une inscription d'un joueur, ou la supprimer.
* `/api/clubs/{idClub}/inscriptions/` : pour récupérer les inscriptions d'un club, ou en créer de nouvelles.
* `/api/clubs/{idClub}/inscriptions/{idInscription}` : pour récupérer les détails d'une inscription d'un club, ou la supprimer.

On pourrait soit garder la route de base `/api/inscriptions` et ajouter les nouvelles routes ou alors seulement garder les nouvelles routes.
Nous allons choisir la deuxième option.

```php
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/inscriptions',
    operations: [
        new Post(provider: CreateProvider::class),
        new GetCollection(),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'joueur',
            fromClass: Joueur::class,
        )
    ],
    normalizationContext: ["groups" => ["inscription:club:read"]],
    denormalizationContext: ["groups" => ['inscription:club:write']],
    validationContext: ["groups" => ["Default", "inscription:club:write"]],
)]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/inscriptions/{idInscription}',
    operations: [
        new Get(),
        new Delete(),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'joueur',
            fromClass: Joueur::class,
        ),
        'idInscription' => new Link(
            fromClass: Inscription::class,
        )
    ],
    normalizationContext: ["groups" => ["inscription:club:read"]],
)]
#[ApiResource(
    uriTemplate: '/clubs/{idClub}/inscriptions',
    operations: [
        new Post(provider: CreateProvider::class),
        new GetCollection(),
    ],
    uriVariables: [
        'idClub' => new Link(
            toProperty: 'club',
            fromClass: Club::class,
        )
    ],
    normalizationContext: ["groups" => ['inscription:joueur:read']],
    denormalizationContext: ["groups" => ['inscription:joueur:write']],
    validationContext: ["groups" => ["Default", "inscription:joueur:write"]]
)]
#[ApiResource(
    uriTemplate: '/clubs/{idClub}/inscriptions/{idInscription}',
    operations: [
        new Get(),
        new Delete(),
    ],
    uriVariables: [
        'idClub' => new Link(
            toProperty: 'club',
            fromClass: Club::class,
        ),
        'idInscription' => new Link(
            fromClass: Inscription::class,
        )
    ],
    normalizationContext: ["groups" => ['inscription:joueur:read']]
)]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['inscription:joueur:read', 'inscription:club:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:joueur:write'])]
    #[Assert\NotNull(groups: ['inscription:joueur:write'])]
    #[Groups(['inscription:joueur:write', 'inscription:joueur:read'])]
    private ?Joueur $joueur = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:club:write'])]
    #[Assert\NotNull(groups: ['inscription:club:write'])]
    #[Groups(['inscription:club:write', 'inscription:club:read'])]
    private ?Club $club = null;
}
```

Il est important de noter que notre exemple est assez complexe au niveau des **groupes de validation** et de **sérialisation**, car nous avons voulu
faire en sorte de pratiquement tout avoir avec (en plus) des relations **bidirectionnelles** :

* Lister les clubs dans les données du joueur et inversement.
* Avoir des routes spécifiques pour lire, créer ou supprimer des inscriptions par rapport à un joueur ou à un club.

Dans un cas concret, vous pourriez vouloir seulement un sous-ensemble de ces possibilités.

#### Routes composées des identifiants des entités

On pourrait reprendre le style de route que nous utilisions dans la première solution et les appliquer avec notre implémentation actuelle :

* `PUT /joueurs/{idJoueur}/clubs/{idClub}` : pour ajouter un joueur à un club.
* `DELETE /joueurs/{idJoueur}/clubs/{idClub}` : pour retirer un joueur d'un club.
* `GET /joueurs/{idJoueur}/clubs/{idClub}` : pour vérifier si un joueur est inscrit à un certain club.

Et les routes inverses (`/clubs/{idClub}/clubs/{idJoueur}`).

Ce qui nous permettrait de **compléter** notre ensemble de route.

Cependant, tout cela demande aussi de coder un **StateProvider** et un **StateProcessor** dédiés :

```php
namespace App\State;
class InscriptionProvider implements ProviderInterface
{
    public function __construct(
        private InscriptionRepository $inscriptionRepository,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
       return $this->inscriptionRepository->findOneBy(["joueur" => $uriVariables["idJoueur"], "club" => $uriVariables["idClub"]]);
    }
}

namespace App\State;
class InscriptionProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private JoueurRepository $joueurRepository,
        private ClubRepository $clubRepository,
    )
    {}
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $joueur = $this->joueurRepository->find($uriVariables["idJoueur"]);
        if(!$joueur) {
            throw new NotFoundHttpException("Joueur inexistant.");
        }
        $club = $this->clubRepository->find($uriVariables["idClub"]);
        if(!$club) {
            throw new NotFoundHttpException("Club inexistant.");
        }
        if(!$data) {
            $data = new Inscription();
        }
        $data->setJoueur($joueur);
        $data->setClub($club);
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
```

Et enfin, on définit les routes dans `Inscription` :

```php
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/clubs/{idClub}',
    operations: [
        new Get(),
        new Delete(),
        new Put(deserialize: false, processor: InscriptionProcessor::class, allowCreate: true),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'joueur',
            fromClass: Joueur::class,
        ),
        'idClub' => new Link(
            toProperty: 'club',
            fromClass: Club::class,
        )
    ],
    normalizationContext: ["groups" => ['inscription:club:read']],
    provider: InscriptionProvider::class
)]
#[ApiResource(
    uriTemplate: '/clubs/{idClub}/joueurs/{idJoueur}',
    operations: [
        new Get(),
        new Delete(),
        new Put(deserialize: false, processor: InscriptionProcessor::class, allowCreate: true),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'joueur',
            fromClass: Joueur::class,
        ),
        'idClub' => new Link(
            toProperty: 'club',
            fromClass: Club::class,
        )
    ],
    normalizationContext: ["groups" => ['inscription:joueur:read']],
    provider: InscriptionProvider::class
)]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['inscription:joueur:read', 'inscription:club:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:joueur:write'])]
    #[Assert\NotNull(groups: ['inscription:joueur:write'])]
    #[Groups(['inscription:joueur:write', 'inscription:joueur:read'])]
    private ?Joueur $joueur = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:club:write'])]
    #[Assert\NotNull(groups: ['inscription:club:write'])]
    #[Groups(['inscription:club:write', 'inscription:club:read'])]
    private ?Club $club = null;
}
```

L'idée ne serait pas forcément de remplacer les routes précédentes, mais elles pourraient éventuellement remplacer (ou compléter) celles qui permettent de créer, lire et supprimer une inscription.
Pour la **lecture** et la **supression**, on a uniquement besoin de connaître les identifiants du **joueur** et du **club** concerné, sans avoir besoin de connaître l'identifiant "technique" de l'entité inscription.

**Note à part** : en codant des **StateProviders** adéquats, on pourrait coder la logique des routes suivantes `api/joueurs/{id}/clubs` (dans `Club`) et `api/clubs/{id}/joueurs` (dans `Joueur`).

### Utilisation d'une entité avec une clé composite

Une dernière solution (que nous allons présenter birèvement) serait d'utiliser une entité avec une 
[clé composite](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/tutorials/composite-primary-keys.html) liée à la fois à un **joueur** et un **club** :

En base de données, cela génèrera une table **Inscription**(<u>#idJoueur</u>, <u>#idClub</u>) comme celle présentée dans le schéma relationnel initial.

Notre entité `Inscription` à deux relations `ManyToOne` : une vers le `Joueur` et l'autre vers `Club`. 
        
Du côté de `Joueur` et `Club`, on a alors des **collections** d'entités `Inscription` (on supprime les relations `ManyToOne` 
entre **Club** et **Joueur**).

On peut créer, supprimer ou mettre à jour des entités **Inscription** en utilisant les identifiants du **joueur** et du **club** concernés.

Cette méthode respecte la modélisation initiale du modèle E/A et la contrainte d'unicité 
est naturellement gérée au travers de la clé (pas deux fois la même inscription d'un joueur à un même club).

Dans l'idée, pour mettre en place cette solution, il faudrait suivre ces étapes :

1. On n'associe pas `Joueur` et `Club` avec une `ManyToMany`.

2. À la place, on crée une nouvelle entité `Inscription` (et son repository) composée :

    * D'une relation `ManyToOne` avec `Joueur` qui **ne doit pas pouvoir être nulle** qui peut être éventuellement bidirectionnelle (collection d'inscriptions dans `Joueur`).

    * D'une relation `ManyToOne` avec `Club` qui **ne doit pas pouvoir être nulle** qui peut être éventuellement bidirectionnelle (collection d'inscriptions dans `Club`).

    * D'une **clé composite** composée du `joueur` et du `club`.

3. On ajoute des **asertions** `NotBlank` et `NotNull` sur `joueur` et `club`.

4. On met à jour la structure de la base de données avec doctrine.

Globalement, notre classe `Inscription` ressemblerait à ceci :

```php
#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ApiResource]
class Inscription
{
    #[Id, ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:joueur:write'])]
    #[Assert\NotNull(groups: ['inscription:joueur:write'])]
    private ?Joueur $joueur = null;

    #[Id, ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:club:write'])]
    #[Assert\NotNull(groups: ['inscription:club:write'])]
    private ?Club $club = null;
}
```

Il faudrait ensuite coder les différentes routes en utilisant un **StateProvider** et des **StateProcessor** customisés.

Par exemple :

```php
#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/clubs/{idClub}',
    operations: [
        new Put(deserialize: false, processor: InscriptionProcessor::class, allowCreate: true),
        new Get(),
        new Delete(),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            fromClass: Joueur::class
        ),
        'idClub' => new Link(
            fromClass: Club::class
        ),
    ],
    provider: InscriptionProvider::class
)]
class Inscription
{
    #[Id, ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:joueur:write'])]
    #[Assert\NotNull(groups: ['inscription:joueur:write'])]
    private ?Joueur $joueur = null;

    #[Id, ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:club:write'])]
    #[Assert\NotNull(groups: ['inscription:club:write'])]
    private ?Club $club = null;
}

class InscriptionProvider implements ProviderInterface
{

    public function __construct(private InscriptionRepository $repository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return $this->repository->find(["joueur" => $uriVariables["idJoueur"], "club" => $uriVariables["idClub"]]);
    }
}

class InscriptionProcessor implements ProcessorInterface
{
    public function __construct(
        private JoueurRepository $joueurRepository,
        private ClubRepository $clubRepository,
        private EntityManagerInterface $entityManager
    )
    {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if(!$data) {
            $joueur = $this->joueurRepository->find($uriVariables["idJoueur"]);
            if(!$joueur) {
                throw new NotFoundHttpException("Joueur inexistant.");
            }
            $club = $this->clubRepository->find($uriVariables["idClub"]);
            if(!$club) {
                throw new NotFoundHttpException("Club inexistant.");
            }
            $data = new Inscription();
            $data->setJoueur($joueur);
            $data->setClub($club);
            $this->entityManager->persist($data);
            $this->entityManager->flush();
        }
       
        return $data;
    }
}
```

* On pourra ensuite utiliser la route : `/api/joueurs/{idJoueur}/clubs/{idClub}` pour créer, récupérer ou supprimer une inscription entre un joueur et un club (et inversement).

* On pourrait aussi ajouter les routes `/api/joueurs/{idJoueur}/inscriptions` et `/api/clubs/{idClub}/inscriptions` 
ou même `/api/joueurs/{idJoueur}/clubs/` et `/api/clubs/{idClub}/joueurs/`.

**Avantages de cette solution**

* Plus respectueuse du modèle E/A et du schéma relationnel initial.

* Ne nécessite pas d'identifiant "technique" supplémentaire.

* S'adapte à toutes les situations.

**Inconvénients**

* Dans le pratique, même si Doctrine gère les **clés composites**, [il est déconseillé de les utiliser](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/best-practices.html#avoid-composite-keys) par soucis de performance.

* La mise en place d'une telle méthode peut être difficile au travers d'API Platform.

* Comme il n'y a pas d'identifiants "technique" (comme avec les inscriptions), l'architecture de la route peut être compliquée à gérer et à faire évoluer.

* Architecture de route compliquée s'il y a plus de deux entités (ternaires, etc...).

### Évolution de l'association

Une association binaire entre deux classes peut éventuellement évoluer :

* Si on ajoute des données sur l'association (elle devient alors **porteuse**).

* Si de nouvelles entités participent à l'association (elle devient une ternaire ou plus).

Avec les deux dernières solutions présentées (mais principalement celle avec la classe coordinatrice), 
il est facile de faire évoluer la ressource :

* Porteuse : on ajoute de nouveaux attributs dans l'entité.
* Nouvelle entité (transformation de l'association en n-aire avec n > 2) : on ajoute de nouvelles relations dans l'entité.

Imaginons qu'on souhaite connaître la date d'inscription d'un joueur à un club : il suffit d'ajouter 
une propriété `$dateInscription` à notre entité `Inscription` :

```php
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['inscription:joueur:read', 'inscription:club:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:joueur:write'])]
    #[Assert\NotNull(groups: ['inscription:joueur:write'])]
    #[Groups(['inscription:joueur:write', 'inscription:joueur:read'])]
    private ?Joueur $joueur = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(groups: ['inscription:club:write'])]
    #[Assert\NotNull(groups: ['inscription:club:write'])]
    #[Groups(['inscription:club:write', 'inscription:club:read'])]
    private ?Club $club = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;
}
```

On pourra soit la fournir dans le **payload**, soit la générer automatiquement
(dans cas, il faudrait interdire l'écriture sur cette propriété).

## Conclusion

À travers les deux parties de ce complément de TD, vous avez appris à :

* Mieux gérer les différents types de **relations**.

* Implémenter des associations **plusieurs-plusieurs** binaires (ou plus) avec différentes solutions.

* Utiliser plus amplement le système de **sous-ressource**.

* Utiliser des **StateProvider**.

* Gérer la **sérialisation** entre différentes classes de manière plus poussée.

Attention, comme mentionné quelques fois dans ce complément, nous n'avons pas (beaucoup) géré la **sécurité** des actions.

Dans un contexte réel, il faudrait utiliser le paramètre **security** sur les différentes routes et opérations 
(et coupler cela avec des **Voters**) afin de vérifier que l'utilisateur a bien le droit de faire une action donnée, en fonction
des données la ressource créée ou modifiée.

Il faudrait aussi gérer les **groupes de dénormalisaiton** pour définir quand une propriété peut être modifiée ou non.

Par exemple :

* Un joueur ne doit pas pouvoir s'attribuer le casier d'un autre joueur.
* Un joueur ne peut pas s'attribuer le résultat d'un autre joueur.
* Dans une inscription, on ne doit pas pouvoir changer le joueur ou le club concerné.
* Un joueur ne doit pas pouvoir modifier sa liste d'inscriptions (à des clubs) notamment en précisant une inscription existante à laquelle il n'est pas liée...

Bref, il est important de garder tout cela en tête !
