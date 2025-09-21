---
title: TD4 &ndash; Développement d'une API REST
subtitle: API, REST, API Platform
layout: tutorial
lang: fr
---

## Introduction

Dans ce TD, nous allons mettre en place une **API REST** pour l'application **The Feed** que nous avons développé lors des TDs précédemment. Nous n'allons pas directement poursuivre le code du site que vous avez développé (dans une API, il n'y a que le côté "back-end", hors pages de documentation) mais vous pourrez reprendre certains bouts de code qui pourront être réutilisés (notamment les assertions).

Nous avons déjà abordé la notion d'API l'an dernier. Nous allons tout de même faire une petite remise à niveau sur les **API** et plus particulièrement sur les **API REST**.

De nos jours, les architectures qui séparent activement la partie `back-end` (serveur, routage, services, accès aux données) de la partie `front-end` (ce qui est rendu niveau client, pages html, etc...) sont de plus en plus privilégiées. En effet, une telle séparation permet notamment d'utiliser un même serveur applicatif avec plusieurs technologies clientes (une application sur smartphone, une application Vue.js, React, etc...).

Dans ce fonctionnement, le serveur ne doit alors que renvoyer des données (généralement au format `JSON`, ou bien parfois `XML`) mais il ne se charge pas du rendu de la page. Ce sont alors les technologies clientes qui, une fois les données récupérées auprès du serveur, les utilisent pour mettre à jour leur interface. On appelle alors le programme côté back-end une `API` pour `Application Programming Interface`. Cela signifie en fait que ce programme est lui-même un `service` qui sert à exécuter des actions et récupérer de l'information, mais pas de document (pages web) à proprement parler.

Dans le monde des **API**, il existe un **style architectural** nommé `REST` pour **representational state transfer**. Dans cette architecture on parle de **ressources** pour faire référence aux différentes entités de notre service (publications, utilisateurs, commentaires...). Ces ressources doivent être désignées à travers des routes formulées de manière précise, par exemple :

* `/utilisateurs` : désigne tous les utilisateurs

* `/utilisateurs/1` : désigne l'utilisateur 1

* `/publications` : désigne toutes les publications

* `/publications/3` : désigne la troisième publication

* `/utilisateurs/1/publications` : désigne toutes les publications de l'utilisateur numéro 1

* `/utilisateurs/1/publications/5` : désigne la 5ème publication de l'utilisateur numéro 1

Grossièrement, on retrouve le schéma `/{ensemble}/{id}/...` etc...

Pour manipuler ces ressources, on utilise les méthodes `HTTP` suivantes :

* `GET` : Récupère les données de la ressource désignée par la route. On utilise généralement cette méthode sous deux formes : soit pour récupérer un ensemble de ressources (liste) ou bien pour récupérer une ressource précise.

* `POST` : Crée une ressource dans l'ensemble désignée par la route avec les informations fournies dans le corps de la requête.

* `PUT` : Remplace complètement la ressource désignée par la route avec les informations fournies dans le corps de la requête (tous les attributs nécessaires doivent donc être spécifiés, comme pour `POST`). On écrase l'ancienne ressource avec la nouvelle.

* `PATCH` : Met à jour partiellement les données de la ressource désignée par la route avec les informations fournies dans le corps de la requête (seuls les attributs qui ont besoin d'être mis à jour doivent être spécifiés).

* `DELETE` : Supprime la ressource désignée par la route.

Donc, par exemple, on pourrait avoir :

* `GET /publications` : Renvoie toutes les publications.

* `GET /publication/1` : Renvoie les informations de la publication ayant pour identifiant 1.

* `POST /publication` : Créé une nouvelle publication (et renvoie ses informations).

* `DELETE /utilisateurs/3` : Supprime l'utilisateur numéro 3.

Bien entendu, pour une ressource donnée, le développeur n'est pas obligé d'implémenter toutes les méthodes. Parfois, il n'est pas souhaitable que toutes les opérations soient disponibles pour agir sur une ressource.

Dans les requêtes de type **création / modification** (`POST`, `PUT`, `PATCH`), le client envoie des données dans le corps de la requête, généralement sous le même format que les réponses de l'API (donc, généralement, du `JSON`). Ce corps de requête contenant les données est appelé `payload`.

En plus de cela, une `API REST` doit être **sans état** (stateless), c'est-à-dire que le serveur **ne doit pas stocker d'informations sur l'état du client**. Donc, par exemple, l'utilisation d'une session est interdite. À la place, le client peut stocker des données sous la forme de `token` qui peuvent être lus et vérifiés par le serveur. C'est notamment grâce à ce système qu'on pourra authentifier nos utilisateurs et ajouter de la sécurité sur certaines routes.

**On rappelle que les commandes doivent être exécutées à l'intérieur de votre conteneur Docker.**

## Mise en place avec Symfony

Pour développer le plus simplement possible notre **API**, nous allons utiliser un outil appelé **API Platform** exploitable au travers de **Symfony**.

Cet outil est assez puissant, car il permet de centraliser une grande partie de la logique de l'API au niveau des classes **entités**. Il y a peu de code à écrire dans d'autres classes (sauf pour certaines opérations spécifiques plus complexes) et généralement pas du tout de contrôleur à mettre en place (sauf opérations très particulières, comme un webhook, par exemple pour Stripe). À partir des données des entités et des différents attributs, **API Platform** se charge de générer les routes et appliquer les différentes opérations de récupération, de sauvegarde, de vérification, etc... Vous n'aurez quasiment pas à manipuler de repositories par vous-même.

Vous allez vite vous rendre compte qu'il est possible de configurer beaucoup d'aspects de l'API au travers des **attributs** (comme nous le faisions pour les routes, par exemple). Bien sûr, nous n'allons pas explorer toutes les possibilités qu'offre l'outil aujourd'hui, mais vous pourrez pousser plus loin dans votre projet !

Comme expliqué plus tôt, nous allons créer un nouveau projet indépendamment de celui que vous avez déjà développé dans les trois premiers TDs. Il faut donc reprendre les étapes d'installation.

<div class="exercise">

1. Depuis le terminal (dans le conteneur docker), assurez-vous d'être bien placé dans `/var/www/html` puis exécutez la commande suivante :

    ```bash
    composer create-project symfony/skeleton:"6.4.*" the_feed_api
    ```

    Le projet est accessible sur votre machine (hors conteneur) dans le dossier `shared/public_html/the_feed_api`.

2. Toujours dans votre conteneur, installez maintenant la librairie spécifique à API Platform :

    * Dans le dossier du projet, exécutez la commande suivante :

        ```bash
        composer require api
        ```

        Répondez `n` (no) à la question posée.

        Puis (pour générer le fichier `.htaccess` dans `public`) :

        ```bash
        composer require symfony/apache-pack
        ```

        Répondez `y` (oui) à la question posée.

3. Donnez au serveur web les **permissions** pour créer et éditer des fichiers dans votre projet (à exécuter depuis la racine du projet) :

   ```bash
   chown -R 1000:www-data .
   ```

   Il peut y avoir des erreurs et certaines permissions non accordées, ce n'est pas grave.

4. Ouvrez le fichier `config/packages/api_plaform.yaml` et éditez-le ainsi :

   ```yaml
   api_platform:
       title: The Feed API
       version: 1.0.0
       defaults:
           cache_headers:
               vary: ['Content-Type', 'Authorization', 'Origin']
           formats:
               jsonld: ['application/ld+json']
               json: ['application/json']
   ```

    Les deux options dans `formats` vont nous permettre d'utiliser deux formats pour la lecture et l'écriture de données : `json` (que vous connaissez bien) mais aussi un format plus évolué, le `ld+json`.

5. Nous allons maintenant configurer la base de données dans le fichier `.env`. Configurez donc une nouvelle base (nouveau nom) et ne réutilisez pas la précédente (par exemple, nommez la base `the_feed_api`). Pour rappel, comme nous sommes dans le conteneur, l'adresse hôte à utiliser est `db`, le login `root` et le mot de passe `root`. Le port est `3306`.

6. Exécutez la commande suivante afin de créer la base :

    ```bash
    php bin/console doctrine:database:create
    ```
    
7. En vous rendant dans le dossier du projet, videz le cache :

    ```bash
    php bin/console cache:clear
    ```

8. Rendez-vous sur votre site à l'adresse : [https://localhost/the_feed_api/public/api/](https://localhost/the_feed_api/public/api/). Si cela fonctionne, tout est prêt. Vous devriez voir une page liée à **API Platform**.

</div>

Vous aurez peut-être remarqué que, même si nous nous trouvons dans l'environnement `dev`, il faut vider le cache. Cela est dû au fonctionnement interne d'API Platform et malheureusement, nous ne pouvons pas vraiment faire autrement. Après certaines modifications, il faudra donc vider le cache pour que les changements soient pris en compte.

Retenez donc bien cette commande :

```bash
php bin/console cache:clear
```

Ou bien, sa version abrégée :

```bash
php bin/console c:c
```

## Les publications

Nous allons commencer par créer le code correspondant à la ressource **publication**.

Nous pourrons très rapidement tester cette première ressource grâce au récapitulatif donné par `API Platform` et via l'outil `Postman` dont nous reparlerons plus tard. Dans un second temps, nous allons configurer l'entité pour fixer des règles plus précises.

Pour le moment, comme dans le premier TD, nos publications n'auront pas d'auteurs.

### Création de l'entité

Pour générer le code de base lié à l'entité publication, nous allons utiliser la commande `make:entity` que vous connaissez bien. Néanmoins, le `bundle` permettant d'utiliser ces commandes n'est pas installé. En effet, vous aurez peut-être remarqué que contrairement au premier TD, nous n'avons pas installé l'ensemble de librairie `webapp` (avec composer). C'est un ensemble de librairies utiles pour développer un site web traditionnel, mais dans notre cas, nous n'avons pas besoin de tout ça pour construire une API. Le bundle `maker` fait partie de ce lot, mais nous pouvons l'installer indépendamment.

<div class="exercise">

1. Installez le bundle `maker-bundle` :

    ```bash
    composer require symfony/maker-bundle --dev
    ```

2. Créez une nouvelle entité `Publication` à l'aide de la commande `make:entity` en ajoutant l'option `--api-resource` qui permet d'indiquer à Symfony que nous créons une entité liée à **API Platform**.

    Pour les propriétés à ajouter :

    * `message` de type `text` (`null` non autorisé).

    * `datePublication` de type `datetime` (`null` non autorisé).

3. Allez observer le code de la nouvelle classe créée dans `src/Entity`.

4. Videz le cache.

5. Mettez à jour la structure de votre base de données. Pour rappel, les commandes à utiliser sont :

    ```bash
    php bin/console make:migration
    php bin/console doctrine:migrations:migrate
    ```

    Vérifiez que votre base de données est bien à jour.

</div>

Comme vous pouvez le constater, en plus des annotations liées à doctrine que vous connaissez déjà, une annotation `#[ApiResource]` est présente. Elle permet simplement d'indiquer à l'application que cette entité est une ressource de l'API. Avec cette configuration simple, notre ressource est manipulable avec toutes les méthodes HTTP.

### Premières requêtes

Tout est en place pour faire nos premières requêtes ! Nous pouvons manipuler les **publications** via la route `/api/publications` à partir de la racine de notre site. Mais tout d'abord, nous allons visualiser les détails de notre nouvelle ressource.

<div class="exercise">

1. Rechargez la page web de présentation de l'API : [https://localhost/the_feed_api/public/api/](https://localhost/the_feed_api/public/api/).

2. Explorez la documentation des différentes méthodes proposées. Vous pouvez même envoyer des requêtes avec le bouton `Try it out`.

</div>

Pour aller déclencher notre `API`, nous n'allons pas directement utiliser l'interface web proposée par `API Platform`, mais plutôt un logiciel nommé `Postman` (nous l'avions rapidement abordé dans les TDs de complément web l'an dernier).

Ce logiciel va permettre de paramétrer et d'envoyer des requêtes de manière interactive et de visualiser le résultat très simplement. Sur les machines de l'IUT, il est déjà installé, chez-vous, vous pouvez le télécharger [ici](https://www.postman.com/downloads/?utm_source=postman-home).

Nous allons donc tenter de manipuler notre ressource `publication` à l'aide de ce logiciel !

<div class="exercise">

1. Allumez **postman**. L'application vous propose de créer un compte, mais vous n'en avez pas besoin. Cliquez simplement sur "**Skip signing in and take me straight to the app**" tout en bas.

2. Sur l'interface, créez un nouvel onglet et paramétrez-le ainsi :

    * Dans la liste déroulante du choix des méthodes, sélectionnez `POST`.

    * Juste à côté, le champ permet de rentrer l'URL de la ressource à viser. Par exemple : [https://localhost/the_feed_api/public/api/publications](https://localhost/the_feed_api/public/api/publications) (pour Linux/macOS, il faut descendre un peu plus bas sur la page)

    * Comme mentionné plus tôt, avec les requêtes de **création / modification**, nous avons besoin d'un `payload` au format `JSON`. Pour le configurer, rendez-vous dans `Body` puis sélectionnez l'option `raw` et enfin, à droite, sélectionnez `JSON` dans la liste déroulante (à la place de `Text`).

    * Remplissez la zone de saisie des données avec le `payload` suivant :

    ```json
    {
        "message": "Hello world!",
        "datePublication": "2024-11-07T21:18:27.568Z"
    }
    ```

3. Cliquez sur **Send**. L'application envoie une alerte, car nous utilisons un certificat auto-signé pour pouvoir proposer `https` sur notre serveur web. Acceptez d'utiliser ce certificat "non sécurisé" et poursuivez la requête. Observez le résultat : la publication devrait vous être renvoyée (avec son identifiant) ! Ajoutez-en quelques autres.

4. Ouvrez un nouvel onglet et faites en sorte de récupérer la liste de toutes les publications (il n'y a pas de `payload` à mettre dans ce cas...).

5. Ouvrez un nouvel onglet et faites en sorte de récupérer une publication précise. Ici il n'y a encore pas de `payload`, l'identifiant doit être précisé au niveau de l'URL.

6. Ouvrez un nouvel onglet et faite en sorte de supprimer une publication. 
</div>

### Configuration plus poussée

À ce stade, vous avez pu tester diverses routes pour manipuler les publications, mais vous avez pu constater quelques soucis de logique. En effet, la **date** est un paramètre rentré par le client. Ce qui implique qu'il doit connaître précisément la date d'envoi, mais aussi qu'il peut la falsifier (par exemple, mettre 2050 pour être la publication la plus récente...). D'ailleurs, la date qui vous est donnée plus tôt dans le `payload` est antérieure à aujourd'hui ! 

Dans le premier TD, nous avons vu comment générer cette date automatiquement, mais il faut aussi pouvoir **empêcher** l'utilisateur de pouvoir l'envoyer dans le `payload`. Pour cela, nous pouvons nous aider de l'attribut `#[ApiProperty]`. Cet attribut se place au-dessus d'une propriété de la classe. Ses paramètres permettent de configurer la présentation de la propriété et ajouter certaines règles. Par exemple :

* `writable: true/false` : autorise ou interdit l'écriture de cette propriété lors de la création/modification (par défaut, autorisé si non précisé). Si la propriété est présente dans le payload, elle est ignorée.

* `readable: true/false` : renvoie ou non la propriété lorsqu'on lit la ressource (par défaut, autorisé).

* `description : ...` : permet de décrire le rôle de la propriété plus en détail (pour la documentation destinée aux personnes souhaitant utiliser l'API).

Si on prend l'exemple suivant :

```php
use ApiPlatform\Metadata\ApiProperty;

class Exemple {

    #[ApiProperty(description: 'Description de la propriété...', readable: false)]
    private ?string $propriete = null;

}
```

Quand je vais effectuer une requête, la propriété n’apparaîtra jamais quand je vais lire les données d'une entité `Exemple`. Cependant, il faut impérativement qu'elle soit envoyée dans le `payload` dans le cas d'une requête d'écriture. La description permet d'enrichir la page de documentation automatique générée par API Platform.

Les attributs `write` et `read` sont utiles quand on souhaite appliquer la même logique dans n'importe quel contexte. Cependant, pour pouvoir autoriser la lecture ou l'écriture d'une propriété (ou d'une ressource) selon le contexte (droits d'accès, groupe, type de requête...) on utilisera plutôt les **groupes de validation** et les **groupes de sérialisation**.

Dans l'annotation `#[ApiResource]` au-dessus de la classe, il est possible de rajouter un paramètre `order` pour spécifier comment sont ordonnés les résultats d'une requête renvoyant une collection de cette ressource. 

On le spécifie ainsi : `#[ApiResource(order : ["attribut1" => "ASC ou DESC", "attribut2" => "ASC OU DESC", ...])]`. 

On trie les résultats par rapport au premier attribut spécifié puis, en cas d'égalité, par rapport au second, et ainsi de suite (similaire au `ORDER BY` en SQL). Les valeurs `ASC` ou `DESC` permettent de spécifier le sens du tri (croissant ou décroissant).

```php
//Quand je récupère l'ensemble des entreprises, elles sont triées de celle possédant le plus gros CA à celle possédant le plus petit CA.
#[ApiResource(
    ...,
    order : ["chiffreAffaire" => "DESC"]
)]
class Entreprise {

    private ?string $nom = null;

    private ?float $chiffreAffaire = null;

}
```

Concernant la **génération automatique** d'une propriété (dans notre cas, la date) nous avons déjà vu cela dans le premier TD. Il vous suffit d'ajouter l'attribut `#[ORM\HasLifecycleCallbacks]` au niveau de la classe puis créer une méthode initialisant la date, annotée avec l'attribut `#[ORM\PrePersist]`.

<div class="exercise">

1. Faites les modifications nécessaires au niveau de l'entité `Publication` afin qu'une date entrée dans le `payload` soit ignorée (qu'on ne puisse pas l'écrire). Ensuite, pour que celle-ci soit plutôt générée automatiquement du côté du serveur, on pourra utiliser le code suivant qu'on avait déjà utilisé dans les TDs précédents :

    ```php
    #[ORM\HasLifecycleCallbacks]
    class Publication {

        #[ORM\PrePersist]
        public function prePersistDatePublication() : void {
            $this->datePublication = new \DateTime();
        }

    }
    ```

2. Nous allons reprendre les **assertions** de notre ancienne classe `Publication` pour la propriété `message`. Concernant l'assertion `Length`, nous allons en garder qu'une seule pour le moment, et sans son groupe de validation (nous ajouterons le premium plus tard...).

    ```php
    use Symfony\Component\Validator\Constraints as Assert;

    class Publication {

        #[ORM\Column(type: Types::TEXT)]
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 4,
            max: 50,
            minMessage: "Le message est trop court! (4 caractères minimum)",
            maxMessage: "Le message est trop long! (50 caractères maximum)"
        )]
        private ?string $message = null;
    }
    ```

3. Faites en sorte que la collection de publications renvoyées soit triées de la plus récente à la plus ancienne.

4. Videz le cache.

5. Sur `Postman`, testez que tous les changements sont bien pris en compte. Il est important de noter qu'au niveau de la requête `POST`, même si la date est précisée, celle-ci doit être ignorée.

</div>

Maintenant, nous aimerions interdire l'utilisation de certaines méthodes. En effet, nous ne voulons pas que les publications soient modifiables. Il faut donc interdire les méthodes `PUT` et `PATCH`, ou plutôt, autoriser seulement les autres méthodes. Pour cela, il suffit d'utiliser le paramètre `operations` au niveau de l'annotation `#[ApiResource]`. Ce paramètre est une **liste** des opérations permises, sous la forme d'objets (qu'on peut d'ailleurs configurer de manière ciblée).

Les opérations possibles sont :

* `GetCollection` : récupération d'un ensemble de ressources.
* `Get` : récupération d'une ressource ciblée.
* `Post` : création d'une ressource.
* `Put` : mise à jour complète d'une ressource ciblée.
* `Patch` : mise à jour partielle d'une ressource ciblée.
* `Delete` : suppression d'une ressource ciblée.

Ainsi, la configuration suivante permet seulement l'utilisation de la méthode `Get` ciblée et `Delete` :

```php
#[ApiResource(
    operations: [
        new Get(),
        new Delete()
    ]
)]
```

Petite note à part, par défaut, `GetCollection` utilise un système de pagination afin de limiter le nombre de ressources renvoyées (par défaut, 30). Il est alors possible de préciser un paramètre `page` dans le **query string** de la route, pour naviguer. Par exemple, `/api/publications` renvoie les publications de 1 à 30. Et `/api/publications?page=3` renvoie les publications de 61 à 90.

Ce système est nécessaire afin de limiter les données lues côté client et ainsi charger le contenu au fur et à mesure (imaginez si vous deviez charger tout **Twitter/X** à chaque accès !!!). Il est possible d'augmenter le nombre de ressources renvoyées par page ou bien simplement désactiver ce système (et donc tout renvoyer à chaque fois). Par convenance dans le cadre de ce TP, nous allons donc désactiver ce système, mais retenez bien que dans un contexte réel, il faudrait le conserver et charger le contenu petit à petit, au fil du parcours de l'utilisateur.

Pour configurer tout cela, on édite le contenu du fichier `config/packages/api_platform.yaml` :

```yaml
# Dans config/packages/api_platform.yaml
api_platform:
    ...
    defaults:
        ...
        pagination_items_per_page: 30 # Pour changer le nombre de ressources renvoyées si la pagination est activée
        pagination_enabled: true/false # Active ou désactive la pagination
```

<div class="exercise">

1. Modifiez le fichier `config/package/api_platform.yaml` afin de désactiver la pagination.

2. Empêchez l'utilisation des méthodes `PUT` et `PATCH` sur les publications.

3. Videz le cache.

4. En utilisant `Postman`, vérifiez qu'il n'est effectivement plus possible d'utiliser ces méthodes. Vérifiez aussi que les autres méthodes fonctionnement toujours.

</div>

Parfait, vous avez configuré votre première ressource ! Comme vous l'avez constaté, nous n'avons pas eu besoin de produire du code hors de la classe `Publication` pour faire tout cela.

## Les utilisateurs

Il est temps de nous attaquer à la création de nos **utilisateurs** qui vont, eux aussi, être des **ressources** de l'application.

Pour créer la ressource `Utilisateur`, nous n'allons pas nous embêter et reprendre la classe `Utilisateur` (et son repository) que vous avez déjà développé dans le projet précédent et l'adapter légèrement. En fait, pour spécifier que notre entité est une ressource de l'API, il suffit de rajouter l'attribut `ApiRessource` au-dessus de la classe.

<div class="exercise">

1. Importez la classe `Utilisateur` dans `src/Entity` ainsi que la classe `UtilisateurRepository` dans `src/Repository` depuis le projet précédent.

2. Pour le moment, supprimez la propriété `publications`, le bout de code correspondant dans le constructeur ainsi que les méthodes qui lui sont liées (add, remove, get...). Nous ferons le lien entre publication et utilisateur plus tard.

3. Supprimez la propriété `nomPhotoProfil`, ses attributs et ses getters/setters. Nous ne gérerons pas l'upload de photo de profil dans notre API (cela est néanmoins possible !).

4. Concernant le **mot de passe**, nous gérerons ça plus tard, donc pour le moment, **commentez** simplement la propriété `password`, ses attributs, ses getters/setters et la déclaration de l'implémentation de l'interface `PasswordAuthenticatedUserInterface` (au niveau du `implements`...). Il faut aussi commenter dans `UtilisateurRepository` la déclaration de l'implémentation de l'interface `PasswordUpgraderInterface` ainsi que la méthode `upgradePassword`.

5. Faites en sorte que la propriété premium puisse être lue, mais jamais écrite.

6. Faites en sorte que cette entité soit utilisée comme une ressource et interdisez l'utilisation de la méthode `PUT` (mise à jour partielle avec `PATCH` autorisée, mais pas de mise à jour complète avec `PUT`).

7. Videz le cache.

8. Synchronisez vos changements avec la base de données (souvenez vous des 2 commandes à utiliser...)

9. Rechargez la page listant les opérations de l'API et vérifiez que votre utilisateur apparait bien.

10. Testez de créer des utilisateurs (login/adresseEmail, pas de mot de passe pour le moment) et vérifiez que vos contraintes sont respectées (essayez de rentrer un login trop court ou trop long, une adresse email au mauvais format, etc...). Vérifiez aussi que, même si la propriété `premium` est précisé dans le payload, avec la valeur `true`, elle est ignorée (la valeur `premium` de l'utilisateur reste à `false`).

11. Testez la modification d'un utilisateur existant avec `PATCH`, en configurant la requête correctement, sur `Postman`. Là aussi, on doit pouvoir modifier le login et l'adresse email, mais toute modification sur le statut premium est ignorée. On envoie le même type de `payload` que lors d'un `POST`, sauf qu'on indique seulement les attributs qu'on souhaite mettre à jour.

12. Testez également la méthode `GET` (récupérer tous les utilisateurs, récupérer un utilisateur précis...)
</div>

## Connexion entre les publications et les utilisateurs

Avant de mettre en place un système d'authentification, nous allons relier nos publications à nos utilisateurs.

### Associations

Pour faire en sorte qu'une publication possède un auteur, nous allons utiliser la commande `make:entity` pour modifier la classe `Publication`, en ajoutant un attribut de type `relation`, exactement comme vous l'aviez fait dans le TD2.

<div class="exercise">

1. En utilisant la commande `make:entity`, mettez à jour votre entité `Publication` en ajoutant une propriété `auteur` qui sera un **Utilisateur** :

    * La propriété ne peut pas être nulle.

    * La relation est bi-directionnelle (on doit avoir la liste des publications côté utilisateurs). Nommez cette propriété `publications` côté Utilisateur.

    * Activez la suppression des entités orphelines.

2. La stratégie de suppression doit être `CASCADE` (si un utilisateur est supprimé, toutes ses publications sont supprimées...). Si vous ne vous souvenez plus comment faire, jetez un œil à votre attribut `auteur` de la classe `Publication` du projet précédent.

3. Activez le mode de chargement `EAGER` (eager loading) pour la récupération des données de l'auteur.

4. Ajoutez les attributs (assertions) nécessaires pour "forcer" l'utilisateur à préciser l'auteur lorsqu'il crée une publication. Il faut que l'auteur ne soit pas absent du payload de la requête, et qu'il ne soit pas **null**...

5. Dans votre base de données, supprimez toutes vos publications (un champ non null va être ajouté ce qui va causer des problèmes si on laisse les publications actuelles, sans auteur).

6. Mettez à jour la structure de votre base de données avec les commandes adéquates.

7. Videz le cache.

8. Sur `Postman`, vérifiez que vous obtenez un message d'erreur si vous tentez d'ajouter une publication sans préciser son auteur.

9. Maintenant, tentez d'ajouter une publication en précisant l'identifiant numérique (son id) de l'utilisateur pour la partie `auteur`. Analysez le message d'erreur que vous obtenez.

10. La valeur à préciser pour faire référence à une autre ressource est appelé `IRI` (International Ressource Identifier) qui est une référence (un "lien") interne à l'application. Pour le trouver, récupérez (avec une requête `GET`) les détails d'un de vos utilisateurs. Il faut regarder au niveau de la propriété `@id`. Attention selon l'installation du projet sur votre serveur web, la route décrite dans `@id` n'est pas forcément la même. L'`IRI` correspond au chemin qui débute par `/the_feed_api/...`. Dans certains cas, il est aussi possible de directement créer la ressource liée en précisant ses informations dans un sous-document, mais ce n'est pas ce que nous souhaitons faire ici (on ne veut pas créer un utilisateur lorsqu'on crée une publication) et nous ne verrons pas ce mécanisme dans le cadre de ce TP.

11. Une fois le mécanisme des `IRI` compris, retentez de créer une publication en affectant un utilisateur.

12. Ajoutez plusieurs publications liées à un utilisateur et vérifiez que la suppression de l'utilisateur entraîne la suppression des publications qui lui sont liées.

</div>

### Les groupes de sérialisation

Vous remarquerez que quand on affiche la liste des publications ou une publication précise, au niveau de l'auteur, seul son `IRI` est indiqué. Cela est gênant, car si on veut connaître les détails de l'auteur (par exemple son login) on est obligé de faire une requête supplémentaire.

Il serait donc plutôt préférable d'afficher les détails de l'utilisateur à la place. Nous pouvons faire cela grâce aux **groupes de sérialisation**.

Quand on manipule nos ressources, il peut exister **deux types de contextes** :

* La **normalisation** : quand on transforme un objet de l'application en `JSON` et qu'on le renvoie au client (requête de lecture, type GET, mais aussi ce qui est renvoyé après avoir créé/mis à jour une entité).

* La **dénormalisation** : quand on charge un `payload` au format `JSON` et qu'on le transforme en objet de l'application (requêtes de création ou de mise à jour : POST/PUT/PATCH).

Dans les deux cas, il peut être possible de définir des **groupes** pour contrôler la présence d'une propriété :

* Dans le cas de la **normalisation**, il est donc possible de contrôler quelles propriétés sont **sérialisées**, c'est-à-dire, renvoyées au client sous le format `JSON`.

* Dans le cas de la **dénormalisation**, il est possible de contrôler les propriétés du payload à ignorer, dans tel ou tel contexte (par exemple, si on veut que le login soit précisé lors de la création d'un compte, mais il ne doit pas pouvoir changer lors d'une mise à jour...)

Présentement, nous allons d'abord nous intéresser à la **normalisation** pour qu'on puisse représenter quelques données de l'auteur d'une publication.

Un paramètre de l'annotation `#[ApiResource(...)` nommé `normalizationContext : ["groups" => ['nom_groupe1', ...]]` permet d'activer certains groupes pendant la phase de **normalisation**. Au niveau des propriétés, l'annotation `#[Groups(['nom_groupe1', 'nom_groupe2', ...])]` permet de faire en sorte qu'une propriété soit affichée ou non dans le document `JSON` selon le groupe activé.

Par exemple :

```php
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["serialization:etudiant:read"]],
)]
class Etudiant {

    #[Groups(['serialization:etudiant:read'])]
    private ?int $id = null;

    #[Groups(['serialization:etudiant:read'])]
    private ?string $nom = null;

    #[Groups(['serialization:etudiant:read'])]
    private ?string $prenom = null;

    public ?Groupe $groupe = null;
}

use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["serialization:groupe:read"]],
)]
class Groupe {

    #[Groups(['serialization:groupe:read'])]
    private ?int $id = null;

    #[Groups(['serialization:groupe:read'])]
    private ?string $nomGroupe = null;

    public Collection $etudiants;

}
```

Ici, quand on récupère les données d'un étudiant, on affiche seulement son id, son nom, son prénom, mais pas son groupe (même pas d'IRI).

Pour les groupes, on affiche tout sauf la liste des étudiants (qui aurait été une liste d'IRI aussi).

Pour qu'on obtienne aussi l'identifiant et le nom du groupe quand on lit les données d'un étudiant, il faut d'abord préciser le groupe `serialization:etudiant:read` au niveau de la propriété `groupe` et il faut ensuite ajouter le groupe `serialization:etudiant:read` sur chaque propriété de la classe `Groupe` qu'on souhaite afficher quand on rend le groupe d'un utilisateur : 

```php
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["serialization:etudiant:read"]],
)]
class Etudiant {

    #[Groups(['serialization:etudiant:read'])]
    private ?int $id = null;

    #[Groups(['serialization:etudiant:read'])]
    private ?string $nom = null;

    #[Groups(['serialization:etudiant:read'])]
    private ?string $prenom = null;

    #[Groups(['serialization:etudiant:read'])]
    public ?Groupe $groupe = null;
}

use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["serialization:groupe:read"]],
)]
class Groupe {

    #[Groups(['serialization:groupe:read', 'serialization:etudiant:read'])]
    private ?int $id = null;

    #[Groups(['serialization:groupe:read', 'serialization:etudiant:read'])]
    private ?string $nomGroupe = null;

    public Collection $etudiants;

}
```

On aurait aussi pu préciser directement le groupe `serialization:groupe:read` dans l'attribut `normalizationContext` de la classe `Etudiant` :

```php
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...,
    normalizationContext: ["groups" => ["serialization:etudiant:read", "serialization:groupe:read"]],
)]
class Etudiant {

    #[Groups(['serialization:etudiant:read'])]
    private ?int $id = null;

    #[Groups(['serialization:etudiant:read'])]
    private ?string $nom = null;

    #[Groups(['serialization:etudiant:read'])]
    private ?string $prenom = null;

    #[Groups(['serialization:etudiant:read'])]
    public ?Groupe $groupe = null;
}

use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...,
    normalizationContext: ["groups" => ["serialization:groupe:read"]],
)]
class Groupe {

    #[Groups(['serialization:groupe:read'])]
    private ?int $id = null;

    #[Groups(['serialization:groupe:read'])]
    private ?string $nomGroupe = null;

    public Collection $etudiants;

}
```

Si à l'inverse on avait voulu les détails de chaque étudiant d'un groupe (quand on lit le groupe) :

```php
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...,
    normalizationContext: ["groups" => ["serialization:etudiant:read"]],
)]
class Etudiant {

    #[Groups(['serialization:etudiant:read', 'serialization:groupe:read'])]
    private ?int $id = null;

    #[Groups(['serialization:etudiant:read', 'serialization:groupe:read'])]
    private ?string $nom = null;

    #[Groups(['serialization:etudiant:read', 'serialization:groupe:read'])]
    private ?string $prenom = null;

    public ?Groupe $groupe = null;
}

use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    ...,
    normalizationContext: ["groups" => ["serialization:groupe:read"]],
)]
class Groupe {

    #[Groups(['serialization:groupe:read'])]
    private ?int $id = null;

    #[Groups(['serialization:groupe:read'])]
    private ?string $nomGroupe = null;

    #[Groups(['serialization:groupe:read'])]
    public Collection $etudiants;

}
```

Si ce n'est pas encore clair, ce que nous faisons ici, c'est dire à quel groupe appartient telle ou telle propriété. Lorsque l'API traite une requête (en GET, en POST, etc...) elle n'activera pas les mêmes groupes, selon le contexte. Ces groupes définissent les propriétés qui sont renvoyées dans le JSON lors de requêtes de lecture (type GET) et à l'inverse ce qui est traité (ou ignoré, si le groupe ne correspond pas) dans le **payload** lors d'une requête d'écriture (POST, PUT, PATCH...).

Bref, les groupes activés dans `normalizationContext` traversent les entités ! Il est aussi possible de définir plusieurs groupes (affichant plus ou moins de propriétés selon le contexte). On peut même créer une classe et une méthode permettant de coder un algorithme pour savoir quels groupes sont activés (par exemple, pour afficher plus d'informations à un utilisateur connecté...).

Il faut éviter dans se retrouver dans une situation de "cycle" (par exemple, un étudiant donne aussi les infos de son groupe, qui donne les infos sur ses étudiants, qui donne les infos de leur groupe, etc...).

Si vous avez bien compris ce mécanisme, à vous de jouer !

<div class="exercise">

1. En utilisant les groupes de sérialisation, faites en sorte que quand on lit une publication, on obtienne aussi les détails de l'utilisateur (id, login, adresse email, premium).

2. Videz le cache.

3. Testez en appelant la méthode `GET` générale, mais aussi en ciblant une publication en particulier.

</div>

Nous avons défini `normalizationContext` de manière globale (pour toutes les opérations de la ressource) mais il est aussi possible de le définir pour une opération précise (dans les paramètres de `new Get()`, `new Post()`, etc...)

### Publications d'un utilisateur

Comme nous l'avons vu en introduction de ce TD, avec l'architecture `REST`, il doit être possible d'accéder à la liste des publications d'un utilisateur précis en utilisant cette route :

`/utilisateurs/{id}/publications`

Par exemple : `/utilisateurs/2/publications` : les publications de l'utilisateur 2.

Pour cela, rien de plus simple : il suffit de configurer une **nouvelle opération** `GetCollection` avec un chemin personnalisé (il est possible d'avoir plusieurs fois la même opération si les chemins sont différents). Il faut préciser un **template d'URL** et des **variables** afin d'aller chercher la ressource au bon endroit.

```php
use ApiPlatform\Metadata\Link;

#[ApiResource(
    operations: [
        ...,
        new GetCollection(
            /* Template avec nom de variables intégrées entre accolades. Il est possible d'en indiquer plusieurs */
            uriTemplate: '/chemin/{identifiant1}/ressource',
            uriVariables: [
                /* On indique comment accèder à la sous-ressource (dont l'identifiant correspondant à celui passé dans la route) */
                'identifiant1' => new Link(
                    /* La propriété qui contient la ressource ciblée dans la classe cible */
                    fromProperty: 'nom_propriete',
                    /* La classe cible dont on veut récupérer une instance à partir de l'identifiant  */
                    fromClass: Cible::class
                )
            ],
        ),
    ]
)]
```
Pour mieux illustrer cela, reprenons notre exemple d'étudiants et de groupe. On souhaite avoir une route `/groupe/{id}/etudiants` pour obtenir tous les étudiants d'un groupe précis :

```php
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new GetCollection(
            uriTemplate: '/groupes/{idGroupe}/etudiants',
            uriVariables: [
                'idGroupe' => new Link(
                    fromProperty: 'etudiants',
                    fromClass: Groupe::class
                )
            ],
        ),
    ]
)]
class Etudiant {
    private ?int $id = null;

    private ?string $nom = null;

    private ?string $prenom = null;

    public ?Groupe $groupe = null;
}
```

<div class="exercise">

1. En ajoutant une **nouvelle opération** `GetCollection` dans `Publication`, faites en sorte d'ajouter une route qui permet d'obtenir la liste des publications d'un utilisateur précis.

2. Videz le cache.

3. Testez votre nouvelle route en essayant d'afficher la liste des publications d'un de vos utilisateurs.

</div>

## Authentification

Nous avons enfin pu relier nos publications à nos utilisateurs efficacement, mais il reste plusieurs problèmes :

* Un utilisateur qui poste une publication doit connaître son `IRI`.

* On peut affecter une publication à n'importe quel utilisateur !

* N'importe qui peut supprimer un utilisateur ou une de ses publications.

* N'importe qui peut modifier le profil d'un utilisateur.

Pour régler cela, nous allons mettre en place un système d'authentification pour faire en sorte qu'une publication soit affectée à l'utilisateur authentifié qui la poste et nous rajouterons un système de sécurité pour empêcher la modification ou la suppression de ressources qui n'appartiennent pas à l'utilisateur qui émet la requête.

### Json Web Token

Comme nous l'avons mentionné au début de ce TD, l'architecture **REST** implique la notion de **stateless**, c'est-à-dire que le serveur ne garde aucune information sur l'utilisateur en mémoire vive ou avec une session, etc... Mais alors, comment mettre en place un système d'authentification et faire comprendre au serveur que l'utilisateur est légitime ? Pour cela, nous allons utiliser le mécanisme des `JSON Web Tokens` souvent abrégé en `JWT`.

Un `JWT` est une chaîne de caractères appelée `token` encodée en `base64` qui contient de l'information. Une fois décodée, ce jeton se décompose en trois parties :

* Le `header` (au format `JSON`) qui contient des informations sur la nature du jeton (le type de jeton, le type d'algorithme utilisé),

* Le `payload` (au format `JSON`) qui contient de l'information utile (que l'on souhaite lire) placée dans ce jeton,

* La `signature` qui permet de vérifier l'authenticité du jeton.

Pour créer un `JWT`, on utilise une paire clé publique/clé privée. La **clé privée** sert à créer la `signature` du jeton à partir de la concaténation du `header` et du `payload`. Elle est conservée par l'émetteur et n'est pas transmise. La **clé publique** quant à elle sert à vérifier la signature du `token` (pour attester qu'il n'a pas été modifié) et peut être distribuée. Le jeton final est encodé en `base64`.

Tout le monde peut décoder un `JWT` et lire son contenu. Donc il **ne doit pas contenir d'informations sensibles** (mot de passe, numéro de carte de crédit, etc...) Cependant, bien qu'il soit lisible par tous, il est impossible de le **falsifier**. En effet, seul l'émetteur qui possède la clé privée ayant servi à signer le jeton peut le modifier ! Si quelqu'un tente de falsifier un `JWT`, il ne pourra pas connaître la signature adéquate à apposer pour ce nouveau corps de données. Ainsi, grâce à la signature, un token falsifié sera rejeté.

Vous pouvez aller voir à quoi rassemble un `JWT` [à cette adresse](https://jwt.io/).

De nos jours, les **API** utilisent un système d'authentification par `token` comme avec les `JWT`. La logique est la suivante :

* Le serveur possède une paire clé publique/clé privée.

* Quand un utilisateur veut s'authentifier, il envoie ses identifiants. Le
  serveur les vérifie et, si tout est bon, créé un `JWT` signé avec sa clé
  privée et contenant les informations utiles au serveur, par exemple, l'id de
  l'utilisateur.

* Le serveur renvoie le `JWT` et le client le conserve.

* Dès qu'il veut accéder à une route sécurisée, le client envoie le `JWT` au serveur, en plus de sa requête.

* Quand le serveur reçoit le `JWT`, il vérifie qu'il n'a pas été falsifié (avec la clé publique) et peut donc le décoder en toute confiance et récupérer les informations de l'utilisateur à partir de son identifiant stocké dans le `JWT` (en faisant une requête sur la base pour obtenir le reste des informations, par exemple) et ainsi vérifier s'il a le droit d'effectuer cette requête.

Bien que le `JWT` soit décodable côté client, il est impossible le falsifier pour changer l'id de l'utilisateur contenu, car le serveur le détectera. Il est aussi impossible de simplement créer un `JWT` avec l'identifiant d'un autre utilisateur, car le client ne peut pas générer la signature adéquate (car il ne possède pas la clé privée).

On respecte la notion de **stateless** car le serveur ne garde aucune information sur l'utilisateur dans une session ou en mémoire vive. Le `JWT` est transmis à chaque requête par le client, quand on en a besoin, ce qui permet de vérifier sa légitimité.

Néanmoins, si ce token venait à être dérobé, alors on pourrait usurper un utilisateur. C'est pour cela que les `JWT` ont une date d'expiration (généralement, 3600 secondes, mais on peut mettre plus). Pour éviter que l'utilisateur n'ait à se reconnecter manuellement lors de l'expiration, il faut alors mettre en place tout un système de rafraîchissement que nous aborderons dans une section bonus de ce TD.

La manière dont est stocké le token côté client est également très importante ! Nous verrons les différentes problématiques de sécurités liées à cet aspect, et quelle est la manière considérée la plus "propre" et la plus sécurisée pour stocker et transmettre le `JWT`.

### Prise en charge des utilisateurs

Tout abord, nous allons devoir intégrer nos utilisateurs au système d'authentification de `Symfony` pour que par la suite, ils puissent être vérifiés et récupérés automatiquement lors de l'envoi des identifiants au serveur.

Il faut tout d'abord indiquer à Symfony que notre entité `Utilisateur` joue le rôle d'utilisateur dans notre application, en ajoutant cette section dans `config/packages/security.yaml` :

```yaml
#config/packages/security.yaml
security:
    providers:
        app_user_provider:
            entity:
                #Entité représentant nos utilisateurs
                class: App\Entity\Utilisateur
                #La propriété avec laquelle l'utilisateur s'identifie
                property: login
```

Dans le projet précédent, cette section avait été générée automatiquement, car nous avions utilisé la commande `make:user` pour créer l'entité `Utilisateur`.

Si vous vous souvenez bien, dans le formulaire de création de l'utilisateur, il y avait un champ `plainPassword` qui ne faisait pas partie de l'entité et qui était **haché** puis placé dans la propriété `password`. Mais comment faire cela, alors que nous n'avons pas de formulaire ni de contrôleur pour gérer ce comportement ?

Pour la question du champ `plainPassword`, c'est très simple : il suffit de la placer dans la classe `Utilisateur` (comme propriété de la classe) mais ne pas placer d'attribut `#[ORM\Column]` dessus. Ainsi, la propriété pourra être utilisée dans le payload mais ne sera pas enregistrée dans la base de données. On interdira l'écriture du champ `password` par l'utilisateur, car cela sera géré par l'application (pour rappel, `password` contient le mot de passe haché).

Enfin, pour pouvoir transformer `plainPassword` en mot de passe haché, nous allons utiliser une classe particulière appelée `StateProcessor`.

Sur API Platform, il existe deux types de classes importantes : Les `StateProcessor` et les `StateProvider`.

* Une classe de type `StateProcessor` est un **service** qui est appelé après avoir reçu et vérifié le `payload` lors d'une requête de création ou de mise à jour. Elle a pour but d'effectuer des traitements avant l'enregistrement en base de données.

* Une classe de type `StateProvider` va permettre à l'inverse d'appliquer des modifications aux données récupérées lors d'une opération type `GET` (lecture de données), avant de les transmettre au client.

Il est bien entendu possible d'injecter d'autres services (et paramètres) dans ces classes. Il est notamment possible d'accéder au **processeur** par défaut utilisé par API Platform pour poursuivre le traitement normal après (ou avant) avoir appliqué nos modifications (notre "logique métier"). Par exemple, le "traitement normal" opéré par le processeur d'API Platform lors d'une requête POST est de persister l'entité en base de données avec Doctrine.

Dans notre cas, il nous faut donc un `StateProcessor` afin de hacher le mot de passe avant de sauvegarder l'utilisateur dans la base de données.

La commande suivante permet d'initialiser un `StateProcessor` :

```bash
php bin/console make:state-processor MonStateProcessor
```

Ce qui donne la classe suivante :

```php
#src/State/MonStateProcessor.php
class MonStateProcessor implements ProcessorInterface {

    public function __construct(
        /* Injection de dépendances */
    )
    {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        //$data est l'entité qu'on manipule (par exemple, une Publication, un Utilisateur...)
        //$operation est l'opération exécutée (GET, POST, ...)
        //$uriVariables contient les éventuelles variables paramétrées dans le chemin de la route

        //On retourne la donnée après modification
        return $data;
    }

}
```

Généralement, on va **injecter** un service nommé `ProcessorInterface` dans notre processeur, permettant de poursuivre le traitement produit par API Platform (sauvegarde en base de données). Néanmoins, comme quand nous définissons nos propres services avec des interfaces, il faut préciser à notre application quel est le service concret qu'on souhaite utiliser pour cette interface (API Platform n'en enregistre pas par défaut). 

Comme il existe différents services que nous pouvons utiliser pour cette interface (un pour la sauvegarde, un pour la suppression...) il ne faut pas fixer ce service de manière globale dans l'application. À la place, on va utiliser l'attribut `#[Autowire]` afin de préciser le service concret que l'on souhaite utiliser.

```php
#src/State/MonStateProcessor.php
class MonStateProcessor implements ProcessorInterface {

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor
        //Autres services....
    )
    {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        //Modification des données de $data
        $data->setPropriete(...);
        //Sauvegarde en base
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

}
```

Ensuite, il ne reste plus qu'à préciser le **state processor** que l'on souhaite utiliser sur telle ou telle opération !

```php
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: MonStateProcessor::class),
    ]
)]
class Etudiant {
    private ?int $id = null;

    private ?string $nom = null;

    private ?string $prenom = null;

    public ?Groupe $groupe = null;
}
```

Pour les providers, l'interface à implémenter est `StateProvider` (la commande `make:state-provider`) et le paramètre à préciser dans l'opération : `provider`. Nous n'aurons malheureusement pas l'occasion de traiter un cas de **state provider** dans ce TD, mais c'est globalement la même chose (on utilise un service pour récupérer l'information puis on applique nos modifications).

<div class="exercise">

1. Supprimez tous les utilisateurs de votre base de données.

2. Modifiez le fichier `security.yaml` afin que Symfony utilise votre entité `Utilisateur` comme classe pour les utilisateurs de l'application.

   Modifiez aussi le fournisseur de sécurité
   ```diff
    security:
        firewalls:
            main:
   -            provider: users_in_memory
   +            provider: app_user_provider
   ```

3. Dans votre classe `Utilisateur`, décommentez la propriété `password`, ses getters/setters ainsi que la déclaration d'implémentation de l'interface `PasswordAuthenticatedUserInterface`. Faites en sorte qu'il soit impossible de lire et d'écrire cette propriété (au niveau de l'API). Il faut aussi décommenter dans `UtilisateurRepository` la déclaration de l'implémentation de l'interface `PasswordUpgraderInterface` ainsi que la méthode `upgradePassword`.

4. Ajoutez une propriété `$plainPassword` de type `?string` à la classe `Utilisateur` (ainsi que ses getters/setters). Pour le `getter`, précisez bien le type de retour `?string` (pour autoriser les valeurs nulles). Cet attribut ne doit pas être stocké dans la base ! Reprenez les assertions que vous utilisiez dans la classe `UtilisateurType` du projet précédent pour les appliquer sur cette propriété (sous forme d'attributs). N'oubliez pas d'importer les classes correspondantes. Aussi, en utilisant un autre attribut (provenant d'API Platform), faites en sorte que cette propriété ne puisse jamais être lue par les utilisateurs (jamais affichée/normalisée quand on renvoie une ressource type utilisateur).

5. Modifiez la méthode `eraseCredentials` afin que celle-ci mette `plainPassword` à **null**. En effet, par mesure de sécurité, comme cette propriété est stockée dans la classe utilisateur et non pas dans un formulaire, elle va être enregistré dans la session. Il faut donc posséder une méthode afin de "vider" cette information sensible après l'avoir utilisé.

6. Créez un **state processor** nommé `UtilisateurProcessor` qui devra hacher le mot de passe de l'utilisateur (`plainPassword`), puis l'affecter à `password`. Il faut ensuite utiliser `eraseCredentials` afin de supprimer les informations sensibles. Enfin, il faut reprendre le traitement normal d'API Platform pour sauvegarder l'entité en base. 

    * Vous pouvez réutiliser le bout de code (voir la méthode complète) définie dans la classe `UtilisateurManager` du projet précédent. Il vous faudra donc aussi injecter et utiliser le service `UserPasswordHasherInterface`.

    * **Attention** : lorsque vous utilisez la commande `make:state-processor`, le type de retour de la fonction `process` est `void`. Changez plutôt cela en `mixed`.

7. Utilisez votre `UtilisateurProcessor` comme processeur de l'opération `POST` sur l'entité `Utilisateur`.

8. Videz le cache.

9. Synchronisez vos changements avec la base de données.

10. Créez un utilisateur, depuis Postman. Il faudra bien préciser le `plainPassword` cette fois. Allez vérifier ensuite, dans votre base, que le mot de passe est bien haché.

11. Modifiez votre `UtilisateurProcessor` de manière à ne pas tenter de hacher le mot de passe s'il n'est pas transmis (s'il est **null**, donc). Cela va nous permettre d'utiliser le même processeur pour la création et la mise à jour (mais on aurait aussi pu en faire deux distincts). Lors de la mise à jour du profil, un utilisateur ne souhaite pas forcément modifier son mot de passe, mais s'il le fait, il faut bien le ré-hacher. Affectez donc aussi `UtilisateurProcessor` comme processeur de l'opération `PATCH`.

12. Videz le cache puis vérifiez que la mise à jour de l'utilisateur fonctionne bien quand on donne un nouveau mot de passe, qui est donc re-haché.

</div>

### Groupes de validation

Actuellement, si on essaye de faire un `PATCH` sur l'utilisateur, on nous obligera toujours à rentrer le mot de passe, car nous utilisons les attributs `NotBlank` et `NotNull`. Or, dans un `PATCH`, on doit pouvoir mettre à jour seulement les attributs que l'on souhaite. 

Nous allons utiliser le mécanisme appelé **groupes de validation** que nous avons vu lors du TD3 afin d'activer certaines contraintes seulement dans certains contextes.

Pour rappel, afin de préciser les **groupes** dans lesquels un attribut s'applique, on utilise le paramètre `groups`. Ensuite, au niveau de l'opération, on utilise le paramètre `validationContext` pour préciser les groupes de validation actifs lors de cette opération. Il faut penser à préciser le groupe `Default` afin que les attributs n'ayant pas précisé de groupe soient activés !

```php
#[ApiResource(
    operations: [
        new Post(validationContext: ["groups" => ["Default", "validation:exemple:create"]]),
        new Patch(validationContext: ["groups" => ["Default", "validation:exemple:update"]]),
    ],
)]
class Exemple {
    #[Assert\NotBlank(groups: ["validation:exemple:create"])]
    #[Assert\NotNull(groups: ["validation:exemple:create"])]
    private ?string $propriete;
}
```

Dans l'exemple ci-dessus, on oblige `propriete` à être précisé seulement lors de la création de l'entité (POST). Lors de la mise à jour, si la propriété n'est pas précisée, cela n'engendre pas d'erreur (mais on peut quand même l'ajouter au payload, ce n'est juste pas obligatoire).

Attention, ces groupes sont différents de ceux utilisés dans `normalizationContext` et `denormalizationContext`. Par convention, nous les nommons de la même manière, mais leur usage est tout à fait différent.

<div class="exercise">

1. En vous aidant de deux groupes de validation : `validation:utilisateur:create` et `validation:utilisateur:update`, faites en sorte qu'il soit obligatoire de préciser `plainPassword` seulement lors de la requête `POST`.

2. Videz le cache puis vérifiez que vous pouvez mettre à jour l'utilisateur sans préciser le mot de passe.

3. Utilisez vos groupes de validation sur les attributs `Assert\NotBlank` et `Assert\NotNull` des autres propriétés (login et adresse email) afin qu'ils soient obligatoires lors de la création, mais pas lors de la mise à jour (en fait, c'est le cas par défaut lors d'un PATCH, car les valeurs de ces attributs existent déjà dans les données de l'utilisateur et ne sont pas nulles, mais préciser ces groupes permet plus de clarté). Videz le cache.

</div>

### Contexte de dénormalisation

Maintenant, nous faisons face à un autre problème : on voudrait que certaines propriétés puissent être précisées lors de la création, mais pas lors de la mise à jour (qu'elles soient ignorées). Par exemple, on souhaite que le login d'un utilisateur ne puisse pas être mis à jour. Pour cela, on peut utiliser les groupes de **dénormalisation**.

À l'inverse des groupes de **normalisation** où nous avions précisé quels attributs afficher ou non lors d'une opération type `GET`, les groupes de **dénormalisation** permettent d'ignorer certaines propriétés.

Au niveau d'une propriété, il suffit de rajouter le `groupe` (dans l'annotation `#[Groups(...)]` que vous avez déjà utilisé pour la normalisation) pour lequel la propriété ne doit pas être ignoré. Par exemple : 

```php

//Si 'entite:create' ou 'entite:update' sont des groupes de dénormalisation actifs, la propriété n'est pas ignorée
#[Groups(['serialization:entite:read', 'serialization:entite:create', 'serialization:entite:update'])]
private ?string $prop1 = null;

//Si le groupe 'entite:create' est actif, la propriété n'est pas ignorée, mais si le groupe 'entite:update' est actif, elle est ignorée.
#[Groups(['serialization:entite:read', 'serialization:entite:create'])]
private ?string $prop2 = null;
```

Pour définir quel groupe activer sur telle ou telle méthode, on spécifie un paramètre `denormalizationContext` au niveau de l'opération souhaitée.

Par exemple :
```php
#[ApiResource(
    operations: [
        new Patch(denormalizationContext: ["groups" => ["serialization:entite:update"]]) ,
        new Post(denormalizationContext: ["groups" => ["serialization:entite:create"]]) ,
    ],
    normalizationContext: ["serialization:entite:read"]
)]
```

<div class="exercise">

1. En utilisant deux nouveaux groupes : `serialization:utilisateur:create` et `serialization:utilisateur:update`, faites en sorte que le **login** soit ignoré dans le cadre d'une requête `PATCH` s'il est envoyé dans le **payload** de la requête. Attention, il faut préciser les groupes de **dénormalisation** où les autres propriétés sont actives (`plainPassword`, `adresseEmail` doivent pouvoir être créés et mis à jour, `login` seulement créé). L'identifiant est un cas à part, car il n'est pas possible que l'utilisateur le créé ou le mette à jour de manière générale.

2. Videz le cache. Tentez de mettre à jour le login d'un utilisateur (avec `PATCH`). Vous devriez constater que le login n'a pas été mis à jour !

3. Tentez de créer un utilisateur sans spécifier le login, cela ne devrait pas fonctionner.

4. Tentez de mettre à jour seulement l'adresse mail d'un utilisateur, cela devrait fonctionner. 

</div>

### Prise en charge des JWT (connexion)

Maintenant, il ne nous reste plus qu'à mettre en place un système de connexion afin de délivrer un `JWT` qui permettra de nous identifier auprès du serveur, à chaque requête. Pour cela, nous allons utiliser le bundle `jwt-authentication-bundle`.

<!--
**Si vous êtes en local**, il faudra activer l'extension **sodium** dans votre fichier `php.ini`. Trouvez la ligne `;extension=sodium` dans votre fichier et enlevez `;` afin de décommenter l'extension.
-->

Afin d'activer l'authentification de nos utilisateurs, il nous faut une route dédiée. Pour la déclarer, nous pouvons utiliser le fichier `config/routes/api_platform.yaml` afin d'y ajouter une nouvelle route :

```yml
api_auth:
    path: /api/auth
    methods: ['POST']
```

Les utilisateurs enverront un `paylaod` contenant le login et le mot de passe sur cette route. L'application se chargera alors ensuite de nous envoyer notre **token** d'authentification que nous pourrons utiliser sur toutes les requêtes futures (qui ont besoin que l'utilisateur soit authentifié).

Pour prendre en charge tout cela, il faut simplement éditer le fichier `security.yaml` :

```yaml
security:
    ...
    firewalls:
        main:
            #L'application est sans état (pas de session)
            stateless: true
            #Le nom du provider que nous avons configuré plus tôt
            provider: app_user_provider
            json_login:
                #Le nom de la route d'authentification
                check_path: /api/auth
                #La propriété correspondant au login dans notre entité
                username_path: login
                #La propriété correspondant au mot de passe (haché) dans notre entité
                password_path: password
                #Les services qui gèrent le succès ou l'échec d'authentification
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
            jwt: ~
```

Attention, même si on précise `password` ici (correspondant au mot de passe haché), dans notre payload, il faudra préciser le mot de passe en clair (le bundle se chargera ensuite de le vérifier).

Pour se connecter, on devra donc envoyer un `payload` de la forme :

```json
{
    "login": "test",
    "password" : "test_mdp"
}
```

Attention, ici, on utilise le nom d'attribut `password` et pas `plainPassword`.

<div class="exercise">

1. Installez le package suivant (système d'authentification par JWT) :

    ```bash
    composer require "lexik/jwt-authentication-bundle"
    ```

2. Comme nous l'avons vu, la création et la vérification de `JWT` nécessite une paire de clés (publique/privée). Utilisez la commande suivante pour générer ces clés :

    ```bash
    php bin/console lexik:jwt:generate-keypair
    ```

    Elles sont placées dans le dossier `config/jwt`. Les fichiers de configuration se mettent à jour automatiquement (jetez un œil à `.env`, par exemple). Si vous êtes sur le serveur de l'IUT, il faut aussi protéger ce dossier pour qu'on ne puisse pas récupérer vos clés. Téléchargez ce fichier [htaccess]({{site.baseurl}}/assets/TD4/htaccess3), placez-le dans le dossier contenant les clés puis renommez-le en `.htaccess`.

3. Au niveau du fichier `config/routes/api_platform.yaml` ajoutez la route d'authentification.

4. Éditez le fichier `config/packages/security.yaml` afin de prendre en charge le système d'authentification.

5. Videz le cache.

6. En utilisant `Postman`, envoyez une requête `POST` à la route [https://localhost/the_feed_api/public/api/auth](https://localhost/the_feed_api/public/api/auth) en précisant un `payload` de connexion d'un utilisateur de votre application (avec un login / mot de passe valide). Vous devriez obtenir un `JWT` !

7. Sur le site [https://jwt.io/](https://jwt.io/), tentez de décoder ce jeton et observez l'information qu'il contient.
</div>

Dorénavant, afin que l'API puisse autoriser certaines requêtes qui nécessitent certains droits (par exemple, être connecté pour créer une publication), il faut envoyer le `JWT` dans l'en-tête `Authorization` de la requête, avec le format suivant : `Bearer jwt` (où `jwt` est le jwt obtenu suite à la connexion). Sur une application cliente (par exemple en `Vue.js`, `Angular` ou `React`) ce token serait alors stocké par le client et intégré aux requêtes nécessaires au besoin.

Ce système est assez standard et est utilisé dans de nombreuses APIs. Cependant... Nous allons tout casser ! En effet, renvoyer directement le `JWT` dans le corps de la réponse n'est pas toujours une très bonne pratique selon le contexte, notamment si votre **API** est utilisée au travers d'une application cliente sur navigateur comme nous allons le faire bientôt (dans les TDs de Vue.js). Il faut bien comprendre que quelqu'un qui possède votre **token** peut usurper votre identité jusqu'à son expiration. Il faut donc limiter au maximum les risques que cela se produise.

Cette fois, le problème vient du client, et plus précisément des applications qui tournent sur un navigateur web (avec un framework JavaScript par exemple, comme nous le ferons dans les prochains TDs). Le problème est de savoir où et comment stocker le JWT. Il y a plusieurs options :

* Dans une **variable** simple. Mais celle-ci sera supprimée si la page est rechargée, ce n'est donc pas idéal du tout (l'utilisateur devra se reconnecter...)

* Dans le `sessionStorage` qui est un objet qui permet d'enregistrer des couples clé/valeurs qui persistent si la page est rechargée (mais pas si le navigateur est redémarré).

* Dans le `localStorage` qui est similaire, mais dont les données sont aussi conservées si le navigateur est redémarré.

Jusqu'à il y a quelques années (et encore un peu aujourd'hui), beaucoup d'applications utilisaient la démarche suivante :

* L'API génère le `JWT` et l'envoi dans la réponse de la requête (ce que vous avez actuellement).

* Le client (navigateur web) stocke le `JWT` dans le `localStorage` et l'envoi à chaque requête où cela est nécessaire. De plus, comme on peut décoder le `JWT` (qui ne contient aucune information sensible), on peut connaître la date d'expiration du token et agir en conséquence.

Cependant, ce système était fortement vulnérable à un type d'attaque bien connue : l'attaque `XSS` (Cross-site Scripting). Cette attaque consiste à exploiter une vulnérabilité du site pour introduire un script malveillant qui sera exécuté par le navigateur d'une victime (ou pire, tous les utilisateurs). Cette attaque est par exemple possible si certaines informations fournies par l'utilisateur ne sont pas correctement échappées par l'application et traitées comme du code. 

Un attaquant pourrait par exemple envoyer des informations (par exemple, quand il rentre sa biographie sur son profil) contenant un bout de code JavaScript qui irait lire le `JWT` dans le `localStorage` et l'enverrait ailleurs, sur un espace accessible par l'attaquant. Toutes les personnes qui consulteraient la biographie en question exécuteraient alors (sans le savoir) ce script, et leur token serait donc envoyé à l'attaquant. Par la suite, ce dernier pourra donc usurper les identités des utilisateurs dont il a récupéré le token.

Le danger vient donc du fait que n'importe quel script `javascript` puisse accéder et lire dans `localStorage`. Si le contenu du `JWT` n'est pas sensible en soi, le `JWT` en lui-même est utilisé pour vous identifier. Il faut donc absolument éviter de le stocker dans un endroit potentiellement vulnérable.

Pour pallier ce problème, une alternative peut être choisie : utiliser des **cookies sécurisés**. Plus précisent des cookies en mode **secure** et **httpOnly** et en configurant adéquatement l'attribut **SameSite**. Ces options sont des indications utilisées par le **navigateur** pour stocker le cookie et savoir s'il faut ou non l'envoyer lors d'une requête. Regardons de plus près ces paramètres :

* **secure** : le cookie n'est envoyé que si la requête est chiffrée (**https**).
* **httpOnly** : le cookie n'est pas accessible dans le **javascript**. Il sera envoyé automatiquement à chaque requête vers le serveur, mais à aucun moment un script pourra lire ses données. Cela élimine le risque de se le faire voler en cas d'attaque XSS.
* L'attribut **SameSite** permet de définir si le cookie doit être envoyé ou non selon le site où est émise la requête vers le serveur. Cela permet notamment d'éviter les attaques **CSRF** dont nous allons parler juste après.

Une attaque **CSRF** (Cross-site Request Forgery) consiste à faire envoyer une requête vers un serveur (ici, une API) à l'utilisateur, mais depuis un autre site. L'idée est que le site en question contienne un script JavaScript qui émet la requête. Il faut ensuite faire en sorte que l'utilisateur que l'on souhaite piéger se rende sur ce site. La requête sera alors envoyée depuis le navigateur du client, comme si c'était lui qui était à l'initiative de la requête. Alors, même avec un cookie **secure** et en **httpOnly**, dans ce cas, l'attaquant pourra exécuter des actions sous notre identité. Notez cette fois que l'attaquant ne récupère pas notre `JWT`. Il utilise notre identité à travers notre navigateur.

Pour contrer cela, on paramètre l'attribut `Same-Site` du cookie, avec une des deux valeurs suivantes :

* `Same-Site: Strict` : le cookie n'est envoyé que si l'adresse du serveur de destination est la même que celle du site depuis lequel est émise la requête. Et cela pour toutes les opérations (**GET**, **POST**, etc...). Par exemple, si je me trouve sur le site `https://monsite.fr/home` que je possède un cookie lié au site `monsite.fr` et que je fais une requête vers `https://monsite.fr/api/utilisateurs`, le cookie sera envoyé. Par contre, si je me trouve sur le site `https://pas-un-site-suspect-du-tout.arnaque/home` et que je fais une requête vers `https://monsite.fr/api/utilisateurs`, le cookie ne sera pas envoyé.

* `Same-Site: Lax` : même chose sauf pour les opérations type `GET` si la requête mène vers le même site que celui associé au cookie. Par exemple, si on clique sur un lien depuis un site externe. Imaginons le scénario suivant : vous êtes connecté sur le site `https://monsite.fr/`. Sur la page d'accueil, vous vous attendez donc à avoir un rendu en mode "connecté", avec un bouton "profil", "déconnexion", etc. Avec le mode `Strict`, si vous cliquez sur le lien `https://monsite.fr/` depuis un autre site (par exemple, depuis un mail), la page apparaîtra comme si vous étiez déconnecté. Avec le mode `Lax`, le cookie sera envoyé et vous verrez la page comme si vous étiez connecté. Le mode `Lax` est le mode choisi par défaut.

* Il existe une troisième valeur possible `Same-Site: None` : autorise tout (le cookie est envoyé même si la requête est émise depuis un site différent). Cela ne nous intéresse donc pas.

Il reste important de noter que, même avec tout cela, on est toujours exposé "en partie" aux attaques `XSS` : si un script malveillant est présent sur le site et exécuté par l'utilisateur, l'attaquant ne pourra plus récupérer le token de l'utilisateur, mais pourra tout de même exécuter des actions sous son identité. On ne règle donc que la moitié du problème. Pour le reste, c'est au développeur de s'assurer de ne pas introduire ce genre de faille et de mettre en place des mécanismes de sécurité supplémentaires.

Tout cela semble compliqué et fastidieux à configurer ! Mais pas de panique, un simple paramétrage d'API Platform permet de générer et placer un cookie **secure**, **httpOnly** avec l'attribut **SameSite: Lax** dans la réponse de la requête d'authentification. Par défaut, le token sera alors supprimé du **corps** (`JSON`) de la réponse.

Pour cela, il faut modifier le fichier `config/packages/lexik_jwt_authentication.yaml` en ajoutant les sections suivantes :

```yaml
#config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:

    # Création automatique du cookie contenant le JWT
    set_cookies:
        BEARER: ~

    # Pour que l'application recherche le JWT dans les cookies
    token_extractors:
        cookie:
            enabled: true
            #Nom du cookie
            name: BEARER
```

Naturellement, le **cookie** stockant le token a la même date d'expiration que le `JWT`.

<div class="exercise">

1. Modifiez le fichier `lexik_jwt_authentication.yaml` afin de changer la gestion du `JWT` dans la réponse de la requête d’authentification.

2. Sur `Postman`, identifiez-vous de nouveau. Cette-fois, le corps de la réponse devrait être vide. Cliquez sur le bouton **Cookies** (sous le bouton **Send**). Vous devriez observer le `JWT` stocké sous le nom de `Bearer`.

</div>

### Ajouter des informations à la réponse après une connexion

Lorsqu'on se connecte, l'application cliente aimerait potentiellement connaître certaines informations :

* Notre login/identifiant, pour l'afficher sur l'interface, ou afficher une page type "mon profil".

* La date d'expiration du token, pour pouvoir mettre en place le rafraîchissement (section bonus du TD) et/ou déconnecter automatiquement l'utilisateur.

Comme nous n'envoyons plus le `JWT`, l'application cliente n'a donc plus accès à ces informations (qu'elle aurait pu obtenir en décodant le `JWT`). On peut alors envisager plusieurs solutions :

* Avoir une route dédiée qui donne les informations de l'utilisateur courant. L'idée est que le serveur décode les informations contenues dans le JWT et les renvoient en réponse. Ensuite, le client peut stocker ces informations dans des variables temporaires et utiliser la route en question à chaque rechargement.

* Directement renvoyer les informations utiles lors de la connexion et les stocker dans le `localStorage`. Il n'y a pas de problème ici, car on ne stocke pas d’informations sensibles, ou bien le `JWT`. Même en cas de faille `XSS`, les données récupérées ne seront pas intéressantes et exploitables.

Nous allons donc choisir la deuxième option et faire en sorte d'ajouter des informations concernant l'utilisateur dans le corps de la réponse. On aimerait ajouter son **identifiant**, son **login**, son **adresse email**, son statut **premium** et la **date d'expiration** du `JWT`. Pour cela, on peut créer une classe qui permettra de détecter l'événement d’authentification et d'ajouter des informations dans le corps de la réponse.

Voici cette classe :

```php
namespace App\EventListener;


use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class AuthenticationSuccessListener
{
    public function __construct(
        //Service permettant de décoder un JWT (entre autres)
        private JWTTokenManagerInterface $jwtManager
        )
    {}

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        //Insertion de données de l'utilisateur ici - À compléter
        $data['attribut'] = $user->getXXX();
        
        //Récupération des données contenues de le JWT - À compléter
        //On décode le jwt qui est déjà encodé, à ce stade, afin de récupérer les informations qui nous intéressent.
        $jwt = $this->jwtManager->parse($data['token']);
        $data['token_exp'] = $jwt['???'];

        $event->setData($data);
    }
}
```

Le but de la méthode `onAuthenticationSuccessResponse` est de capter l'événement déclenché quand l'utilisateur s'est correctement authentifié (bon login/mot de passe). L'événement `$event` nous permet d'utiliser diverses méthodes :

* `getData` : renvoie le tableau clé-valeur associatif qui sera converti en objet `JSON` dans le corps de la réponse. Il est donc possible d'y insérer des données comme dans un tableau associatif classique, en associant la donnée à un nom (une clé).

* `getUser` : renvoie l'objet `Utilisateur` correspondant au token. Il est donc possible de récupérer certaines valeurs de ses propriétés et de l'insérer dans `data`.

* `setData` permet de réaffecter le contenu du corps de la réponse.

Une fois la classe créée, il faut l'enregistrer en tant que receveuse de l'événement dans le fichier `config/services.yaml` :

```yml
# Dans config/services.yaml
services:

    ...

    # Nom personnalisé
    acme_api.event.authentication_success_listener:
        # Classe prenant en charge l'événement
        class: App\EventListener\AuthenticationSuccessListener
        # Précision de l’événement à capter
        tags:
            - { name: kernel.event_listener, event: lexik_jwt_authentication.on_authentication_success, method: onAuthenticationSuccessResponse }
```

<div class="exercise">

1. Créez un dossier `EventListener` dans `src` et ajoutez la classe `AuthenticationSuccessListener` telle que définie ci-dessus puis complétez-la afin d'enregistrer l'identifiant, le login, l'adresse email et le statut premium de l'utilisateur ainsi que la date d'expiration du `JWT` dans la réponse de la requête.

2. Éditez le fichier `config/services.yaml` pour enregistrer ce gestionnaire d'événement.

3. Videz le cache puis tentez de vous authentifier de nouveau. Vous devriez observer les nouvelles informations dans le corps de la réponse.

</div>

### Token de rafraîchissement

Plus tôt, nous avions évoqué le **système de rafraîchissement** pour permettre une plus grande sécurité. La logique est la suivante :

* Les JWT émis par l'application suite à l'authentification ont une faible durée de vie (3600 secondes par défaut). C'est une bonne chose que ce `JWT` d’authentification qui est transmis dans **beaucoup de requêtes** ne soit valide que sur une courte durée. Ainsi, on limite les risques en cas de vol ou autre. Cependant, en l'état, cela n'est pas très pratique, car cela voudrait dire que l'utilisateur est déconnecté de force dès que le token expire (et doit donc se reconnecter après 3600 secondes).

* Pour ne pas avoir à demander à l'utilisateur de se reconnecter dès que son JWT expire, lors de l'authentification, on transmet un **token de rafraîchissement** (en plus du JWT). Ce token a une durée de vie bien plus longue (par exemple, un mois) et est stocké en base. Dès que l'application cliente détecte que le JWT de l'utilisateur a expiré, elle fait appelle à une route spéciale permettant de rafraîchir notre JWT, en transmettant le token de rafraîchissement. Un nouveau JWT est transmis et la durée de vie du token de rafraîchissement est soit réinitialisé (durée de vie remise au maximum), ou alors, un nouveau token de rafraîchissement est émis. 

La route de rafraîchissement peut aussi être utilisée à chaque ouverture de l'application afin d'obtenir un nouveau `JWT`.

Contrairement au JWT d'authentification, le token de rafraîchissement peut être gardé de manière plus sécurisée et n'a pas besoin d'être transmis à chaque requête. Si un client n'est pas actif pendant une longue période (par exemple, une semaine) son JWT d'authentification (avec une courte durée de vie) aura expiré, mais il ne sera pas "déconnecté" pour autant, car si **token de rafraîchissement** n'aura pas expiré. En fait, quand il retournera sur l'application, son JWT d'authentification sera renouvelé grâce au token de rafraîchissement. Cependant, au bout d'une trop longue période d'inactivité (par exemple, plusieurs mois) l'utilisateur sera naturellement "déconnecté" à cause de l'expiration de son token de rafraîchissement.

Lorsque le token de rafraichissement est utilisé, on peut prolonger sa durée de vie ou bien en générer un nouveau.

Contrairement aux tokens d’authentification (à courte durée de vie), les tokens de rafraîchissement sont stockés dans la base de données, car ils ont une durée de vie potentiellement longue (plusieurs mois). Ainsi, si un utilisateur est compromis, il suffit de supprimer le token de rafraîchissement de la base.

La sécurisation de ce token est aussi primordiale, car il permet d'obtenir de nouveaux JWTs de manière illimitée (pendant toute la durée de vie du token). Dans un fonctionnement standard et basique, le client doit stocker ce token et l'envoyer dans le `payload` des différentes routes qui nécessitent d'utiliser ce token (rafraîchissement, invalidation...) en plus du `JWT` d'authentification (dans l'en-tête `Authorization` ou dans un cookie...). Mais comme nous l'avons vu, cela peut poser des problèmes de sécurité en cas d'attaque `XSS` (cela est même encore plus dangereux, car quelqu'un qui a accès au token de rafraîchissement peut générer autant de tokens d'authentification qu'il veut !)

Ici aussi, il semble donc être plus judicieux de s'orienter vers un stockage **dans un cookie sécurisé** (comme nous l'avons fait pour le `JWT` d'authentification), plutôt que de renvoyer directement le token dans le corps de la réponse. Avec ce mécanisme, il n'y a pas besoin d'inclure le token dans le payload des requêtes qui le nécessitent, l'API ira l'extraire du cookie. De plus, afin de ne pas envoyer le token de rafraîchissement à chaque requête qui nécessite d'être authentifié, on peut exploiter le paramètre `PATH` du cookie qui permet de spécifier un chemin qui doit exister dans l'URL ciblé afin que le cookie soit envoyé. On fera donc en sorte que ce cookie ne soit envoyé que sur les routes qui permettent de rafraichir ou d'invalider le token.

Pour mettre en place tout cela, nous allons nous servir du bundle `JWTRefreshTokenBundle`. La configuration est assez simple.

* Tout d'abord, on crée l'entité suivante, qui correspond à l'entité gérant les tokens de rafraîchissement (car ils sont stockés dans notre BDD) :

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
    //Pas besoin de propriété(s) particulière(s).
}
```

* Ensuite, on met en place la route liée au rafraîchissement, dans le fichier `config/routes/gesdinet_jwt_refresh_token.yaml` (qui sera générée une fois le bundle installé) :

```yaml
# Dans config/routes/gesdinet_jwt_refresh_token.yaml

# Route pour rafraîchir notre JWT (on limite en POST)
api_refresh_token:
    path: /api/token/refresh
    methods: ['POST']
```

* Après, on édite le fichier `security.yaml` afin de paramétrer notre système de rafraîchissement et nos deux nouvelles routes :

```yaml
# Dans config/packages/security.yaml
security:
    ...
    firewalls:
        ...
        main:
            ...
            entry_point: jwt
            refresh_jwt:
                check_path: api_refresh_token
                #Afin que le token ne soit envoyé que sur les sous-routes /api/token/...
                path: /api/token/
            ...
```

* Enfin, dans `config/packages`, on édite le fichier `gesdinet_jwt_refresh_token.yaml` pour configurer le bundle et indiquer quelle classe correspond au token de rafraîchissement et d'autres paramètres :

```yaml
# Dans config/packages/gesdinet_jwt_refresh_token.yaml
gesdinet_jwt_refresh_token:
    # Classe représentant le token de rafraîchissement (option déjà paramétrée, par défaut)
    refresh_token_class: App\Entity\RefreshToken
    # Pour que la durée de vie du token de rafraîchissement soit réinitialisée (à son maximum) après chaque utilisation
    ttl_update: true
    # Cette option permet de stocker le token de rafraîchissement dans un cookie (sécurisé, comme pour le JWT) au lieu de le renvoyer dans le corps de la réponse.
    cookie:
        enabled: true
```

Maintenant, quand vous vous authentifierez avec la route `/api/auth`, vous obtiendrez votre `token` (JWT) habituel ainsi qu'un token `refresh_token`. Celui-ci sera notamment utilisé lors de l'accès à la route suivante :

* `/api/token/refresh` (en POST) : rafraîchit votre JWT et réinitialise la durée de vie du token de rafraîchissement.
 
On a aussi accès à cette commande qui permet de supprimer tous les tokens de rafraîchissement ayant expiré :

```bash
php bin/console gesdinet:jwt:clear

```

Maintenant, à vous de jouer !

<div class="exercise">

1. Installez le bundle `JWTRefreshTokenBundle` :

    ```bash
    composer require gesdinet/jwt-refresh-token-bundle
    ```

    Quand on vous pose la question relative à l'utilisation d'une `recipe` répondez **yes**. Le mécanisme des `recipes` permet de configurer automatiquement certains aspects de l'application quand on ajoute une nouvelle librairie, un bundle... Par exemple, en créant de nouveaux fichiers ou en complétant certains fichiers de configuration. Cela est défini par le développeur du module installé.

2. Créez l'entité `RefreshToken` (vous pouvez directement copier le code présenté plus tôt).

3. Complétez les différents fichiers de configurations : `config/routes/gesdinet_jwt_refresh_token.yaml`, `config/packages/security.yaml`, `config/packages/gesdinet_jwt_refresh_token.yaml`.

4. Videz le cache.

5. Mettez à jour la structure de votre base de données en utilisant les commandes adéquates.

6. Testez votre nouveau système :

    * Authentifiez-vous et vérifiez que vous obtenez bien un `refresh_token` dans vos **cookies** en plus de votre token habituel.

    * Testez la route permettant de rafraîchir votre JWT. Dans vos cookies, vous pourrez alors constater que le cookie `BEARER` contenant le `JWT` d'authentification est mis à jour.

    * Vous pouvez aussi observer la table gérant les **tokens de rafraîchissement** dans votre base de données.

</div>

Dans l'absolu, nous pourrions aussi inclure (lors de l'authentification ou du rafraîchissement) des informations sur le token de rafraîchissement (notamment, sa date d'expiration). Cependant, dans l'absolu, le client n'en a pas vraiment besoin. S'il essaye de rafraîchir le `JWT` alors que le token de rafraîchissement a expiré, le serveur renverra une erreur `401` (Unauthorized) et le client sera alors au courant que le token a expiré. À l'inverse, pour le `JWT` d'authentification, il semble utile que le client connaisse la date d'expiration afin d'utiliser le token de rafraîchissement au moment opportun.

Si toutefois on a vraiment besoin de donner certaines informations du JWT au client (dans le corps de la réponse `JSON`), on peut alors adapter la classe `AuthenticationSuccessListener` et décoder le token de rafraîchissement qui est associé à la clé `refresh_token` dans `$data`.

### Suppression du cookie et invalidation du token (déconnexion)

Nous avons mis en place un système d'authentification grâce aux **JWT** et nous pouvons maintenant "connecter" un utilisateur en utilisant la route `/api/auth` et également obtenir un **token de rafraichissement** permettant de renouveler notre **JWT** grâce à la route `/api/token/refresh`. Cependant, il reste un problème à régler : comment "déconnecte" t-on un utilisateur quand on utilise ces tokens.

Ici, le terme de "déconnexion" (comme le terme de "connexion") est un abus de langage. On ne connecte pas vraiment l'utilisateur : on lui délivre des **tokens** qui lui permettront de s'authentifier à chaque requête. Il n'y a pas de système de session qui débute ou est terminée comme dans un site web classique. La question est donc de savoir comment faire pour que l'utilisateur puisse décider d'invalider son **JWT d'authentification** et son **token de rafraichissement avant la durée d'expiration prévue** et aussi comment faire en sorte que le **client** (navigateur ou autre) n'utilise plus ces tokens invalides.

On a donc deux étapes à gérer : **l'invalidation des tokens** (pour qu'il devienne inutilisable) et la suppression de ces derniers côté client, dans l'espace dans lequel ils sont stockés.

Pour la **première étape** :

* Concernant le **JWT d'authentification**, on peut utiliser un système de **liste de blocage** gérée en mémoire grâce au bundle `LexikJWTAuthenticationBundle`. Quand on décide **d'invalider le JWT**, il est alors répertorié dans un cache. Le token est supprimé de la liste quand il arrive à expiration. Ainsi, même si une requête est envoyée avec un `JWT` valide (signature correcte, non expiré), s'il est présent dans la liste de blocage, la requête échouera. Cela renforce également la sécurité globale de l'application : si le `JWT` est volé, si l'utilisateur décide de l'invalider (en se "déconnectant") alors le voleur ne pourra plus utiliser ce token.

Les données **mises en cache** n'ont généralement pas pour vocation d'êtres gardée pendant une longue durée (plusieurs jours, plusieurs mois, etc). C'est parfait, car nos `JWT` ont une durée de vie courte ! (généralement une heure). Ces tokens seront automatiquement détruits (et donc retiré de la liste de blocage) lors de leur expiration.

* Concernant le **token de rafraichissement**, un système d'invalidation est prévu par le bundle `JWTRefreshTokenBundle`. On n'utilise donc pas de liste de blocage avec cache (comme pour les **JWT** d'authentification) : le système d'invalidation et de blocage est directement géré grâce aux données stockées dans la base. Les tokens de rafraîchissement seront automatiquement détruits et retirés de la base de données lors de l'invalidation.

On a aussi accès à cette commande qui permet de révoquer un token précis (par exemple, si le compte de l'utilisateur est compromis) :

```bash
php bin/console gesdinet:jwt:revoke TOKEN
```

Dans tous les cas, il faut que la demande d'invalidation du token soit faite par le client auprès du serveur, via une **route**, par exemple.

Pour la **deuxième étape**, cela dépend de la façon dont sont envoyés les **tokens** au client, et comment ils sont stockés :

* S'ils sont envoyés dans le corps de la réponse d'authentification et stockés par le client (dans le `localStorage` par exemple), alors, quand l'utilisateur doit se déconnecter, après avoir demandé au serveur d'invalider les tokens, il suffit au code client de le supprimer de là où il est stocké. Nous avions vu que cette approche était plutôt **déconseillée**.

* S'ils sont stockés dans des **cookies httpOnly** alors le code client (du moins, le JavaScript dans un navigateur) n'y a pas accès ! Le serveur doit demander explicitement au client (navigateur) de supprimer les cookies contenant le **JWT d'authentification** et le **token de rafraîchissement** (en utilisant des en-têtes de réponse `Set-Cookie` avec une date d'expiration passée).

Nous sommes donc dans le second cas. La demande de suppression des cookies pourra être regroupée avec la route permettant d'invalider nos tokens. Ce sera en quelque sorte notre "route de déconnexion".

L'objectif est donc de mettre en place une route `/api/token/invalidate` qui permettra de bloquer l'utilisation du **JWT d'authentification** et du **token de rafraichissement** et permettra au serveur par la même occasion de demander la suppression des cookies contenant les tokens au client.

Voici les étapes pour mettre en place cette route :

1. Tout d'abord, on déclare la route dans le fichier `config/routes/gesdinet_jwt_refresh_token.yaml` :

   ```yaml
   # Dans config/routes/gesdinet_jwt_refresh_token.yaml
   api_token_invalidate:
       path: /api/token/invalidate
       methods: ['POST']
   ```

   On n'utilise pas de contrôleur, car le code gérant le blocage du `JWT` (et la suppression du cookie correspondant) est automatiquement géré par Symfony (et les bundle `LexikJWTAuthenticationBundle` et `JWTRefreshTokenBundle` qui gèrent les différents **tokens**).

2. On édite ensuite le fichier `config/packages/lexik_jwt_authentication.yaml` afin d'activer la liste de blocage des `JWT` :

   ```yaml
   # Dans config/packages/lexik_jwt_authentication.yaml
   lexik_jwt_authentication:
       ...
       blocklist_token:
           enabled: true
           cache: cache.app
   ```

   Cette liste sera utilisée pour vérifier le **JWT d'authentification** lors de chaque requête nécessitant d'être authentifié et sera mise à jour dès qu'on provoquera une déconnexion au niveau de Symfony.

3. Après, on édite le fichier `config/packages/gesdinet_jwt_refresh_token.yaml` :

    ```yaml
    # Dans config/packages/gesdinet_jwt_refresh_token.yaml
    gesdinet_jwt_refresh_token:
        ...
        # Le firewall (section définie dans security.yaml) paramétrant notre système de déconnexion / d'invalidation de token.
        logout_firewall: main
    ```

4. Enfin, on édite le fichier `config/packages/security.yaml` afin d'indiquer à Symfony quelle est la route utilisée pour se déconnecter. On indique alors aussi de faire une demande de suppression du cookie `BEARER` (qui contient le **JWT d'authentification**) dès que la déconnexion se produit :

   ```yaml
   # Dans config/packages/security.yaml
   security:
       ...
       firewalls:
           ...
           main:
               ...
               logout:
                   path: api_token_invalidate
                   delete_cookies: ['BEARER']
   ```

   Concernant le cookie contenant le **token de rafraichissement** (`refresh_token`), il n'y a pas besoin de le préciser. Le bundle correspondant le supprimera automatiquement lors de la déconnexion.

Avec tout cela, le système de déconnexion est complet !

<div class="exercise">

1. Éditez les quatre fichiers nécessaires pour mettre en place le système de déconnexion (ou plutôt, **invalidation** des **tokens** et suppression des cookies).

2. En utilisant `Postman`, authentifiez-vous (avec la route `/api/auth`) et vérifiez que vous possédez bien les cookies `BEARER` et `refresh_token` dans le menu **Cookies** (accessible sous le bouton **Send**). Allez voir le **token de rafraichissement** dans la base de données.

3. Retirez temporairement la ligne `delete_cookies: ['BEARER']` du fichier `config/packages/security.yaml` puis "déconnectez-vous" en utilisant la route `/api/token/invalidate` (avec la méthode `POST`). Vérifiez que vous possédez toujours le cookie `BEARER` (et donc votre **JWT d'authentification**) mais plus le `refresh_token`. Essayez de créer une publication. L'action devrait vous être refusée (car le **JWT d'authentification** a été invalidé).

4. Vérifiez que le **token de rafraichissement** a aussi bien disparu de la table dédiée dans la base de données.

5. Restaurez la ligne `delete_cookies: ['BEARER']` dans le fichier `config/packages/security.yaml`.

6. Supprimez à la main le cookie `BEARER` sur Postman. Authentifiez-vous de nouveau et observez le retour du cookie.

7. Enfin, "déconnectez-vous" de nouveau en utilisant la route `/api/token/invalidate`. Cette fois, tous les cookies devraient avoir disparu !

</div>

### Tokens dans le corps de la requête VS avec des cookies sécurisé

Nous venons de voir que, dans notre cas, utiliser un cookie sécurisé pour transporter les différents `JWTs` permettant à l'utilisateur de s'authentifier ou de rafraîchir son token semble être plus raisonnable. Cependant, cette solution n'est pas toujours adaptée. En fait, cela dépend des **clients** qui vont consommer votre API, en fonction de leur nature (navigateur, application mobile, etc) et de leur nombre.

Même si le fait d'utiliser un cookie présente de nombreux avantages au niveau de la sécurité, il peut poser des problèmes de gestion en fonction des clients qui souhaitent l'utiliser :

* Concernant les navigateurs web, les cookies `HttpOnly` ne peuvent pas être envoyés par des clients dont le nom de domaine de l'adresse web se trouve en dehors du nom de domaine de l'adresse web de l'API. Par exemple, si mon API se trouve sur `www.exemple.com/api` et mon client sur `www.exemple.com/client`, dans les deux cas, le nom de domaine est `exemple.com`. Si on envoie une requête de connexion depuis `www.exemple.com/client`, le cookie écrit par l'API sera lié au nom de domaine `exemple.com` et sera bien envoyé à chaque requête nécessitant une authentification émise par `www.exemple.com/client`. De plus, on peut faire en sorte que le cookie fonctionne aussi dans les sous-domaines de `exemple.com` en configurant l'attribut `DOMAIN` du cookie (pour qu'il fonctionne sur `app.exemple.com`, par exemple...). Cependant, si une requête de connexion est émise depuis le site `coucou.com` le cookie créé sera toujours lié à `exemple.com` (car c'est là que se trouve l'API) et il ne sera jamais envoyé lors des requêtes émises depuis `coucou.com`. Bref, si l'API doit être utilisées par plusieurs sites externes qui ne sont pas sur le même nom domaine qu'elle, ça ne sera pas possible.

* La gestion des cookies est essentiellement un mécanisme des clients de type **navigateur web**, et ne sont pas forcément gérés naturellement par les autres types de clients, comme les applications mobiles par exemple. Cependant, il existe des librairies pour gérer cela dans la plupart des cas. Et sinon, il suffit juste d'extraire les données de la réponse. De plus, ici, le paramétrage `HttpOnly`, `SameSite` et `Secure` n'ont pas vraiment d'importance, car ce sont des mécanismes liés au navigateur web. Une application mobile peut donc extraire les données des cookies envoyées par le serveur, les stocker et les envoyer au besoin à sa guise. De même, sur une application mobile, on ne retrouve pas les problèmes des failles XSS et CSRF (mais il peut y en avoir d'autres !). Bref, en dehors des navigateurs, la gestion des cookies n'est pas vraiment naturelle (mais pas impossible). On peut donc se dire que dans ce contexte, garder les tokens dans le corps de la réponse peut *éventuellement* être une meilleure solution (en tout cas, plus facile).

Bref, le choix de la méthode à privilégier dépend du contexte de votre projet : 

* Si l'API et le client sont **sur le même nom de domaine** (ou sous-domaines) et que l'on souhaite éventuellement l'utiliser avec un client mobile (ou autre) et qu'il n'y a pas d'autres clients web situés sur un autre nom de domaine, il vaut mieux privilégier l'option **cookie** qui présente moins de risques de sécurité (même si un peu plus dur à gérer sur un client qui n'est pas un navigateur, comme une application mobile ou autre). 

* Si l'API n'est **pas sur le même nom domaine que le client** (et que **plusieurs clients web** et éventuellement autres doivent utiliser l'API) : il faut plutôt choisir de renvoyer les tokens dans le corps de la réponse, le stocker, et, pour les sites web qui utilisent l'API, faire particulièrement attention à sécuriser le site contre les attaques `XSS`.

* On pourrait aussi éventuellement envisager d'avoir **les deux options en même temps** : lors de l'authentification, des cookies sont créés et les `JWTs` sont renvoyés dans le corps de la requête. Comme ça, on s'adapte en fonction du client. Les différents composants que nous venons de voir proposent de faire cela en activant une option pour conserver le `JWT` dans le corps de la réponse, même si les cookies sont utilisés :

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    ...
    remove_token_from_body_when_cookies_used: false
```

```yaml
# config/packages/gesdinet_jwt_refresh_token.yaml
gesdinet_jwt_refresh_token:
    ...
    cookie:
      ...
      remove_token_from_body: false
```

Dans notre cas, il n'y aura (bientôt) qu'un client web sur le même nom de domaine que notre API, donc **l'option cookie convient parfaitement**. Cependant, nous avons vu que dans tous les cas, une attaque `XSS` (pour une application sur un navigateur web) reste un danger (même si les conséquences sont potentiellement un peu moins grandes avec les cookies). C'est donc au client qui consomme l'API de s'assurer que ce genre de faille ne puisse pas être exploitée.

## Sécurité

Maintenant que nous pouvons nous authentifier, nous pouvons sécuriser l'accès à nos routes ! Nous allons donc voir comment :

* Autoriser l'accès à certaines routes seulement aux utilisateurs authentifiés.

* Vérifier que l'utilisateur qui modifie/supprime une ressource en est bien le propriétaire.

* Affecter automatiquement l'utilisateur effectuant la requête comme auteur d'une publication.

### Sécurisation de l'accès aux routes

On peut limiter l'accès à une méthode sur une ressource donnée en utilisant le paramètre `security` au niveau de la méthode en question, qui permet de vérifier les droits **avant** le traitement de la requête. On peut alors spécifier du **code** pour vérifier le droit d'accès à la route, en utilisant notamment la fonction `is_granted` pour vérifier que l'utilisateur possède un certain **rôle** (ou une permission, si on utilise les **voters**).

Par exemple :

```php
#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_USER')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
```

Ici, la route utilisant la méthode `POST` sur cette ressource est uniquement accessible aux utilisateurs avec le rôle `ROLE_USER` (donc, tous les utilisateurs authentifiés). La méthode `DELETE` elle par contre n'est accessible qu'aux utilisateurs ayant le rôle `ROLE_ADMIN`.

Il est également possible d'utiliser l'attribut `securityPostDenormalize` qui active la vérification **après** le traitement des données envoyées par la requête (après l'étape de dénormalisation) ce qui est notamment utile dans le cas d'opérations d'écritures. Dans le cas d'une mise à jour, on peut alors aussi accéder à "l'ancien" objet (avant modification).

On a aussi accès à certaines variables :

* `user` : représente l'instance de l'utilisateur connecté.

* `object` : représente l'instance de la ressource en cours d'accès ou de modification (on a donc accès à ses méthodes). Attention toutefois, si on utilise la méthode `POST`, l'objet n'existe pas encore ! Pour y avoir accès, il ne faut alors pas passer par `securty` (qui vérifie les droits avant), mais plutôt par `securityPostDenormalize`.

* `previous_object` : uniquement accessible quand on utilise `securityPostDenormalize`. Représente l'instance de la ressource avant d'être modifiée (dans le cas d'opération `PUT` ou `PATCH` par exemple).

* `request` : contient les données de la requête.

Si l'objet ciblé a un lien avec l'utilisateur, il est alors possible de comparer un attribut de l'objet avec l'utilisateur, par exemple.

```php
#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_USER') and user.age > 13"),
        new Delete(security: "is_granted('ROLE_USER') and object.getOwner() == user"),
        new Get(security: "!object.isPrivate() or (is_granted('ROLE_USER') and object.getOwner() == user) or is_granted('ROLE_ADMIN') "),
        new Patch(security:"is_granted('ROLE_ADMIN')", securityPostDenormalize: "previous_object.getOwner() == user and (object.getOwner() == user or !object.isPrivate())"),
    ]
)]
```

Dans cet exemple :

* L'utilisateur peut créer un objet avec `POST` s'il est connecté et a plus de 13 ans.

* L'utilisateur peut supprimer l'objet s'il est connecté et qu'il en est le propriétaire

* L'utilisateur ne peut consulter l'objet que si celui n'est pas privé, ou s'il en est le propriétaire, ou s'il est admin.

* L'utilisateur peut mettre à jour l'objet seulement s'il est admin, et il devait être propriétaire de l'objet avant modification (mais il a pu éventuellement pu effectuer un changement de propriétaire). Cependant, le changement de propriétaire n'est possible que si l'objet n'est pas privé.

Le paramètre `securityMessage` permet de customiser le message d'erreur lié au paramètre `security` et `securityPostDenormalizeMessage ` celui relatif au paramètre `securityPostDenormalize`.

```php
new Delete(security: "is_granted('ROLE_USER') and object.getOwner() == user", securityMessage: "...")
```

<div class="exercise">

1. Faites en sorte que seuls les utilisateurs authentifiés puissent créer des publications.

2. Faites en sorte que seul le propriétaire d'une publication puisse la supprimer.

3. Faites en sorte que seul l'utilisateur concerné puisse modifier ou supprimer son compte.

4. Videz le cache.

5. Sur `Postman`, supprimez le cookie `BEARER` contenant le `JWT` si celui-ci est toujours présent (bouton `Cookies` puis la croix à côté du cookie, pour le supprimer).

6. Tentez d'accéder à une des routes sécurisées. Vous obtenez normalement un message d'erreur.

7. Reconnectez-vous (afin d'obtenir de nouveau le cookie contenant le `JWT`) puis réessayez de soumettre la requête de création d'une publication. Cela devrait fonctionner !

8. Essayer de supprimer une publication qui ne vous appartient pas et un compte qui ne vous appartient pas (cela ne doit pas fonctionner). Et vérifiez qu'à l'inverse, vous pouvez effectivement supprimer vos propres publications (celles du compte auquel vous êtes connectés), et que vous pouvez aussi modifier vos propres informations...

</div>

Il est aussi possible d'utiliser les paramètres `security` et `securityPostDenormalize` dans un attribut `#[ApiProperty]` au niveau de chaque propriété de l'entité afin de contrôler les droits de lecture et d'écriture par propriété :

```php
#[ApiResource(
    operations: [
        new Get()
        new GetCollection()
        new Post(security: "is_granted('ROLE_JOURNALISTE')"),
        new Patch(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getOwner() == user)")
    ]
)]
class Article {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    private ?string $contenu = null;

    #[ORM\Column]
    #[ApiProperty(securityPostDenormalize: "is_granted('ROLE_ADMIN')")]
    private ?bool $desactive = false;
}
```

Dans cet exemple :

* Tout le monde peut récupérer la liste des publications ou les détails d'un article, mais seuls les utilisateurs connectés pourront en voir le contenu.
* Seul un journaliste peut publier un article, mais seul un administrateur peut écrire la propriété "desactive".
* Seul un administrateur ou le propriétaire d'un article peut modifier l'article, mais seul un administrateur peut modifier la propriété "desactive".

Attention, dans cet exemple, il faut que `$desactive` ait une valeur par défaut ou puisse être null en base de données, car un journaliste non-administrateur ne pourra pas l'écrire dans le payload d'une requête `POST`.

Ici aussi, bien que pratique, cette fonctionnalité est limité, car elle ne permet pas de différencier l'opération (au niveau de la propriété). Pour gérer des conditions plus complexes, on préférera utiliser diverses techniques comme :

* [Ajouter dynamiquement un groupe de sérialisation](https://api-platform.com/docs/core/serialization/#changing-the-serialization-context-dynamically) selon les permissions de l'utilisateur connecté.
* Utiliser une classe pour [calculer dynamiquement les groupes de sérialisation activés](https://api-platform.com/docs/core/serialization/#changing-the-serialization-context-on-a-per-item-basis-for-symfony)
* Utiliser une classe pour [calculer dynamiquement les groupes de validation activés](https://api-platform.com/docs/core/serialization/#changing-the-serialization-context-on-a-per-item-basis-for-symfony) (comme vous le ferez éventuellement dans la section bonus...).

### Affectation automatique de l'auteur d'une publication

Enfin, avec ce nouveau système d'authentification, nous pouvons automatiquement affecter l'utilisateur qui créé une publication comme auteur de celle-ci et de ne plus avoir à spécifier l'`IRI` (ce qui posait aussi un problème de sécurité, car on peut ainsi affecter un autre utilisateur à une publication !).

Pour cela, nous allons réutiliser le mécanisme des `processeurs` que nous avions vu plus tôt dans le cadre du hachage du mot de passe. Il vous suffit de créer un `processeur` affecté à la création d'une publication !

En plus du même `ProcessorInterface` que vous avez utilisé auparavant (pour sauvegarder), vous aurez besoin du service `Security` vous permettant de récupérer l'utilisateur courant avec `getUser`.

<div class="exercise">

1. Créez le **state processor** `PublicationProcessor`. Injectez les services qu'il faut et complétez la classe afin d'affecter l'utilisateur comme auteur de la publication traitée. Après avoir affecté l'utilisateur à la publication, il faudra poursuivre le traitement "normal" en utilisant le processeur d'API Platform. Attention à bien modifier le type de retour fonction `process` en `mixed` si vous utilisez la commande dédiée pour créer la classe.

2. Au niveau de la classe `Publication`, spécifiez votre nouveau processeur au niveau de la méthode `POST`.

3. Toujours dans la même classe, servez-vous de l'annotation `#[ApiProperty]` pour interdire l'écriture de l'auteur (vu qu'il est affecté automatiquement). Retirez également les attributs `NotBlank` et `NotNull` que vous aviez sans doute placé précédemment sur cette propriété.

4. Videz le cache puis connectez-vous (si ce n'est pas déjà fait, c'est-à-dire si vous ne possédez pas le cookie **BEARER** contenant le `JWT`) et tentez de créer une nouvelle publication. Le JWT sera automatiquement envoyé au serveur avec la requête, dans un cookie. Vérifiez alors que la publication est bien créée et que l'auteur a bien été affecté par rapport à l'utilisateur représenté par le `JWT` que vous utilisez.

</div>

### Autorisation d'envoi d'informations d'authentification

Afin que les navigateurs acceptent d'envoyer des informations d'authentification pour les requêtes sécurisées (donc, votre `JWT`), il faut modifier la configuration de l'API afin qu'elle ajoute un en-tête `Access-Control-Allow-Credentials` à **true** (sinon, le navigateur refusera de traiter la requête).

Pour cela, rien de plus simple : il suffit d'ajouter un paramètre dans le fichier `config/packages/nelmio_cors.yaml` :

```yaml
#config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        allow_credentials: true
        ...
    ...
```

<div class="exercise">

Éditez le fichier de configuration du bundle `nelmio_cors.yaml` afin d'ajouter le paramètre `allow_credentials`. Cela nous sera utile lorsque vous développerez le client en `Vue.js` qui se servira de cette API pour proposer un **front-end** à l'application.

</div>

## Bonus

Pour finir, quelques sections bonus afin d'améliorer encore plus votre API !

### Prise en compte du statut premium

Dans le projet précédent, nous avions fait en sorte d'avoir une limite de taille différente pour les messages selon le statut de l'utilisateur. Pour cela, nous avions utilisé des **groupes de validation** calculés à partir de l'état de l'utilisateur (`publication:write:normal` et `publication:write:premium`). Mais ici, comment faire ? Nous ne pouvons pas coder dans le paramètre contrôlant les groupes de validation actifs dans telle ou telle opération.

En fait, à la place d'avoir un paramètre sous la forme d'une chaîne de caractère, nous pouvons préciser une classe spéciale qui se chargera de générer le tableau contenant la liste des groupes nécessaires. Dans cette classe, nous pouvons injecter des services et donc accéder à l'utilisateur.

```php
#src/Validator/ExempleGroupGenerator.php
use ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface;
class ExempleGroupGenerator implements ValidationGroupsGeneratorInterface
{

    public function __construct(
        /* Injection de dépendances */
    )
    {
    }

    public function __invoke(object $object): array
    {
        //$object correspond à l'entité (Publication, Utilisateur...)
        //On peut vérifier que l'objet traité est bien du type attendu...
        assert($object instanceof Exemple);

        //On décide du (ou des) groupe(s) à ajouter...
        $group = "...";

        //On retourne un tableau avec le groupe "Default" et le (ou les) groupe(s) ajoutés
        return ['Default', $group];
    }
}
```

```php
#[ApiResource(
    operations: [
        new Post(validationContext: ["groups" => ExempleGroupGenerator::class]),
    ],
)]
```

<div class="exercise">

1. Modifiez les contraintes de votre entité `Publication` afin que le message puisse contenir jusqu'à 200 caractères si un des groupes de validation activé est `validation:publication:write:premium` et jusqu'à 50 caractères si un des groupes activés est `validation:publication:write:normal` (en récupérant le code correspondant dans le TD précédent...).

2. Créez un dossier `Validator` dans `src` puis à l'intérieur une classe `PublicationWriteGroupGenerator` qui permettra de choisir la bonne liste de groupes à partir du statut de l'utilisateur. Vous aurez encore une fois besoin du service `Security` pour obtenir l'utilisateur courant.

3. Modifiez l'opération `POST` au niveau de l'entité `Publication` afin d'utiliser votre nouveau générateur de groupes, pour la validation.

4. Dans votre base de données, rendez un utilisateur premium (en changeant manuellement la propriété). Sinon, si vous aviez fait la section à propos des commandes lors du dernier, vous pouvez les importer et les utiliser!

5. Videz le cache. Sur Postman, utilisez le JWT d'un compte non premium et vérifiez que l'erreur apparaît bien si vous faites un message dépassant 50 caractères. Vérifiez également que l'erreur n’apparaît pas si vous faites la même chose sur un compte premium (mais que dans ce cas, la limite à 200 est toujours présente).

</div>

### Utiliser les voters

Il est possible d'utiliser le système de **Voter** avec **API Platform** afin de gérer les permissions.

Dans le paramètre `security` de chaque opération, il suffit de préciser la permission dans la fonction `is_granted` et l'objet `object` (si besoin de vérifier la permission par rapport à un objet précis, comme le propriétaire, etc.) :

```php
#[ApiResource(
    operations: [
        new Post(security: "is_granted('PERMISSION', object)"),
    ]
)]
```

<div class="exercise">

1. Importez la hiérarchie de rôles que vous aviez mis en place dans `security.yaml` du projet précédent (avec `ROLE_ADMIN`).

1. Importez le voter `PublicationVoter` depuis le projet précédent (il faudra créer le chemin de répertoires `src/Security/Voter`).

2. Mettez à jour la permission de l'opération `DELETE` pour utiliser la permission définie dans votre **voter**.

3. Créez un nouveau **voter** nommé `UtilisateurVoter` et traitant la permission `UTILISATEUR_EDIT` (qui sera utilisé à la fois pour la mise à jour et la suppression). Cette permission est accordée à un utilisateur connecté qui est soit administrateur (`ROLE_ADMIN`) soit lui-même l'objet cible de cette permission.

4. Mettez à jour les permissions des opérations `PATCH` et `DELETE` pour utiliser votre nouvelle permission, issue de votre **voter**.

5. Videz le cache et vérifiez que les permissions fonctionnent bien (mêmes tests que dans l'exercice que nous avions effectué dans la partie "sécurité" de ce TD).

6. Si vous voulez, vous pouvez également ajouter une permission `PUBLICATION_CREATE` dans votre `PublicationVoter`. La permission est accordée si l'utilisateur est connecté (donc possède `ROLE_USER`). Cela permet de centraliser cette permission et la changer si besoin, dans le futur. Attention toutefois : dans une opération de création, la publication n'existe pas encore! Donc en l'état, notre méthode `support` ne fonctionnerait pas. Dans ce cas, il faut utiliser le paramètre `securityPostDenormalize` au lieu de `security` au niveau de l'opération.

</div>

### Vérification du mot de passe avant mise à jour

L'attribut `#[UserPassword]` (placé au-dessus d'une propriété) permet de vérifier que la chaîne de caractère (en clair) correspond au mot de passe actuel de l'utilisateur, lors de la phase de validation.

Nous souhaitons créer un système pour que quand un utilisateur souhaite mettre à jour son profil, il soit obligé de préciser une propriété `currentPlainPassword` dans le payload afin de valider son identité (et seulement pour la mise à jour !).

Normalement, vous avez toutes les connaissances nécessaires pour implémenter ce système !

<div class="exercise">

1. Créez une propriété `currentPlainPassword` dans la classe `Utilisateur` (propriété **non stockée en base**, comme `plainPassword`). Ajoutez aussi les getters/setters nécessaires.

2. En utilisant cette nouvelle propriété, l'attribut `#[UserPassword]` et les connaissances acquises dans ce TD, faites en sorte que lors d'une mise à jour (PATCH) l'utilisateur soit obligé de confirmer son mot de passe via la propriété `currentPlainPassword` (afin de valider son identité avant d'effectuer la mise à jour). 
   
   Vous n'avez pas besoin de quitter la classe `Utilisateur` ou créer de nouvelles classes. Attention, **cette propriété ne doit pas être utilisée lors de la création de l'entité, ou de sa lecture**. Vous pouvez configurer `#[UserPassword]` comme les autres assertions (en utilisant le paramètre `groups`, par exemple).

3. Dans la méthode `eraseCredentials` mettez aussi `currentPlainPassword` à **null**.

4. Videz le cache puis, sur Postman, vérifiez si tout fonctionne (tentez de mettre à jour votre profil sans puis avec `currentPlainPassword`).

</div>

## Conclusion

Nous avons terminé de construire notre `API` ! Elle est complète et prête à l'emploi pour être utilisée dans n'importe quelle application cliente (mobile, web, ...).

Vous avez pu constater la puissance de l'outil `API Platform`. Nous ne sommes pas beaucoup sortis des classes **entités** et la majeure partie de la logique métier de l'application est spécifiée grâce aux attributs dans ces classes. Pour les traitements particuliers, nous pouvons utiliser les **state processors** (et les **state provider**). S'il y a vraiment besoin, il y a toujours possibilité de définir des **controllers** avec des routes comme nous le faisions avant (par exemple, pour un **webhook**).

Dans la suite des TDs de web, vous allez apprendre à utiliser un framework JS client : `Vue.js`. Gardez donc cette API de côté, vous serez amenés à la réutiliser...
