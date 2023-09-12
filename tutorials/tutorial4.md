---
title: TD4 &ndash; Développement d'une API REST
subtitle: API, REST, API Platform
layout: tutorial
lang: fr
---

## Introduction

Dans ce TD, nous allons mettre en place une **API REST** pour l'application **The Feed** que nous avons développé lors des TDs précédemment. Nous n'allons pas directement poursuivre le code du site que vous avez développé (dans une API, il n'y a que le côté "back-end", hors pages de documentation) mais vous pourrez reprendre certains bouts de code qui pourront être réutilisés (notamment les assertions).

Nous avons déjà abordé la notion d'API l'an dernier. Nous allons tout de même faire une petite mise sur les **API** et plus particulièrement sur les **API REST**.

De nos jours, les architectures qui séparent activement la partie `back-end` (serveur, routage, services, accès aux données) de la partie `front-end` (ce qui est rendu niveau client, pages html, etc...) sont de plus en plus privilégiées. En effet, une telle séparation permet notamment d'utiliser un même serveur applicatif avec plusieurs technologies clientes (une application sur smartphone, une application Vue.js, React, etc...).

Dans ce fonctionnement, le serveur ne doit alors que renvoyer des données (généralement au format `JSON`, ou bien parfois `XML`) mais il ne se charge pas du rendu de la page. C'est alors les technologies clientes qui, une fois les données récupérées auprès du serveur, les utilisent pour mettre à jour leur interface. On appelle alors le programme côté back-end une `API` pour `Application Programming Interface`. Cela signifie en fait que ce programme est lui-même un `service` qui sert à exécuter des actions et récupérer de l'information, mais pas de document (pages web) à proprement parler.

Dans le monde des **API**, il existe un **style architectural** nommé `REST` pour **representational state transfer**. Dans cette architecture on parle de **ressources** pour faire référence aux différentes entités de notre service (publications, utilisateurs, commentaires...). Ces ressources doivent être désignées à travers des routes formulées de manière précise, par exemple :

* `/utilisateurs` : désigne tous les utilisateurs

* `/utilisateurs/1` : désigne l'utilisateur 1

* `/publications` : désigne toutes les publications

* `/publications/3` : désigne la troisième publication

* `/utilisateurs/1/publications` : désigne toutes les publications de l'utilisateur numéro 1

* `/utilisateurs/1/publications/5` : désigne la 5ème publication de l'utilisateur numéro 1

Grossièrement, on retrouve le schéma `/{ensemble}/{id}/...` etc...

Pour manipuler ces ressources, on utilise les méthodes `HTTP` suivantes :

* `GET` : Récupère les données de la ressource désignée par la route. On utilise généralement cette méthodes sous deux formes : soit pour récupérer un ensemble de ressources (liste) ou bien pour récupérer une ressource précise.

* `POST` : Crée une ressource dans l'ensemble désignée par la route avec les informations fournies dans le corps de la requête.

* `PUT` : Remplace complétement la ressource désignée par la route avec les informations fournies dans le corps de la requête (tous les attributs nécessaires doivent donc être spécifiés, comme pour `POST`). On écrase l'ancienne ressource avec la nouvelle.

* `PATCH` : Met à jour partiellement les données de la ressource désignée par la route avec les informations fournies dans le corps de la requête (seul les attributs qui ont besoin d'être mis à jour doivent être spécifiés).

* `DELETE` : Supprime la ressource désignée par la route.

Donc, par exemple, on pourrait avoir :

* `GET /publications` : Renvoie toutes les publications.

* `GET /publication/1` : Renvoie les informations de la publication ayant pour identifiant 1.

* `POST /publication` : Créé une nouvelle publication (et renvoie ses informations).

* `DELETE /utilisateurs/3` : Supprime l'utilisateur numéro 3.

Bien entendu, pour une ressource donnée, le développeur n'est pas obligé d'implémenter toutes les méthodes. Parfois, il n'est pas souhaitable que toutes les opérations soient disponibles pour agir sur une ressource.

Dans les requêtes de type **création / modification** (`POST`, `PUT`, `PATCH`), le client envoie des données dans le corps de la requête, généralement sous le même format que les réponses de l'API (donc, généralement, du `JSON`). Ce corps de données est appelé `payload`.

En plus de cela, une `API REST` doit être **sans état** (stateless), c'est-à-dire que le serveur **ne doit pas stocker d'informations sur l'état du client**. Donc, par exemple, l'utilisation d'une session est interdite. À la place, le client peut stocker des données sous la forme de `token` qui peuvent être lus et vérifiés par le serveur. C'est notamment grâce à ce système qu'on pourra authentifier nos utilisateurs et ajouter de la sécurité sur certaines routes.

## Mise en place avec Symfony

Pour développer le plus simplement possible notre **API**, nous allons utiliser un outil appelé **API Platform** exploitable au travers de **Symfony**.

Cet outil est assez puissant, car il permet de centraliser une grande partie de la logique de l'API au niveau des classes **entités**. Il y a peu de code à écrire dans d'autres classes (sauf pour certaines opérations spécifiques plus complexes) et généralement pas du tout de contrôleur à mettre en place (sauf opérations très particulières, comme un webhook, par exemple pour Stripe.). À partir des données des entités et des différentes attributs, **API Platform** se charge de générer les routes et appliquer les différentes opérations de récupération, de sauvegarde, de vérification, etc... Vous n'aurez quasiment pas à manipuler de repositories par vous-même.

Vous allez vite vous rendre compte qu'il est possible de configurer beaucoup d'aspects de l'API au travers des **attributs** (comme nous le faisions pour les routes, par exemple). Bien sûr, nous n'allons pas explorer toutes les possibilités qu'offre l'outil aujourd'hui, mais vous pourrez pousser plus loin dans votre projet !

Comme expliqué plus tôt, nous allons créer un nouveau projet indépendamment de celui que vous avez déjà développé dans les trois premiers TDs. Il faut donc reprendre les étapes d'installation.

<div class="exercise">

1. Dans le répertoire où vous souhaitez placer le dossier du projet, exécutez la commande suivante :

    ```bash
    composer create-project symfony/skeleton:"6.3.*" the_feed_api
    ```

2. **Si vous utilisez le serveur de l'IUT** :

    * Téléchargez le [fichier d'accès au serveur]({{site.baseurl}}/assets/TD1/htaccess), renommez-le `.htaccess` et placez-le dans le sous-dossier `public`.

    * Téléchargez un [deuxième fichier d'accès]({{site.baseurl}}/assets/TD1/htaccess2), renommez-le `.htaccess` et placez-le à la racine de votre projet (protège contre la lecture de vos fichiers de configuration, contenant vos mots de passes et autres informations confidentielles...).

3. Installez maintenant la librairie spécifique à API Platform :

    ```bash
    composer require api
    ```

    Répondez `n` (no) à la question demandée.

4. Nous allons maintenant configurer la base de données dans le fichier `.env` :

    * Si vous êtes en local, configurez la base que vous voulez, mais ne réutilisez pas celle précédente (par exemple, créez une base `the_feed_api`).

    * Si vous souhaitez utiliser une des bases de données mises à disposition à l'IUT, nous allons éviter de réutiliser votre unique base MySQL, pour ne pas effacer le travail effectué sur le site. À la place, nous allons utiliser votre base de données **Postgres** :

    `DATABASE_URL=postgresql://username:password@ip:port/nom_base`

    À l'IUT, on a la configuration suivante :

    * `username` et `password` : votre login et mot de passe de l'IUT (pour les machines, l'intranet, etc.)

    * `ip` : 162.38.222.151

    * `port` : 5673

    * `nom_base` : votre login IUT.

    Assurez-vous au préalable que votre base est vide ! Vous pouvez vous y connecter et vérifier cela avec DBeaver, notamment (sauvegardez les données utiles si nécessaires, avant de nettoyer).

    **Attention**, si vous comptez déposer votre projet sur un repository git **public**, utilisez plutôt un fichier `.env.local` à la place, qui ne sera ignoré lors des commits (pour ne pas exposer vos mots de passe).

5. **Si vous utilisez une base en local**, exécutez la commande suivante afin de créer la base :

    ```bash
    php bin/console doctrine:database:create
    ```

6. En vous rendant dans le dossier du projet, videz le cache :

    ```bash
    php bin/console cache:clear
    ```

7. Rendez-vous sur votre site à l'adresse : [https://webinfo.iutmontp.univ-montp2.fr/~votre_login/chemin_dossier_projet/public/api/](https://webinfo.iutmontp.univ-montp2.fr/~votre_login/chemin_dossier_projet/public/api/). Adaptez l'URL selon votre situation (avec le serveur local de `Symfony`, il ne faut pas mettre le "public" dans l'URL). Si cela fonctionne, tout est prêt. Vous devriez voir une page liée à **API Platform**.

</div>

Vous aurez peut-être remarqué que, même si nous nous trouvons dans l'environnement `dev`, il faut vider le cache. Cela est dû au fonctionnement interne d'API Platform et malheureusement, nous ne pouvons pas vraiment faire autrement. Après certaines modifications, il faudra donc vider le cache pour que les changements soient pris en compte.

Retenez donc bien cette commande :

```bash
php bin/console cache:clear
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

2. Créez une nouvelle entité `Publication` à l'aide de la commande `make:entity`. Une nouvelle question vous sera demandée lors de l'éxécution, par rapport à d'habitude. Symfony détecte que vous êtes en train d'utiliser `API Platform`, et vous demande si l'entité est une ressource de l'API ou non. il faudra répondre que oui, bien entendu.

    Pour les propriétés :

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

1. Rechargez la page web de présentation de l'API : [https://webinfo.iutmontp.univ-montp2.fr/~votre_login/chemin_dossier_projet/public/api/](https://webinfo.iutmontp.univ-montp2.fr/~votre_login/chemin_dossier_projet/public/api/) (à adapter selon votre situation).

2. Explorez la documentation des différentes méthodes proposées. Vous pouvez même envoyer des requêtes avec le bouton `Try it out`.

</div>

Pour aller déclencher notre `API`, nous n'allons pas directement utiliser l'interface web proposée par `API Platform`, mais plutôt un logiciel nommé `Postman` (nous l'avions rapidement abordé dans les TDs de complément web l'an dernier).

Ce logiciel va permettre de paramétrer et d'envoyer des requêtes de manière interactive et de visualiser le résultat très simplement. À l'IUT, il est installé sur vos machines, chez-vous, vous pouvez le télécharger [ici](https://www.postman.com/downloads/?utm_source=postman-home).

Nous allons donc tenter de manipuler notre ressource `publication` à l'aide de ce logiciel !

<div class="exercise">

1. Allumez **postman**. L'application vous propose de créer un compte, mais vous n'en avez pas besoin. Cliquez simplement sur "**Skip signing in and take me straight to the app**" tout en bas.

2. Sur l'interface, créez un nouvel onglet et paramétrez-le ainsi :

    * Dans la liste déroulante du choix des méthodes, sélectionnez `POST`.

    * Juste à côté, le champ permet de rentrer l'URL de la ressource à viser. Par exemple (à adapter) : [https://webinfo.iutmontp.univ-montp2.fr/~votre_login/chemin_dossier_projet/public/api/publications](https://webinfo.iutmontp.univ-montp2.fr/~votre_login/chemin_dossier_projet/public/api/publications) (pour Linux/macOS, il faut descendre un peu plus bas sur la page)

    * Comme mentionné plus tôt, avec les requêtes de **création / modification**, nous avons besoin d'un `payload` au format `JSON`. Pour le configurer, rendez-vous dans `Body` puis sélectionnez l'option `raw` et enfin, à droite, sélectionnez `JSON` dans la liste déroulante (à la place de `Text`).

    * Remplissez la zone de saisie des données avec le `payload` suivant :

    ```json
    {
        "message": "Hello world!",
        "datePublication": "2023-04-07T21:18:27.568Z"
    }
    ```

3. Cliquez sur "**Send**" et observez le résultat. La publication devrait vous être renvoyée (avec son identifiant) ! Ajoutez-en quelques autres.

4. Ouvrez un nouvel onglet et faites en sorte de récupérer la liste de toutes les publications (il n'y a pas de `payload` à mettre dans ce cas...).

5. Ouvrez un nouvel onglet et faites en sorte de récupérer une publication précise. Ici il n'y a encore pas de `payload`, l'identifiant doit être précisé au niveau de l'URL.

6. Ouvrez un nouvel onglet et faite en sorte de supprimer une publication. 
</div>

### Configuration plus poussée

À ce stade, vous avez pu tester diverses routes pour manipuler les publications, mais vous avez pu constater quelques soucis de logique. En effet, la **date** est un paramètre rentré par le client. Ce qui implique qu'il doit connaître précisément la date d'envoi, mais aussi qu'il peut la falsifier (par exemple, mettre 2050 pour être la publication la plus récente...). D'ailleurs, la date qui vous ai donné plus tôt dans le `payload` est antérieure à aujourd'hui ! 

Dans le premier TD, nous avons vu comment générer cette date automatiquement, mais il faut aussi pouvoir **empêcher** l'utilisateur de pouvoir l'envoyer dans le `payload`. Pour cela, nous pouvons nous aider de l'attribut `#[ApiProperty]`. Cet attribut se place au-dessus d'une propriété de la classe. Ses paramètres permettent de configurer la présentation de la propriété et ajouter certaines règles. Par exemple :

* `writable: true/false` : autorise ou interdit l'écriture de cette propriété lors de la création/modification (par défaut, autorisé si non précisé). Si la propriété est présente dans le payload, elle est ignorée.

* `readable: true/false` : renvoie ou non la propriété lorsqu'on lit la ressource (par défaut, autorisé).

* `required : true/false` : oblige ou non l'écriture de la propriété lors de la création/modification. On précise cette propriété dans le cas où la logique à appliquer est la même pour toutes les opérations (on verra que ce n'est pas toujours souhaitable). On préférera plutôt utiliser les assertions `Blank` ou `NotBlank` avec des **groupes de validation**.

* `description : ...` : permet de décrire le rôle de la propriété plus en détail (pour la documentation destinée aux personnes souhaitant utiliser l'API).

Si on prend l'exemple suivant :

```php
class Exemple {

    #[ApiProperty(readable : false, required: true, description: 'Description de la propriété...')]
    private ?string $propriete = null;

}
```

Quand je vais effectuer une requête POST/PUT/PATCH, je suis obligé de la préciser dans le payload. Mais elle n'apparaitra jamais quand je vais lire les données d'une entité `Exemple`. La description permet d'enrichir la page de documentation automatique générée par API Platform.

Dans l'annotation `#[ApiResource]` au-dessus de la classe, il est possible de rajouter un paramètre `order` pour spécifier comment sont ordonnés les résultats d'une requête renvoyant une collection de cette ressource. 

On le spécifie ainsi : `#[ApiResource(order : ["attribut1" => "ASC ou DESC", "attribut2" => "ASC OU DESC", ...])]`. 

On trie les résultats par rapport au premier attribut spécifié puis, en cas d'égalité, par rapport au second, et ainsi de suite (similaire au `ORDER BY` en SQL). Les valeurs `ASC` ou `DESC` permettent de spécifier le sens du tri (croissant ou décroissant).

```php
//Quand je récupère l'ensemble des entreprises, elles sont triées de celle possédant le plus gros CA à celle possédant le plus petit CA.
#[ApiResource](
    order : ["chiffreAfaire" => "DESC"]
)
class Entreprise {

    private ?string $nom = null;

    private ?float $chiffreAfaire = null;

}
```

Concernant la **génération automatique** d'une propriété (dans notre cas, la date) nous avons déjà vu cela dans le premier TD. Il vous suffit d'ajouter l'attribut `#[ORM\HasLifecycleCallbacks]` au niveau de la classe puis créer une méthode initialisant la date, annotée avec l'attribut `[ORM\PrePersist]`.

<div class="exercise">

1. Faites les modifications nécessaires au niveau de l'entité `Publication` afin qu'une date entrée dans le `payload` soit ignorée (qu'on ne puisse pas l'écrire) et que celle-ci soit plutôt générée automatiquement du côté du serveur. Pour ce dernier point, reprenez simplement le code correspondant depuis votre classe `Publication` développée dans les TDs précédents.

2. Reprenez les **assertions** des propriétés de votre ancienne classe `Publication` et appliquez-les sur celle-ci. Concernant l'assertion `Length` sur le message, gardez en une seule pour le moment et supprimez son groupe de validation (nous ajouterons le premium plus tard...). N'oubliez pas de faire l'import suivant :

    ```php
    use Symfony\Component\Validator\Constraints as Assert;
    ```

3. Faites en sorte que la collection de publications renvoyées soit triées de la plus récente à la plus ancienne.

4. Videz le cache.

5. Sur `Postman`, testez que tous les changements sont bien pris en compte. Il est important de noter qu'au niveau de la requête `POST`, même si la date est précisée, celle-ci doit être ignorée.

</div>

Maintenant, nous aimerions interdire l'utilisation de certaines méthodes. En effet, nous ne voulons pas que les publications soient modifiables. Il faut donc interdire les méthodes `PUT` et `PATCH` ou plutôt, autoriser seulement les autres méthodes. Pour cela, il suffit d'utiliser le paramètres `operations` au niveau de l'annotation `#[ApiResource]`. Ce paramètre est une **liste** des opérations permises, sous la forme d'objets (qu'on peut d'ailleurs configurer de manière ciblée).

Les opérations possibles sont :

* `GetCollection` : récupération d'un ensemble de ressources.

* `Get` : récupération d'une ressource ciblée.

* `Post`

* `Put`

* `Patch`

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

Ce système est nécessaire afin de limiter les données lues côté client et ainsi charger le contenu au fur et à mesure (imaginez si vous deviez charger tout **Twitter** à chaque accès !!!). Il est possible d'augmenter le nombre de ressources renvoyées par page ou bien simplement désactiver ce système (et donc tout renvoyer à chaque fois). Par convenance dans le cadre de l'application mobile que nous allons développer, nous allons donc désactiver ce système, mais retenez bien que dans un contexte réel, il faudrait le conserver et charger le contenu petit à petit, au fil du parcours de l'utilisateur.

Pour configurer tout cela, on créé et on édite le fichier `config/packages/api_platform.yaml` :

```yaml
# Dans config/packages/api_platform.yaml (à créer)
api_platform:
    defaults:
        pagination_items_per_page: 30 # Pour changer le nombre de ressources renvoyées si la pagination est activée
        pagination_enabled: true/false # Active ou désactive la pagination
```

<div class="exercise">

1. Créez le fichier `config/package/api_platform.yaml` et désactivez la pagination.

2. Empêchez l'utilisation des méthodes `PUT` et `PATCH` sur les publications.

3. Videz le cache.

4. En utilisant `Postman`, vérifiez qu'il n'est effectivement plus possible d'utiliser ces méthodes. Vérifiez aussi que les autres méthodes fonctionnement toujours.

</div>

Parfait, vous avez configuré votre première ressource ! Comme vous l'avez constaté, nous n'avons pas eu besoin de produire du code hors de la classe `Publication` pour faire tout cela.

## Les utilisateurs

Il est temps de nous attaquer à la création de nos **utilisateurs** qui vont, eux aussi, être des **ressources** de l'application.

Pour créer la ressource `Utilisateur`, nous n'allons pas nous embêter et reprendre la classe `Utilisateur` (et son repository) que vous avez déjà développé dans le projet précédent et l'adapter légèrement. En fait, pour spécifier que notre entité est une ressource de l'API, il suffit de rajouter l'attribut `ApiRessource` au-dessus de la classe.

Note à part : pour la méthode `PATCH`, le payload n'est pas au format `json`, mais au format `merge-patch+json`. C'est exactement la même chose, sauf que ce format indique à l'application qu'on souhaite mettre à jour seulement une partie de l'entité, avec les attributs spécifiés. Sur `Postman`, lorsque vous réaliserez une requête `PATCH`, il faudra préciser ce format dans votre requête en vous rendant dans la partie `Header` puis en modifiant la valeur de la clé `Content-Type` avec `application/merge-patch+json`.

<div class="exercise">

1. Importez la classe `Utilisateur` dans `src/Entity` ainsi que la classe `UtilisateurRepository` dans `src/Repository` depuis le projet précédent.

2. Pour le moment, supprimez la propriété `publications` et les méthodes qui lui sont liées (add, remove, get...). Nous ferons le lien entre publication et utilisateur plus tard.

3. Supprimez la propriété `nomPhotoProfil`, ses attributs et ses getters/setters. Nous ne gérerons pas l'upload de photo de profil dans notre API (cela est néanmoins possible !).

4. Concernant le **mot de passe**, nous gérerons ça plus tard, donc pour le moment, **commentez** simplement la propriété `password`, ses attributs ainsi que ses getters/setters.

5. Faites en sorte que la propriété premium puisse être lue, mais jamais écrite.

6. Faites en sorte que cette entité soit utilisée comme une ressource et interdisez l'utilisation de la méthode `PUT` (mise à jour partielle avec `PATCH` autorisée, mais pas de mise à jour complète avec `PUT`).

7. Videz le cache.

8. Synchronisez vos changements avec la base de données (souvenez vous des 2 commandes à utiliser...)

9. Rechargez la page listant les opérations de l'API et vérifiez que votre utilisateur apparait bien.

10. Testez de créer des utilisateurs (login/adresseEmail, pas de mot de passe pour le moment) et vérifiez que vos contraintes sont respectées (essayez de rentrer un login trop court ou trop long, une adresse email au mauvais format, etc...). Vérifiez aussi que, même si la propriété `premium` est précisé dans le payload, avec la valeur `true`, elle est ignorée (la valeur `premium` de l'utilisateur reste à `false`).

11. Testez la modification d'un utilisateur existant avec `PATCH`, en configurant la requête correctement, sur `Postman`. Là aussi, on doit pouvoir modifier le login et l'adresse email, mais toute modification sur le statut premium est ignorée.

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

    * La stratégie de suppression doit être `CASCADE` (si un utilisateur est supprimé, toutes ses publications sont supprimées...)

2. Activez le mode de chargement `EAGER` (eager loading) pour la récupération des données de l'auteur.

3. Ajoutez les attributs nécessaires pour "forcer" l'utilisateur à préciser l'auteur lorsqu'il crée une publication.

4. Dans votre base de données, supprimez toutes vos publications (un champ non null va être ajouté ce qui va causer des problèmes si on laisse les publications actuelles, sans auteur).

5. Mettez à jour votre base de données.

6. Videz le cache.

7. Sur `Postman`, vérifiez que vous obtenez un message d'erreur si vous tentez d'ajouter une publication sans préciser son auteur.

8. Maintenant, tentez d'ajouter une publication en précisant l'identifiant numérique (son id) de l'utilisateur pour la partie `auteur`. Analysez le message d'erreur.

9. La valeur à préciser pour faire référence à une autre ressource est appelé `IRI` (International Ressource Identifier) qui est une référence (un "lien") interne à l'application. Pour le trouver, récupérez (avec une requête `GET`) les détails d'un de vos utilisateurs. Il faut regarder au niveau de la propriété `@id`.

10. Une fois le mécanisme des `IRI` compris, retentez de créer une publication en affectant un utilisateur.

11. Ajoutez plusieurs publications liées à un utilisateur et vérifiez que la suppression de l'utilisateur entraîne la suppression des publications qui lui sont liées.

</div>

### Les groupes de sérialisation

Vous remarquerez que quand on affiche la liste des publications ou une publication précise, au niveau de l'auteur, seul son `IRI` est indiqué. Cela est gênant, car si on veut connaître les détails de l'auteur (par exemple son login) on est obligé de faire une requête supplémentaire.

Il serait donc plutôt préférable d'afficher les détails de l'utilisateur à la place. Nous pouvons faire cela grâce aux **groupes de sérialisation**.

Quand on manipule nos ressources, il peut exister **deux types de contextes** :

* La **normalisation** : quand on transforme un objet de l'application en `JSON` et qu'on le renvoie au client (requête de lecture, type GET).

* La **dénormalisation** : quand on charge un `payload` au format `JSON` et qu'on le transforme en objet de l'application (requêtes de création ou de mise à jour : POST/PUT/PATCH).

Dans les deux cas, il peut être possible de définir des **groupes** pour contrôler la présence d'une propriété :

* Dans le cas de la **normalisation**, il est donc possible de contrôler quelles propriétés sont **sérialisées**, c'est-à-dire, renvoyées au client sous le format `JSON`.

* Dans le cas de la **dénormalisation**, il est possible de contrôler les propriétés du payload à ignorer, dans tel ou tel contexte (par exemple, si on veut que le login soit précisé lors de la création d'un compte, mais il ne doit pas pouvoir changer lors d'une mise à jour...)

Présentement, nous allons d'abord nous intéresser à la **normalisation** pour qu'on puisse représenter quelques données de l'auteur d'une publication.

Un paramètre de l'annotation `#[ApiResource(...)` nommé `normalizationContext : ["groups" => ['nom_groupe1', ...]]` permet d'activer certains groupes pendant la phase de **normalisation**. Au niveau des propriétés, l'annotation `#[Groups(['nom_groupe1', 'nom_groupe2', ...])]` permet de faire en sorte qu'une propriété soit affichée ou non dans le document `JSON` selon le groupe activé.

Par exemple :

```php
#[ApiResource(
    ...
    normalizationContext: ["groups" => ["etudiant:read"]],
)]
class Etudiant {

    #[Groups(['etudiant:read'])]
    private ?int $id = null;

    #[Groups(['etudiant:read'])]
    private ?string $nom = null;

    #[Groups(['etudiant:read'])]
    private ?string $prenom = null;

    public ?Groupe $groupe = null;
}

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["groupe:read"]],
)]
class Groupe {

    #[Groups(['groupe:read'])]
    private ?int $id = null;

    #[Groups(['groupe:read'])]
    private ?string $nomGroupe = null;

    public iterable $etudiants;

}
```

Ici, quand on récupère les données d'un étudiant, on affiche seulement son id, son nom, son prénom, mais pas son groupe (même pas d'IRI).

Pour les groupes, on affiche tout sauf la liste des étudiants (qui aurait était une liste d'IRI aussi).

Pour qu'on obtienne aussi l'identifiant et le nom du groupe quand on lit les données d'un étudiant, il faut d'abord ajouter le groupe `utilisateur:read` à la propriété `groupe` et il faut ensuite ajouter le groupe `utilisateur:read` sur chaque propriété de la classe `Groupe` qu'on souhaite afficher quand on rend le groupe d'un utilisateur : 

```php
#[ApiResource(
    ...
    normalizationContext: ["groups" => ["etudiant:read"]],
)]
class Etudiant {

    #[Groups(['etudiant:read'])]
    private ?int $id = null;

    #[Groups(['etudiant:read'])]
    private ?string $nom = null;

    #[Groups(['etudiant:read'])]
    private ?string $prenom = null;

    #[Groups(['etudiant:read'])]
    public ?Groupe $groupe = null;
}

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["groupe:read"]],
)]
class Groupe {

    #[Groups(['groupe:read', 'etudiant:read'])]
    private ?int $id = null;

    #[Groups(['groupe:read', 'etudiant:read'])]
    private ?string $nomGroupe = null;

    public iterable $etudiants;

}
```

Si à l'inverse on avait voulu les détails de chaque étudiant d'un groupe (quand on lit le groupe) :

```php
#[ApiResource(
    ...
    normalizationContext: ["groups" => ["etudiant:read"]],
)]
class Etudiant {

    #[Groups(['etudiant:read', 'groupe:read'])]
    private ?int $id = null;

    #[Groups(['etudiant:read', 'groupe:read'])]
    private ?string $nom = null;

    #[Groups(['etudiant:read', 'groupe:read'])]
    private ?string $prenom = null;

    public ?Groupe $groupe = null;
}

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["groupe:read"]],
)]
class Groupe {

    #[Groups(['groupe:read'])]
    private ?int $id = null;

    #[Groups(['groupe:read'])]
    private ?string $nomGroupe = null;

    #[Groups(['groupe:read'])]
    public iterable $etudiants;

}
```

On aurait aussi pu préciser directement le groupe `groupe:read` dans l'attribut `normalizationContext` de la classe `Etudiant` :

```php
#[ApiResource(
    ...
    normalizationContext: ["groups" => ["etudiant:read", "groupe:read"]],
)]
class Etudiant {

    #[Groups(['etudiant:read'])]
    private ?int $id = null;

    #[Groups(['etudiant:read'])]
    private ?string $nom = null;

    #[Groups(['etudiant:read'])]
    private ?string $prenom = null;

    #[Groups(['etudiant:read'])]
    public ?Groupe $groupe = null;
}

#[ApiResource(
    ...
    normalizationContext: ["groups" => ["groupe:read"]],
)]
class Groupe {

    #[Groups(['groupe:read'])]
    private ?int $id = null;

    #[Groups(['groupe:read'])]
    private ?string $nomGroupe = null;

    public iterable $etudiants;

}
```

Bref, les groupes activés dans `normalizationContext` traversent les entités ! Il est aussi possible de définir plusieurs groupes (affichant plus ou moins de propriétés selon le contexte). On peut même créer une classe et une méthode permettant de coder un algo pour savoir quels groupes sont activés (par exemple, pour afficher plus d'informations à un utilisateur connecté...).

Il faut éviter dans se retrouver dans une situation de "cycle" (par exemple, un étudiant donne aussi les infos de son groupe, qui donne les infos sur ses étudiants, qui donne les infos de leur groupe, etc...)

Si vous avez bien compris ce mécanisme, à vous de jouer !

<div class="exercise">

1. En utilisant les groupes de sérialisation, faites en sorte que quand on lit une publication, on obtienne aussi les détails de l'utilisateur (id, login, adresse email, premium).

2. Videz le cache.

3. Testez en appelant la méthode `GET` générale, mais aussi en ciblant une publication en particulier.

</div>

Nous avons défini `normalizationContext` de manière globale (pour toutes les opérations de la ressource) mais il est aussi possible de le définir pour une opération précise (dans `new Get()`, `new Post()`, etc...)

### Publications d'un utilisateur

Comme nous l'avons vu en introduction de ce TD, avec l'architecture `REST`, il doit être possible d'accéder à la liste des publications d'un utilisateur précis en utilisant cette route :

`/utilisateurs/{id}/publications`

Par exemple : `/utilisateurs/2/publications` : les publications de l'utilisateur 2.

Pour cela, rien de plus simple : il suffit de configurer une **nouvelle opération** `GetCollection` avec un chemin custom (il est possible d'avoir plusieurs fois la même opération si les chemins sont différents). Il faut préciser un **template d'URL** et des **variables** afin d'aller chercher la ressource au bon endroit.

```php
#[ApiResource(
    operations: [
        ...,
        new GetCollection(
            //Template avec nom de variables intégrées entre accolades. Il est possible d'en indiquer plusieurs
            uriTemplate: '/chemin/{identifiant1}/ressource',
            uriVariables: [
                //On indique comment accèder à la sous-ressource (dont l'identifiant correspondant à celui passé dans la route)
                'identifiant1' => new Link(
                    //La propriété qui contient la ressource ciblée dans la classe cible
                    fromProperty: 'nom_propriete',
                    //La classe cible dont on veut récupérer une instance à partir de l'identifiant 
                    fromClass: Target::class
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

1. En ajoutant une nouvelle opération `GetCollection` dans `Publication`, faites en sorte d'ajouter une route qui permet d'obtenir la liste des publications d'un utilisateur précis.

2. Videz le cache.

3. Testez votre nouvelle route en essayant d'afficher la liste des publications d'un de vos utilisateurs.

</div>

## Authentification

Nous avons enfin pu relier nos publications à nos utilisateurs efficacement, mais il reste plusieurs problèmes :

* Un utilisateur qui poste une publication doit connaître son `IRI`.

* On peut affecter une publication à n'importe quel utilisateur !

* N'importe qui peut supprimer un utilisateur ou une de ses publications.

* N'importe qui peut modifier le profil d'un utilisateur

Pour régler cela, nous allons mettre en place un système d'authentification pour faire en sorte qu'une publication soit affectée à l'utilisateur authentifié qui la poste et nous rajouterons un système de sécurité pour empêcher la modification ou la suppression de ressources qui n'appartiennent pas à l'utilisateur qui émet la requête.

### Json Web Token

Comme nous l'avons mentionné au début de ce TD, l'architecture **REST** implique la notion de **stateless**, c'est-à-dire que le serveur ne garde aucune information sur l'utilisateur en mémoire vive, avec une session, etc... Mais alors, comment mettre en place un système d'authentification et faire comprendre au serveur que l'utilisateur est légitime ? Pour cela, nous allons utiliser le mécanisme des `JSON Web Tokens` souvent abrégé en `JWT`.

Un `JWT` est une chaîne de caractères appelée `token` encodée en `base64` qui contient de l'information. Une fois déchiffré, ce jeton se décompose en trois parties :

* Le `header` (au format `JSON`) qui contient des informations sur la nature du jeton (le type de jeton, le type d'algorithme utilisé)

* Le `payload` (au format `JSON`) qui contient de l'information utile (que l'on souhaite lire) placée dans ce jeton.

* La `signature` qui permet de vérifier l'authenticité du jeton.

Pour créer un `JWT`, on utilise une paire clé publique/clé privée. La **clé privée** sert à créer la `signature` du jeton à partir de la concaténation du `header` et du `payload`. Elle est conservée par l'émetteur et n'est pas transmisse. La **clé publique** quant à elle sert à vérifier la signature du `token` (pour attester qu'il n'a pas été modifié) et peut être distribuée. Le jeton final est encodé en `base64`.

Tout le monde peut décoder un `JWT` et lire son contenu. Donc il **ne doit pas contenir d'informations sensibles** (mot de passe, numéro de carte de crédit, etc...) Cependant, bien qu'il soit lisible par tous, il est impossible de le **falsifier**. En effet, seul l'émetteur qui possède la clé privée ayant servi à signer le jeton peut le modifier ! Si quelqu'un tente de falsifier un `JWT`, il ne pourra pas connaître la signature adéquate à apposer pour ce nouveau corps de données. Ainsi, grâce à la signature, un token falsifié sera rejeté.

Vous pouvez aller voir à quoi rassemble un `JWT` [à cette adresse](https://jwt.io/).

De nos jours, les **API** utilisent un système d'authentification par `token` comme avec les `JWT`. La logique est la suivante :

* Le serveur possède une paire clé publique/clé privée.

* Quand un utilisateur veut s'authentifier, il envoie ses identifiants, le serveur les vérifie et, si tout est bon, créé un `JWT` contenant les informations utiles au serveur, par exemple, l'id de l'utilisateur.

* Le serveur renvoie le `JWT` et le client le conserve.

* Dès qu'il veut accéder à une route sécurisée, le client envoie le `JWT` au serveur, en plus de sa requête.

* Quand le serveur reçoit le `JWT`, il vérifie qu'il n'a pas été falsifié (avec les deux clés) et peut donc le décoder en toute confiance et récupérer les informations de l'utilisateur à partir de son identifiant stocké dans le `JWT` (en faisant une requête sur la base pour obtenir le reste des informations, par exemple) et ainsi vérifier s'il a le droit d'effectuer cette requête.

Bien que le `JWT` soit décodable côté client, il est impossible le falsifier pour changer l'id de l'utilisateur contenu, car le serveur le détectera. Il est aussi impossible de simplement créer un `JWT` avec l'identifiant d'un autre utilisateur, car le client ne peut pas générer la signature adéquate (car il ne possède pas la clé privée).

On respecte la notion de **stateless** car le serveur ne garde aucune information sur l'utilisateur dans une session ou en mémoire vive. Le `JWT` est transmis à chaque requête par le client, quand on en a besoin, ce qui permet de vérifier sa légitimité.

Néanmoins, si ce token venait à être dérobé, alors on pourrait usurper un utilisateur. C'est pour cela que les `JWT` ont une date d'expiration (généralement, 3600 secondes, mais on peut mettre plus). Pour éviter que l'utilisateur n'ait à se reconnecter manuellement lors de l'expiration, il faut alors mettre en place tout un système de rafraîchissement que nous aborderons dans une section bonus de ce TD.

### Prise en charge des utilisateurs

Tout abord, nous allons devoir intégrer nos utilisateurs au système d'authentification de `Symfony` pour que par la suite, ils puissent être vérifiés et récupéré automatiquement lors de l'envoi des identifiants au serveur.

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

Dans le projet précédent, cette section avait était générée automatiquement, car nous avions utilisé la commande `make:user` pour créer l'entité `Utilisateur`.

Si vous vous souvenez bien, dans le formulaire de création de l'utilisateur, il y avait un champ `plainPassword` qui ne faisait pas partie de l'entité et qui était **haché** puis placé dans la propriété `password`. Mais comment faire cela, alors que nous n'avons pas de formulaire ni de controller pour gérer ce comportement ?

Pour la question du champ `plainPassword`, c'est très simple : il suffit de la placer dans la classe `Utilisateur` (comme propriété de la classe) mais ne pas placer d'attribut `#[ORM\Column]` dessus. Ainsi, la propriété pourra être utilisée dans le payload mais ne sera pas enregistré dans la base de données. On interdira l'écriture du champ `password` par l'utilisateur, car cela sera géré par l'application (pour rappel, `password` contient le mot de passe chiffré).

Enfin, pour pouvoir transformer `plainPassword` en mot de passe haché, nous allons utiliser une classe particulière appelée `StateProcessor`.

Sur API Platform, il existe deux types de classes importantes : Les `StateProcessor` et les `StateProvider`.

* Une classe de type `StateProcessor` est une sorte de service qui est appelé après avoir reçu et vérifié le `payload` lors d'une requête de création ou de mise à jour. Elle a pour but d'effectuer des traitements avant l'enregistrement en base de données.

* Une classe de type `StateProvider` va permettre à l'inverse d'appliquer des modifications aux données récupérées lors d'une opération `GET`.

Il est bien entendu possible d'injecter des services (et paramètres) dans ces classes. Il est notamment possible d'accéder au **processeur** utilisé par API Platform pour poursuivre le traitement normal après (ou avant) avoir appliqué nos modifications.

Dans notre cas, il nous faut donc un `StateProcessor` afin de chiffrer le mot de passe avant de sauvegarder l'utilisateur dans la base de données.

La commande suivante permet d'initialiser un `StateProcessor` :

```bash
php bin/console make:state-processor MonStateProcessor
```

Ce qui donne la classe suivante :

```php
#src/State/MonStateProcessor.php
class MonStateProcessor implements StateProcessor {

    public function __construct(
        /* Injection de dépendances */
    )
    {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        //$data est l'entité qu'on manipule (par exemple, une Publication, un Utilisateur...)
        //$operation est l'opération éxécutée (GET, POST, ...)
        //$uriVariables contient les éventuelles variables paramétrés dans le chemin de la route
    }

}
```

Généralement, on va **injecter** un service nommé `ProcessorInterface` dans notre processeur, permettant de poursuivre le traitement produit par API Platform (sauvegarde en base de données). Néanmoins, comme quand nous définissons nos propres services avec des interfaces, il faut préciser à notre application quel est le service concret qu'on souhaite utiliser pour cette interface (API Platform n'en enregistre pas par défaut). 

Comme il existe différents services que nous pouvons utiliser pour cette interface (un pour la sauvegarde, un pour la suppression...) il ne faut pas fixer ce service de manière globale dans l'application. À la place, on va utiliser l'attribut `#[Autowire]` afin de préciser le service concret que l'on souhaite utiliser.

```php
#src/State/MonStateProcessor.php
class MonStateProcessor implements StateProcessor {

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor
    )
    {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        //Modification des données de $data

        //Sauvegarde en base
        $this->persistProcessor->process($data, $operation, $uriVariables, $context);
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

3. Dans votre classe `Utilisateur`, décommenttez la propriété `password` et ses getters/setters. Faites en sorte qu'il soit impossible de lire et d'écrire cette propriété (au niveau de l'API).

4. Ajoutez une propriété `$plainPassword` de type `string` à la classe `Utilisateur` (ainsi que ses getters/setters). Cet attribut ne doit pas être stocké dans la base ! Reprenez les assertions que vous utilisiez dans la classe `UtilisateurType` du projet précédent pour les appliquer sur cette propriété. Cette propriété ne doit jamais pouvoir être lue par l'utilisateur (jamais normalisée).

5. Modifiez la méthode `eraseCredentials` afin que celle-ci mette `plainPassword` à **null**. En effet, par mesure de sécurité, comme cette propriété est stockée dans la classe utilisateur et non pas dans un formulaire, elle va être enregistré dans la session. Il faut donc posséder une méthode afin de "vider" cette information sensible après l'avoir utilisé.

6. Créez un **state processor** nommé `UtilisateurProcessor` qui devra hacher le mot de passe de l'utilisateur (`plainPassword`), puis l'affecter à `password`. Il faut ensuite utiliser `eraseCredentials` afin de supprimer les informations sensibles. Enfin, il faut reprendre le traitement normal d'API Platform pour sauvegarder l'entité en base. 

    * Vous pouvez réutiliser le bout de code (voir la méthode complète) définie dans la classe `UtilisateurManager` du projet précédent. Il vous faudra donc aussi injecter et utiliser le service `UserPasswordHasherInterface`.

7. Utilisez votre `UtilisateurProcessor` comme processeur de l'opération `POST` sur l'entité `Utilisateur`.

8. Videz le cache.

9. Synchronisez vos changements avec la base de données.

10. Créez un utilisateur, depuis Postman. Il faudra bien préciser le `plainPassword` cette fois. Allez vérifier ensuite, dans votre base, que le mot de passe est bien haché.

11. Modifiez votre `UtilisateurProcessor` de manière à ne pas tenter de hacher le mot de passe s'il n'est pas transmis (s'il est **null**, donc). Cela va nous permettre d'utiliser le même processeur pour la création et la mise à jour (mais on aurait aussi pu en faire deux distincts). Affectez donc aussi `UtilisateurProcessor` comme processeur de l'opération `PATCH`.

12. Videz le cache puis vérifiez que la mise à jour de l'utilisateur fonctionne bien, c'est-à dire que si le mot de passe est précisé, il est bien re-chiffré.

    Attention, pour rappel, il faut utiliser le **Content-Type** `application/merge-patch+json` pour faire un `PATCH`.

</div>

### Groupes de validation

Actuellement, si on essaye de faire un `PATCH` sur l'utilisateur, on nous obligera toujours à rentrer le mot de passe, car nous utilisons les attributs `NotBlank` et `NotNull`. Or, dans un `PATCH`, on doit pouvoir mettre à jour seulement les attributs que l'on souhaite. 

Nous allons utiliser le mécanisme appelé **groupes de validation** que nous avons vu lors du TD3 afin d'activer certaines contraintes seulement dans certains contextes.

Pour rappel, afin de préciser les **groupes** dans lesquels un attribut s'applique, on utilise le paramètre `groups`. Ensuite, au niveau de l'opération, on utilise le paramètre `validationContext` pour préciser les groupes de validation actifs lors de cette opération. Il faut penser à préciser le groupe `Default` afin que les attributs n'ayant pas précisé de groupe soient activés !

```php
#[ApiResource(
    operations: [
        new Post(validationContext: ["groups" => ["Default", "exemple:create"]]),
        new Patch(validationContext: ["groups" => ["Default", "exemple:udpate"]]),
    ],
)]
class Exemple {
    #[Assert\NotBlank(groups: [`exemple:create`])]
    #[Assert\NotNull(groups: [`exemple:create`])]
    private ?string $propriete;
}
```

Dans l'exemple ci-dessus, on oblige `propriete` à être précisé seulement lors de la création de l'entité (POST). Lors de la mise à jour, si la propriété n'est pas précisée, cela n'engendre pas d'erreur (mais on peut quand même l'ajouter au payload, ce n'est juste pas obligatoire).

Attention, ces groupes sont différents de ceux utilisés dans `normalizationContext` et `denormalizationContext`. Par convention, nous les nommons de la même manière, mais leur usage est tout à fait différent.

<div class="exercise">

1. En vous aidant de deux groupes de validation : `utilisateur:create` et `utilisateur:update`, faites en sorte qu'il soit obligatoire de préciser `plainPassword` seulement lors de la requête `POST`.

2. Videz le cache puis vérifiez que vous pouvez mettre à jour l'utilisateur sans préciser le mot de passe.

3. Utilisez vos groupes de validation sur les attributs des autres propriétés (login et adresse email) afin qu'ils soient obligatoire lors de la création, mais pas lors de la mise à jour (en fait, c'est le cas par défaut lors d'un PATCH, car les valeurs de ces attributs existent déjà dans les données de l'utilisateur et ne sont pas nulles, mais préciser ces groupes permet plus de clarté). Videz le cache.

</div>

### Contexte de dénormalisation

Maintenant, nous faisons face à un autre problème : on voudrait que certaines propriétés puissent être précisées lors de la création, mais pas lors de la mise à jour (qu'elles soient ignorées). Par exemple, on souhaite que le login d'un utilisateur ne puisse pas être mis à jour. Pour cela, on peut utiliser les groupes de **dénormalisation**.

A l'inverse des groupes de **normalisation** où nous avions précisé quels attributs afficher ou non lors d'une opération type `GET`, les groupes de **dénormalisation** permettent d'ignorer certaines propriétés.

Au niveau d'une propriété, il suffit de rajouter le `groupe` (dans l'annotation `#[Groups(...)]` que vous avez déjà utilisé pour la normalisation) pour lequel la propriété ne doit pas être ignoré. Par exemple : 

```php

//Si 'entite:create' et 'entite:update' sont des groupes de dénormalisation.

//La propriété n'est pas ignorée
#[Groups(['entite:read', 'entite:create', 'entite:update'])]
private ?string $prop1 = null;

//Si le groupe 'entite:create' est actif, la propriété n'est pas ignorée, mais si le groupe 'entite:update' est actif, elle est ignorée.
#[Groups(['entite:read', 'entite:create'])]
private ?string $prop2 = null;
```

Pour définir quel groupe activer sur telle ou telle méthode, on spécifie un paramètre `denormalizationContext` au niveau de l'opération souhaitée.

Par exemple :
```php
#[ApiResource(
    operations: [
        new Patch(denormalizationContext: ["groups" => ["entite:create"]]) ,
        new Post(denormalizationContext: ["groups" => ["entite:udpate"]]) ,
    ],
    normalizationContext: ["entite:read"]
)]
```

<div class="exercise">

1. En utilisant deux nouveaux groupes : `utilisateur:create` et `utilisateur:update`, faites en sorte que le login soit ignoré dans le cadre d'une requête `PATCH`. Attention, il faut préciser les groupes de **dénormalisation** où les autres propriétés sont actifs (`login`, `plainPassword`, `adresseEmail` doivent pouvoir être créés et mis à jour). L'identifiant est un cas à part, car il n'est pas possible que l'utilisateur le créé ou le mette à jour de manière générale (mais vous pouvez quand même préciser ses groupes).

2. Videz le cache. Tentez de mettre à jour le login d'un utilisateur (avec `PATCH`). Vous devriez constater que le login n'a pas été mis à jour !

3. Tentez de créer un utilisateur sans spécifier le login, cela ne devrait pas fonctionner.

4. Tentez de mettre à jour seulement l'adresse mail d'un utilisateur, cela devrait fonctionner. 

</div>

### Prise en charge des JWT

Maintenant, il ne nous reste plus qu'à mettre en place un système de connexion afin de délivrer un `JWT` qui permettra de nous identifier auprès du serveur, à chaque requête. Pour cela, nous allons utiliser le bundle `jwt-authentication-bundle`.

**Si vous êtes en local**, il faudra activer l'extension **sodium** dans votre fichier `php.ini`. Trouvez la ligne `;extension=sodium` dans votre fichier et enlevez `;` afin de décommenter l'extension.

Afin d'activer l'authentification de nos utilisateurs, il nous faut une route dédiée. Pour la déclarer, nous pouvons utiliser le fichier `config/routes.yaml` :

```yml
auth:
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
                check_path: auth
                #La propriété correspondant au login dans notre entité
                username_path: login
                #La prorpiété correspondant au mot de passe (haché) dans notre entité
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

3. Au niveau du fichier `config/routes.yaml` ajoutez la route d'authentification.

4. Éditez le fichier `config/packages/security.yaml` afin de prendre en charge le système d'authentification.

5. Videz le cache.

6. En utilisant `Postman`, envoyez une requête `POST` à la route `/api/auth` (à adapter avec votre URL) en précisant un `payload` de connexion d'un utilisateur de votre application (avec un login / mot de passe valide). Vous devriez obtenir un `JWT` !

7. Sur le site [https://jwt.io/](https://jwt.io/), tentez de décoder ce jeton et observez l'information qu'il contient.
</div>

En plus du **login** de l'utilisateur, on aimerait ajouter son **identifiant**, son **adresse email** et son statut **premium** dans le `JWT` (pour pouvoir le lire côté client). Pour cela, on peut créer une classe qui permettra de détecter l'événement de création du `JWT` et d'y ajouter l'identifiant.

Voici cette classe :

```php
namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{
    /**
     * @param JWTCreatedEvent $event
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $payload = $event->getData();
        //Insertion de données ICI - A compléter
        $payload["cle"] = '...';
        $event->setData($payload);
    }
}
```

Le but de la méthode `onJWTCreated` est de capter l'événement de création du token. L'événement `$event` nous permet d'utiliser diverses méthodes :

* `getData` : renvoie le `payload` du token sous la forme d'un tableau clé-valeur associatif. Il est donc possible d'y insérer des données comme dans un tableau associatif classique, en associant la donnée à un nom (une clé).

* `getUser` : renvoie l'objet `Utilisateur` correspondant au token. Il est donc possible de récupérer son id et de l'insérer dans le `payload`.

* `setData` permet de réaffecter le contenu du `payload` du token.

Une fois la classe créée, il faut l'enregistrer en tant que receveuse de l'événement dans le fichier `config/services.yaml` :

```yml
    # Dans config/services.yaml

    # Nom custom
    jwt_created_listener:
        # Classe prenant en charge l'événement
        class: App\EventListener\JWTCreatedListener
        # Précision de l'evenement à capter
        tags:
            - { name: kernel.event_listener, event: lexik_jwt_authentication.on_jwt_created, method: onJWTCreated }
```

<div class="exercise">

1. Créez un dossier `EventListener` dans `src` et ajoutez la classe `JWTCreatedListener` telle que définie ci-dessus puis complétez-la afin d'enregistrer l'identifiant, l'adresse email et le statut premium de l'utilisateur dans le payload.

2. Éditez le fichier `config/services.yaml` pour enregistrer ce gestionnaire d'événement.

3. Videz le cache puis tentez de vous authentifier de nouveau. Décodez le nouveau `JWT` obtenu et vérifiez que les informations supplémentaires sont bien présentes.

</div>

## Sécurité

Maintenant que nous pouvons nous authentifier, nous pouvons sécuriser l'accès à nos routes ! Nous allons donc voir comment :

* Autoriser l'accès à certaines routes seulement aux utilisateurs authentifiés.

* Vérifier que l'utilisateur qui modifie/supprime une ressource en est bien le propriétaire.

* Affecter automatiquement l'utilisateur effectuant la requête comme auteur d'une publication.

### Sécurisation de l'accès aux routes

On peut limiter l'accès à une méthode sur une ressource donnée en utilisant le paramètre `security` au niveau de la méthode en question. On peut alors spécifier du **code** pour vérifier le droit d'accès à la route, en utilisant notamment la fonction `is_granted` pour vérifier que l'utilisateur possède un certain **rôle** (ou une permission, si on utilise les **voters**).

Par exemple :

```php
#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_USER')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
```

Ici, la route utilisant la méthode `POST` sur cette ressource est uniquement accessible aux utilisateurs avec le rôle `ROLE_USER` (donc, tous les utilisateurs authentifiés). La méthode `DELETE` elle par contre n'est accessibles qu'aux utilisateurs ayant le rôle `ROLE_ADMIN`.

On a aussi accès à d'autres variables :

* `user` : représente l'instance de l'utilisateur connecté.

* `object` : représente l'instance de la ressource en cours d'accès ou de modification.

Si l'objet ciblé a un lien avec l'utilisateur, il est alors possible de comparer un attribut de l'objet avec l'utilisateur, par exemple.

```php
#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_USER') and user.age > 13"),
        new Delete(security: "is_granted('ROLE_USER') and object.getOwner() = user"),
        new Get(security: "!object.isPrivate() or (is_granted('ROLE_USER') and object.getOwner() = user) or is_granted('ROLE_ADMIN') "),
    ]
)]
```

Dans cet exemple :

* L'utilisateur peut créer un objet avec `POST` s'il est connecté et a plus de 13 ans.

* L'utilisateur peut supprimer l'objet s'il est connecté et qu'il en est le propriétaire

* L'utilisateur ne peut consulter l'objet que si celui n'est pas privé, ou s'il en est le propriétaire, ou s'il est admin.

<div class="exercise">

1. Faites en sorte que seuls les utilisateurs authentifiés puissent créer des publications.

2. Faites en sorte que seul le propriétaire d'une publication puisse la supprimer.

3. Faites en sorte que seul l'utilisateur concerné puisse modifier ou supprimer son compte.

3. Videz le cache.

4. Tentez d'accéder à une des routes sécurisées et vérifiez que l'accès vous est refusé.

5. Pour pouvoir accéder à une route sécurisée, il faut vous authentifier auprès du serveur en lui envoyant le `JWT` dans les `Headers` de la requête. Pour faire cela sur `Postman`, suivez ces étapes :

    * Dans l'onglet de la requête, rendez-vous dans `Authorization`.

    * Dans `TYPE`, sélectionnez `Bearer Token`.

    * Dans `Token`, placez le JWT que vous avez obtenu en vous authentifiant (re-authentifiez vous si nécessaire).

6. Réessayez de soumettre la requête de création d'une publication, cela devrait fonctionner !

7. Essayer de supprimer une publication qui ne vous appartient pas et un compte qui ne vous appartient pas (cela ne doit pas fonctionner). Et vérifiez qu'à l'inverse, vous pouvez effectivement supprimer vos propres publications (celles du compte auquel vous êtes connectés), et que vous pouvez aussi modifier vos propres informations...

</div>

### Affectation automatique de l'auteur d'une publication

Enfin, avec ce nouveau système d'authentification, nous pouvons logiquement automatiquement affecter l'utilisateur qui créé une publication comme auteur de celle-ci et de plus avoir à spécifier l'`IRI` (ce qui posait aussi un problème de sécurité, car on peut ainsi affecter un autre utilisateur à une publication !).

Pour cela, nous allons réutiliser le mécanisme des `processeurs` que nous avions vu plus tôt dans le cadre du hachage du mot de passe. Il vous suffit de créer un `processeur` affecté à la création d'une publication !

En plus du même `ProcessorInterface` que vous avez utilisé auparavant (pour sauvegarder), vous aurez besoin du service `Security` vous permettant de récupérer l'utilisateur courant avec `getUser`.

<div class="exercise">

1. Créez le **state processor** `PublicationProcessor`. Injectez les services qu'il faut et complétez la classe afin d'affecter l'utilisateur comme auteur de la publication traitée.

2. Au niveau de la classe `Publication`, spécifiez votre nouveau processeur au niveau de la méthode `POST`.

3. Toujours dans la même classe, servez-vous de l'annotation `#[ApiProperty]` pour interdire l'écriture de l'auteur (vu qu'il est affecté automatiquement). Retirez également les attributs `NotBlank` et `NotNull` que vous aviez sans doute placé sur cette propriété, précédemment.

4. Videz le cache puis tentez de créer une nouvelle publication (toujours en attachant votre `JWT` dans la requête). Vérifiez alors que la publication est bien créée et que l'auteur a bien été affecté par rapport à l'utilisateur représenté par le `JWT` que vous utilisez.

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

    public function __invoke(object $object): array|GroupSequence
    {
        //$object correspond à l'entité (Publication, Utilisateur...)
        //On peut vérifier que l'objet traité est bien du type attendu...
        assert($object instanceof Exemple);

        //On décide du (ou des) groupe(s) à ajouter...
        $group = [...]

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

1. Modifiez les contraintes de votre entité `Publication` afin que le message puisse contenir jusqu'à 200 caractères si un des groupes de validation activé est `publication:write:premium` et jusqu'à 50 caractères si un des groupes activés est `publication:write:normal` (comme dans le TD précédent).

2. Créez un dossier `Validator` dans `src` puis à l'intérieur une classe `PublicationWriteGroupGenerator` qui permettra de choisir la bonne liste de groupes le statut de l'utilisateur. Vous aurez encore une fois besoin du service `Security` pour obtenir l'utilisateur courant.

3. Modifiez l'opération `POST` au niveau de l'entité `Publication` afin d'utiliser votre nouveau générateur de groupes, pour la validation.

4. Dans votre base de données, rendez un utilisateur premium (en changeant manuellement la propriété). Sinon, si vous aviez fait la section à propos des commandes lors du dernier, vous pouvez les importer et les utiliser!

5. Videz le cache. Sur Postman, utilisez le JWT d'un compte non premium et vérifiez que l'erreur apparaît bien si vous faites un message dépassant 50 caractères. Vérifiez également que l'erreur n'apparait pas si vous faites la même chose sur un compte premium (mais que dans ce cas, la limite à 200 est toujours présente).

</div>

### Vérification du mot de passe avant mise à jour

L'assertion `#[UserPassword]` (placé au-dessus d'une propriété) permet de vérifier que la chaîne de caractère (en clair) correspond au mot de passe de l'utilisateur, lors de la pahse de validation.

Nous souhaitons créer un système pour que quand un utilisateur souhaite mettre à jour son profil, il soit obligé de préciser une propriété `currentPlainPassword` dans le payload afin de valider son identité (et seulement pour la mise à jour!).

Normalement, vous avez toutes les connaissances nécessaires pour implémenter ce système !

<div class="exercise">

1. Créez une propriété `currentPlainPassword` dans la classe `Utilisateur` (propriété non stockée en base). Ajoutez aussi les getters/setters nécessaires.

2. En utilisant cette nouvelle propriété ainsi que les connaissances acquises dans ce TD, faites en sorte que lors d'une mise à jour (PATCH) l'utilisateur soit obligé de confirmer son mot de passe via la propriété `currentPlainPassword` (afin de valider son identité avant d'effectuer la mise à jour). Vous n'avez pas besoin de quitter la classe `Utilisateur` ou créer de nouvelles classes. Attention, cette propriété ne doit pas être utilisée lors de la création de l'entité, ou de sa lecture.

3. Dans la méthode `eraseCredentials` mettez aussi `currentPlainPassword` à **null**.

4. Videz le cache puis, sur Postman, vérifiez si tout fonctionne (tentez de mettre à jour votre profil sans puis avec `currentPlainPassword`).

</div>

### Token de rafraichissement

Plus tôt, dans la partie consacrée aux **JWT**, nous avions évoqué le **système de rfraichissement** pour permettre une plus grande sécurité. La logique est la suivante :

* Les JWT émis par l'application suite à l'authentification ont une faible durée de vie (3600 secondes par défaut). Ainsi, on, limite les risques en cas de vol. En effet, les JWT ne sont stockés nulle part. Il n'y a pas vraiment moyen de les rendre invalide. Seule la date d'expiration définie le moment où le token n'est plus utilisable.

* Pour ne pas avoir à demander à l'utilisateur de se reconnecter dès que son JWT expire, lors de l'authentification, on transmet un **token de rafraichissement** (en plus du JWT). Ce token a une durée de vie bien plus longue (par exemple, 1 mois) et est stocké en base. Dès que l'application cliente détecte que le JWT de l'utilisateur a expiré, elle fait appelle à une route spéciale permettant de rafraichir notre JWT, en transmettant le token de rafraichissement. Un nouveau JWT est transmis et la durée de vie du token de rafraichissement est réinitialisé (à son max). Côté serveur, on peut aussi supprimer les tokens de rafraichissement si besoin (et ainsi "déconnecter" l'utilisateur réellement).

Contrairement au JWT, le token de rafraichissement peut être gardé de manière plus sécurisée et n'est pas transmis à chaque requête. Si un client n'est pas actif pendant une longue période (par exemple, 1 semaine) il ne sera pas déconnecté, mais quand il retournera sur l'application, son JWT sera renouvelé grâce au token de rafraichissement. Au bout d'une trop longue période d'inactivité (1-2 mois) l'utilisateur sera déconnecté par sécurité, à cause de l'expiration de son token de rafraichissement. Comme les tokens sont stockés dans la base de données, si un utilisateur se fait compromettre, il suffit de supprimer le token de rafraichissement.

Pour mettre en place tout cela, nous allons nous servir du bundle `JWTRefreshTokenBundle`. La configuration est assez simple.

* Tout d'abord, on créé l'entité suivante, qui correspond à l'entité gérant les tokens de rafraichissement (car ils sont stockés dans notre BDD) :

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * @ORM\Entity
 * @ORM\Table("refresh_tokens")
 */
#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
    //Pas besoin de propriété particulières.
}
```

* Ensuite, on crée les différentes routes liées au rafraichissement, dans le fichier `config/routes.yaml` :

```yaml
#Route pour rafraichir notre JWT
api_refresh_token:
    path: /api/token/refresh

#Route pour invalider notre token de rafraichissement (le supprimer). utile pour se "déconnecter" réellement.
api_token_invalidate:
    path: /api/token/invalidate
```

* Après, on édite le fichier `security.yaml` afin de paramétrer notre système de rafraichissement et nos deux nouvelles routes :

```yaml
security:
    ...
    firewalls:
        ...
        main:
            ...
            entry_point: jwt
            refresh_jwt:
                check_path: api_refresh_token
            logout:
                path: api_token_invalidate
```

* Enfin, dans `config/packages`, on crée le fichier `gesdinet_jwt_refresh_token.yaml` pour configurer le bundle et indiquer quelle classe correspond au token de rafraichissement :

```yaml
#config/packages/gesdinet_jwt_refresh_token.yaml
gesdinet_jwt_refresh_token:
    #Classe du token de rafraichissement
    refresh_token_class: App\Entity\RefreshToken
    #Pour que la durée de vie du token de rafraichissement soit réinitialisée (à son max) après chaque utilisation
    ttl_update: true
    #Le firewall (section définie dans security.yaml) paramétrant notre système de déconnexion / d'invalidation de token.
    logout_firewall: main
```

Maintenant, quand vous vous authentifierez avec la route `/api/auth`, vous obtiendrez votre `token` (JWT) habituel ainsi qu'un token `refresh_token`. Vous pourrez alors l'utiliser dans le payload des routes suivantes :

* `/api/token/refresh` (en POST) : rafraichit votre JWT et réinitialise la durée de vie du token de rafraichissement.

* `/api/token/invalidate` (en POST) : supprime le token de rafraichissement. L'utilisateur sera donc "déconnecté" quand son JWT arrivera à expiration (il peut aussi détruire son JWT côté client, mais ici on s'assure que le dernier JWT transmis ne pourra pas être renouvelé).
 
On a aussi accès à quelques commandes utiles, comme :

```bash
php bin/console gesdinet:jwt:clear
php bin/console gesdinet:jwt:revoke TOKEN
```

La première commande permet de supprimer tous les tokens de rafraichissement ayant expiré. La seconde permet de révoquer un token précis (par exemple, si le compte de l'utilisateur est compromis).

Maintenant, à vous de jouer!

<div class="exercise">

1. Installez le bundle `JWTRefreshTokenBundle` :

    ```bash
    composer require gesdinet/jwt-refresh-token-bundle
    ```

2. Activez le bundle dans `config/bundles.php` :

    ```php
    return [
        ...
        Gesdinet\JWTRefreshTokenBundle\GesdinetJWTRefreshTokenBundle::class => ['all' => true],
    ];
    ```

3. Créez l'entité `RefreshToken` (vous pouvez directement copié le code présenté plus tôt).

4. Remplissez les différents fichiers de configurations : `routes.yaml`, `security.yaml`, `gesdinet_jwt_refresh_token.yaml`.

5. Videz le cache.

6. Mettez à jour la structure de votre base de données.

7. Testez votre nouveau système :

    * Authentifiez-vous et vérifiez que vous obtenez bien un `refresh_token` en plus de votre token habituel.

    * Testez la route permettant de rafraichir votre JWT (vous obtenez un nouveau token).

    * Testez la route permettant d'invalider votre token. Normalement, si vous essayez après cela de rafraîchir votre JWT, il doit y avoir une erreur.

</div>

### Utiliser les voters

Si vous avez complété la section bonus du TD précédent concernant les **permisisons avancées** et les **voters**, sachez que vous pouvez aussi les utiliser ici.

Dans le paramètre `seucirty` de chaque opération, il suffit de préciser la permission dans la fonction `is_granted` et l'objet `object` (si besoin de vérifier la permission par rapport à un objet précis, comme le propriétaire, etc.) :

```php
#[ApiResource(
    operations: [
        new Post(security: "is_granted('PERMISSION', object)"),
    ]
)]
```

<div class="exercise">

1. Importez la hiérarchie de rôles que vous aviez mis en place dans `security.yaml` du projet précédent (avec `ROLE_ADMIN`)?.

1. Importez le voter `PublicationVoter` depuis le projet précédent (il faudra créer le chemin de répertoires `src/Security/Voter`).

2. Mettez à jour la permission de l'opération `DELETE` pour utiliser la permission définie dans votre **voter**.

3. Créez un nouveau **voter** nommé `UtilisateurVoter` et traitant la permission `UTILISATEUR_EDIT` (qui sera utilisé à la fois pour la mise à jour et la suppression). Cette permission est accordée à un utilisateur connecté qui soit administrateur (`ROLE_ADMIN`) soit lui-même l'objet cible de cette permission.

4. Mettez à jour les permissions des opérations `PATCH` et `DELETE` pour utiliser votre nouvelle permission, issue de votre **voter**.

5. Videz le cache et testez que les permissions fonctionnent bien (mêmes tests que dans l'exercice que nous avions effectué dans la partie "sécurité de ce TD).

6. Si vous voulez, vous pouvez également ajouter une permission `PUBLICATION_CREATE` dans votre `PublicationVoter`. La permission est accordée si l'utilisateur est connecté (donc possède `ROLE_USER`). Cela permet de centraliser cette permission et la changer si besoin, dans le futur. Attention toutefois dans la méthode `support`, il faut autoriser que l"'objet traité soit **null** en modifiant légèrement la condition (dans une opération de création, la publication n'existe pas encore...).

</div>

## Conclusion

Nous avons terminé de construire notre `API` ! Elle est complète et prête à l'emploi pour être utilisée dans n'importe quelle application cliente (mobile, web, ...).

Vous avez pu constater la puissance de l'outil `API Platform`. Nous ne sommes pas beaucoup sortis des classes **entités** et la majeure partie de la logique métier de l'application est spécifiée grâce aux attributs dans ces classes. Pour les traitements particuliers, nous pouvons utiliser les **state processors** (et les **state provider**). S'il y a vraiment besoin, il y toujours possibilité de définir des **controllers** avec des routes comme nous le faisions avant (par exemple, pour un **webhook**).

Dans la suite des TDs de web, vous allez apprendre à utiliser un framework JS client : `Vue.js`. Gardez donc cette API de côté, vous pourriez être amené à la réutiliser...
