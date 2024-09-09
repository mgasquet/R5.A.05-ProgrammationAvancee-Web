---
title: TD1 &ndash; Découverte du framework Symfony 1/2
subtitle: Routing, Doctrine, Twig, Formulaires
layout: tutorial
lang: fr
---

{% raw %}

**Petite note à l'attention des étudiants du parcours IAMSI** : le TD fait parfois référence au **cours de complément web** de l'année dernière, car nous revenons sur certaines notions, technologies et concepts déjà abordés dans ce cours. Malheureusement, de votre côté, vous n'avez pas eu ce cours spécifique au parcours **RACDV**. Mais pas de panique ! Le TD a été aussi pensé pour vous et ces notions sont réexpliquées complètement. Simplement, là où certaines sections feront office de rappel pour le parcours **RACDV**, dans votre cas, cela sera une découverte. Donc, ne vous en faites pas s'il est fait mention de ce cours de l'année dernière. Ce TD a été conçu pour être accessible par tous !

## Mise en place de l’environnement de développement avec Docker

Afin de simplifier la mise en place de l'environnement de développement en évitant les divers problèmes de compatibilité selon votre machine ou votre système d'exploitation, le travail sur les différents TPs s'effectuera au travers d'un **conteneur Docker** configuré adéquatement et possédant tous les outils dont vous aurez besoin.

<div class="exercise">

Allez sur la page du [dépôt du conteneur Docker](https://gitlabinfo.iutmontp.univ-montp2.fr/progweb-but3/docker), et faites le tutoriel d’installation et de configuration (instructions au niveau du fichier `README.md`). Vous ne devez normalement pas y passer plus de 30 minutes.

</div>

**Toutes les commandes citées dans ce TP (et les prochains) devront être exécutées dans le conteneur du serveur web.**

## Introduction

L'année dernière, dans le cadre du cours de **complément web** (pour le parcours RACDV), vous avez étudié et mis en pratique les notions essentielles afin de construire un **framework** web. Nous nous sommes notamment intéressés aux notions de conteneur à injection de dépendances (conteneur IoC), aux design patterns et globalement aux outils utiles permettant de rendre notre framework simple d'utilisation et hautement paramétrable.

Mais concrètement, qu'est-ce qu'un **framework** ? Un framework est un "cadre de développement" fournissant une architecture et des outils permettant de créer une application (dans notre cas une application web, mais il en existe aussi pour d'autres types de logiciels). Un framework est composé de différentes briques logicielles et est généralement construit de manière à favoriser les bonnes pratiques de conception (utilisation de patterns, architecture organisée en couche, faible couplage, principes SOLID...). La sécurité (basique) de l'application est généralement assurée sans action du développeur (par exemple, pour le web, protection par défaut contre l'injection SQL, le CSRF, la faille XSS...).

La plupart des frameworks utilisent un `ORM` permettant de gérer simplement la couche "stockage" d'une application. Nous avions notamment utilisé l'`ORM` *Hibernate* basé sur `Java` dans le cours de programmation des bases de données du semestre 3. 

On parle de "cadre" car afin de coder son application, le développeur va devoir respecter le fonctionnement imposé par le framework et utiliser ses outils, ce qui l'empêche de dériver en proposant une conception et une manière de développer hors du "cadre" imposé. Et c'est une bonne chose car, sans s'en rendre compte, le développeur utilise de bonnes pratiques, ce qui augmente la maintenabilité et la lisibilité de son programme. De plus, le code d'un programme développé sous un framework précis sera rapidement compréhensible par un autre développeur connaissant aussi cette même technologie.

Avant tout, les frameworks sont surtout faits pour faciliter la vie du développeur et accélérer le développement de l'application.

L'année dernière, le framework que vous avez développé était fonctionnel, mais encore incomplet. Il est donc temps de passer à l'apprentissage d'un outil professionnel concret !

Dans le cadre de ces quatre premiers TDs, nous allons donc étudier le framework PHP `Symfony`.

Symfony est un framework web PHP **français** créé en 2005. Assez tôt, il a connu une forte popularité dans le pays. Aujourd'hui, nous en sommes à la version 6, et Symfony est devenu le framework web (PHP) le plus utilisé en France, comme nous pouvons le voir avec le graphique ci-dessous :

<script type="text/javascript" src="https://ssl.gstatic.com/trends_nrtr/3349_RC01/embed_loader.js"></script>

<script type="text/javascript">
    trends.embed.renderExploreWidget("TIMESERIES", {"comparisonItem":[{"keyword":"/m/09cjcl","geo":"FR","time":"today 5-y"},{"keyword":"/m/0jwy148","geo":"FR","time":"today 5-y"},{"keyword":"/m/02qgdkj","geo":"FR","time":"today 5-y"},{"keyword":"/m/09t3sp","geo":"FR","time":"today 5-y"}],"category":0,"property":""}, {"exploreQuery":"date=today%205-y&geo=FR&q=%2Fm%2F09cjcl,%2Fm%2F0jwy148,%2Fm%2F02qgdkj,%2Fm%2F09t3sp&hl=fr","guestPath":"https://trends.google.fr:443/trends/embed/"});
 </script>

<div style="margin:auto;width:50%">
{% endraw %}
![pas mal non]({{site.baseurl}}/assets/TD1/pas-mal-fr.PNG)
{% raw %}
</div>

Cependant, si nous élargissons ce graphique au niveau mondial, le framework `Laravel` est beaucoup plus utilisé. En fait, Symfony est majoritairement utilisé en France.

<script type="text/javascript">
    trends.embed.renderExploreWidget("TIMESERIES", {"comparisonItem":[{"keyword":"/m/09cjcl","geo":"","time":"today 5-y"},{"keyword":"/m/0jwy148","geo":"","time":"today 5-y"},{"keyword":"/m/02qgdkj","geo":"","time":"today 5-y"},{"keyword":"/m/09t3sp","geo":"","time":"today 5-y"}],"category":0,"property":""}, {"exploreQuery":"date=today%205-y&q=%2Fm%2F09cjcl,%2Fm%2F0jwy148,%2Fm%2F02qgdkj,%2Fm%2F09t3sp&hl=fr","guestPath":"https://trends.google.fr:443/trends/embed/"});
</script>

Nous avons donc fait le choix de plutôt vous apprendre à utiliser Symfony par rapport à Laravel car il y a plus de chances que les offres d'emplois pour vos stages ou votre futur travail après la fin de vos études concernent du développement sous Symfony (si vous souhaitez devenir développeur web, bien sûr).

Cependant, pas de panique, il y a beaucoup de similitudes entre Symfony et Laravel (même certaines choses identiques !). Une fois un framework maîtrisé, il vous sera facile de passer à l'autre.

Symfony permet de créer des sites web classiques en mode "server-side rendering" (génération de la page web du côté du serveur, comme ce que vous faisiez jusqu'ici), des API REST et même des briques logicielles (appelées `bundles`) à intégrer à Symfony, pour lui ajouter de nouvelles fonctionnalités.

Ce framework utilise (par défaut) l'ORM `Doctrine` (syntaxe assez similaire à Hibernate, en JAVA). Concernant les templates (génération de pages HTML), il utilise `Twig` que vous avez déjà utilisé l'année dernière dans le cadre du cours de complément web.

Concernant le thème de l'application que nous allons construire au fil des cours, nous allons reprendre le concept du réseau social **The Feed** en l'améliorant. Concernant la partie sur twig, vous allez donc *un peu* refaire certaines choses que vous aviez faites en complément web, mais pas avec le même framework et nous allons aussi aller plus loin.

Nous allons suivre la progression suivante :

1. Création du site "The Feed" en utilisant Symfony. L'objectif est d'arriver au même résultat que vous aviez obtenu (niveau fonctionnalités) à la fin des cours de complément web.

2. Amélioration du site. Nous allons ajouter de nouvelles fonctionnalités, des rôles pour les utilisateurs, la suppression de compte, de publications, une boutique pour acheter le mode "premium" du site...

3. Création d'une API REST pour The Feed. Nous allons créer une API complète pour le site, ce qui permettra de complétement découpler l'application de la partie "front-end" et ainsi la relier et l'utiliser avec n'importe quelle technologie côté client (ce qui sera le thème de la suite des cours de cette ressource !).

Pour cette première séance, recréons donc The Feed en utilisant ce nouvel outil ! Vous allez constater que certaines parties vont aller beaucoup plus rapidement (et facilement) que l'année dernière !

## Installation et mise en route

Tout d'abord, il va falloir créer un projet avec **Symfony**. Nous pouvons faire cela simplement en utilisant l'outil **composer** (installateur de librairies PHP que vous avez déjà utilisé l'an dernier).

<div class="exercise">

1. Depuis le terminal (dans le conteneur docker), assurez-vous d'être bien placé dans `/var/www/html`.

2. Exécutez les commandes suivantes :

    ```bash
    composer create-project symfony/skeleton:"6.4.*" the_feed
    cd the_feed
    composer require webapp
    ```

   Il se peut que composer vous demande de faire un choix concernant Docker, tapez simplement "n".

   Cet ensemble de commandes crée les fichiers de base de votre projet et télécharge les briques logicielles essentielles pour le développement d'un site web.

3. Donnez au serveur web les **permissions** pour créer et éditer des fichiers dans votre projet (à exécuter **depuis la racine du projet**) :

   ```bash
   chown -R root:www-data .
   ```
   On rappelle encore une fois qu'il faut exécuter ces commandes dans le terminal qui s'exécute dans votre conteneur docker.

   Il peut y avoir des erreurs et certaines permissions non accordées, ce n'est pas grave.

4. Sur votre machine, ouvrez le répertoire du projet (`the_feed`) avec votre **IDE** favori (de préférence, `PHPStorm`). Celui-ci se trouve dans le dossier partagé `shared/public_html` (là où vous avez installé le conteneur docker).

5. Téléchargez le {% endraw %}[fichier d'accès au serveur]({{site.baseurl}}/assets/TD1/htaccess){% raw %}, renommez-le `.htaccess` et placez-le dans le sous-dossier `public` du projet.

6. Testez que votre projet a bien été initialisé en vous rendant à l'adresse [https://localhost/the_feed/public](https://localhost/the_feed/public)

</div>

Vous devriez maintenant voir la page par défaut de Symfony, signe que tout est bien installé. Vous êtes prêts à débuter le développement du site !

Comme vous pouvez le constater, il y a **beaucoup** de fichiers qui ont été générés. Au lieu de tous vous les présenter dans une section dédiée, nous parlerons plutôt de chaque fichier/dossier utile individuellement quand nous aurons besoin de l'utiliser.

Quand vous uploaderez votre projet sur git, certains dossiers comme `vendor` (contenant les librairies importées) ou bien le cache du site seront ignorés. Pour installer un projet déjà existant (chez vous, sur une autre machine, dans un autre conteneur docker...) il suffit d'exécuter la commande suivante à la racine du projet :

```bash
composer install
```

Composer va notamment utiliser les dépendances listées dans le fichier `composer.json` pour télécharger tout ce qu'il faut.

## Premiers pas

Dans cette première section, nous allons voir comment créer des contrôleurs, des routes et générer puis renvoyer la page HTML désirée. Nous parlerons aussi de la gestion des **messages flash** avec Symfony.

### Contrôleur et Routing

Symfony propose diverses commandes qui permettent d'initialiser (voir de créer en quasi-totalité) des classes de certaines catégories (entités, contrôleurs, formulaires...) et de la placer au bon endroit dans l'architecture de l'application. Ces commandes doivent s'exécuter **à la racine du projet**;

Toutes les commandes de Symfony s'exécutent via un script `PHP` nommé **console** se trouvant dans le sous-dossier **bin**. On utilise donc le programme `php` (généralement à partir de la racine du projet) pour exécuter ce script. Il suffit ensuite de préciser le nom de la commande (de Symfony) souhaitée et d'éventuels paramètres et/ou options.

```bash
php bin/console macommande param1 param2 --option1 --option2
```

Afin de créer un nouveau contrôleur, nous pouvons notamment utiliser la commande suivante (à partir de la racine du projet) :

```bash
php bin/console make:controller ExempleController
```

Ce qui aura pour effet de placer dans le dossier `src/Controller` le fichier suivant :

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExempleController extends AbstractController
{
    #[Route('/exemple', name: 'app_exemple')]
    public function index(): Response
    {
        return $this->render('exemple/index.html.twig', [
            'controller_name' => 'ExempleController',
        ]);
    }
}
```

Une route et une fonction d'exemple sont créées par défaut, mais nous la supprimerons pour placer nos propres fonctions.

Bien entendu, vous pouvez aussi créer un contrôleur à la main, mais la commande a aussi l'avantage d'inclure les premiers imports nécessaires.

Pour rappel, le **routing** est le fait d'associer un chemin du site (par exemple `/coucou`) et une méthode HTTP (GET, POST, PUT, PATCH ou DELETE) à une fonction (du contrôleur) qui va traiter la requête puis renvoyer la réponse (page `HTML` ou des données sous un format comme du `JSON` pour une API...)

#### Attributs en PHP

Pour créer une route avec Symfony, nous utilisons une fonctionnalité introduite depuis PHP 8 : les **attributs** (on retrouvera parfois le terme d'**annotations** pour désigner les attributs, qui était l'ancienne façon de faire...)

Les **attributs** sont des informations que nous allons ajouter à un élément de l'application : une fonction, une classe entière, une propriété de la classe... Ce sont des méta-données et des règles qui vont pouvoir être lues (ici par le framework) interprétées et utilisées pour configurer notre application. Ces attributs sont aussi paramétrables.

Un attribut se présente ainsi :

```php
#[ClasseAttribut(param1: ..., param2: ...)]
```

En réalité, un **attribut** est une classe. Les paramètres que l'on précise quand on utilise un attribut sont des données passées au constructeur de l'attribut (en fait, on a créé une nouvelle instance chaque fois quand précise un attribut). Il est ensuite possible d'analyser une classe, une fonction, etc... afin de récupérer ses attributs (il y a des méthodes PHP dédiées, par exemple : [ReflectionProperty::getAttributes](https://www.php.net/manual/en/reflectionproperty.getattributes.php), [ReflectionClass::getAttributes](https://www.php.net/manual/en/reflectionclass.getattributes.php)).

Lors de l'utilisation de l'application, les différentes briques qui composent Symfony se chargent de lire ces attributs et d'exécuter des actions dans différents contextes, par exemple, pour enregistrer les informations sur les routes de l'application, ou bien sur les permissions.

#### Arguments nommés en PHP

Vous aurez remarqué que le constructeur de `ClasseAttribut` utilise une syntaxe un peu particulière pour son initialisation. En fait, depuis **PHP 8**, nous ne sommes pas obligés de préciser les paramètres d'une fonction dans l'ordre (cela inclut les constructeurs). On peut directement indiquer le nom du paramètre souhaité (même si celui-ci se trouve, par exemple en 3ᵉ position) et lui associer sa valeur. Ce mécanisme est appelé **arguments nommés** et est similaire à ce qu'il est possible de faire en **python**, notamment. Plus d'informations sur [la documentation officielle](https://www.php.net/manual/fr/functions.arguments.php#functions.named-arguments).

#### Attribut pour le routage

Afin de relier une route à une méthode d'un contrôleur avec Symfony, il suffit donc d'ajouter l'attribut `Route` suivant, au-dessus de la méthode désirée :

```php
 #[Route('/exemple', name: 'route_exemple', methods: ["GET", "POST", ...])]
public function methodeExemple(): Response
{
    ...
}
```

* Le premier paramètre correspond au sous-chemin à partir de la racine de votre site web. Dans l'exemple ci-dessus, si par exemple votre projet est hébergé sur `https://monsite.com`, alors cela correspond à l'url `https://monsite.com/public/exemple` (dans un cas réel, on pointerait directement le nom de domaine sur le sous-dossier "public" du projet, et pas à sa racine, ce qui donnerait alors directement `https://monsite.com/exemple`).

* Le second paramètre `name` correspond au nom de la route. Celui-ci doit être **unique** dans toute l'application (pas deux routes avec le même nom). Ce nom de route est très important, car on pourra l'utiliser au lieu du chemin pour rediriger l'utilisateur, ou bien générer des URLs dans nos pages HTML.

* Le dernier paramètre correspond aux **méthodes HTTP** autorisées sur cette route. Par exemple, on peut autoriser seulement la méthode `GET` à être appelée sur cette route, ou bien seulement `GET` ou `POST`.

Vous noterez que si le nom d'une route doit être unique, il est toutefois possible d'avoir un chemin identique pour deux routes différentes si les actions autorisées ne sont pas les mêmes.

Par exemple, on pourrait regrouper le traitement des actions `GET` et `POST` sur une seule méthode pour le chemin `/exemple` :

```php
 #[Route('/exemple', name: 'route_exemple', methods: ["GET", "POST"])]
public function methodeExemple(): Response
{
    ...
}
```

Ou bien diviser son traitement avec deux routes distinctes :

```php
 #[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(): Response
{
    ...
}

 #[Route('/exemple', name: 'route_exemple_post', methods: ["POST"])]
public function methodeExemplePost(): Response
{
    ...
}
```

Il existe d'autres paramètres utiles dont nous pouvons nous servir, par exemple `locale` qui permet de restreindre une route selon le pays de l'utilisateur.

#### Paramètre des actions

Concernant les paramètres des méthodes, nous pouvons notamment ajouter des services qui seront injectés automatiquement (nous en reparlerons plus tard) ainsi qu'un objet de type `Request` qui permet de lire des données envoyées, par exemple dans le query string, ou bien pour traiter un formulaire.

```php
 #[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(Request $request): Response
{
    $paramQueryString = $request->get("param");
    ...
}
```

Il est aussi possible d'ajouter des `paramètres` dans le chemin des routes de la manière suivante :

```php
 #[Route('/exemple/{id}', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet($id): Response
{
    //J'ai accès au $id passé dans l'URL
}
```

Dans l'exemple ci-dessus, si l'utilisateur accède à la route `/exemple/5`, alors la variable `$id` vaudra "5". Il faut que le paramètre de la méthode ait le même nom que celui de la route (ici `id` => `$id`)

Il est possible de placer des paramètres n'importe où dans le chemin :

```php
 #[Route('/exemple/{id}/coucou/{nom}', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet($id, $nom): Response
{
    ...
}
```

Je peux par exemple déclencher cette route/méthode avec le chemin `/exemple/2/coucou/test`, ce qui affectera `$id` à "2" et `$nom` à "test".

Dans un premier temps, vous allez faire un contrôleur simple ne renvoyant pas encore de pages HTML, seulement du texte brut. Pour cela, il vous suffira de renvoyer un objet `Response` :

```php
 #[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(): Response
{
    ...
    $contenu = ...
    return new Response($contenu);
}
```

<div class="exercise">

1. En utilisant la commande `make:controller` (à la racine du projet), créez un contrôleur nommé `DemoController`. On rappelle (une dernière fois) que les commandes doivent être exécutées dans le terminal s’exécutant dans votre conteneur docker !

2. Supprimez la méthode d'exemple générée par défaut dans votre nouveau contrôleur.

3. Créez une méthode ayant une route visant le chemin `/hello`, nommée `hello_get` et autorisant seulement la méthode `GET`. Cette méthode doit renvoyer "Hello world" à l'utilisateur. Testez votre route sur votre site.

4. Créez une deuxième méthode/route nommée `hello_get2` similaire à la première, mais permettant d'ajouter un paramètre "nom" dans le chemin et qui doit renvoyer "Hello (nom)" où le nom est celui passé dans l'URL. Testez votre nouvelle route sur votre site [https://localhost/the_feed/public/hello/Paul](https://localhost/the_feed/public/hello/Paul).

</div>

### Twig

Comme vous l'avez constaté dans la section précédente, les différentes méthodes des contrôleurs doivent retourner un objet de type `Response`. Ces réponses peuvent être variées selon le type d'application, mais dans notre cas, nous souhaitons renvoyer une page HTML à l'utilisateur.

Pour faciliter le développement de la partie `front-end`, Symfony utilise le moteur de template `twig` que vous avez déjà utilisé l'année dernière.

Les fichiers `twig` sont appelés `templates` et on les nomme généralement ainsi : `nom.format_genere.twig` (en effet, twig ne sert pas seulement à générer du HTML!). Dans notre cas, comme nous allons générer du `HTML`, nous les nommerons donc `nom.html.twig`. Ces templates sont rangés dans le sous-dossier `templates` à partir de la racine du projet.

Afin de demander à Symfony de générer une page HTML à partir d'un template `twig`, on utilise la méthode `render` disponible dans tous les contrôleurs :

```php
 #[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(): Response
{
    ...
    return $this->render("chemin_template/template.html.twig", ["param1" => ..., "param2" => ...]);
}
```

* Le premier paramètre de la méthode `render` est le chemin vers le template désiré **à partir du dossier templates**. Si par exemple, mon template se trouve simplement directement dans le dossier template, j'indique `mon_template.html.twig`. Par contre, s'il se trouve dans le sous-dossier "demo", j'indique `demo/mon_template.html.twig`.

* Le second paramètre est un **tableau associatif** qui permet de passer des données nommées directement au template, qui pourra alors les utiliser directement pour générer la page HTML. Ce paramètre est **optionnel**. On ne le spécifie donc pas s'il n'y a pas de paramètres à passer au template.

Chaque paramètre passé au template est accessible sous le même nom dans le template (le "nom" correspond à la valeur de la clé associée à la donnée dans le tableau associatif). Dans l'exemple précédent, on a alors accès dans le template à deux variables `param1` et `param2`.

Avant tout, quelques rappels sur le langage utilisé par ce moteur de templates (vous pouvez aller rapidement sur cette partie si vous vous souvenez bien des cours de l'année dernière)

* L'instruction `{{ donnee }}` permet d'afficher une donnée. Elle sera
  automatiquement échappée pour le *HTML*.

  On peut accéder à une méthode d'un objet avec `{{ donnee.methode() }}`, et à
  un attribut avec `{{ donnee.attribut }}`. *Twig* essayera d'abord de trouver
  un attribut public `$donnes->attribut`, puis appellera sinon
  `$donnes->getAttribut()`, `$donnes->isAttribut()` et `$donnes->hasAttribut()`
  (*cf.* [documentation de Twig](https://twig.symfony.com/doc/3.x/templates.html#variables)).

    ```twig
    <p>{{ donnee }}</p>
    ```

    ```twig
    <p>{{ monObjet.attribut }}</p>
    <p>{{ monObjet.methode() }}</p>
    ```

* On peut définir des variables locales : 

    ```twig
    {% set exemple = "coucou" %}
    <p>{{ exemple }}</p>
    ```

* La structure conditionnelle `if` permet de ne générer une partie du document que si une condition est remplie :

    ```twig
    {% if test %}
    Code HTML....
    {% endif %}
    ```

* Il est bien sûr possible de construire des conditions complexes avec les opérateurs : `not`, `and`, `or`, `==`, `<`, `>`, `<=`, `>=`, etc... par exemple :

    ```twig
    {% if test and (not (user.name == 'Smith') or user.age <= 20) %}
    Code HTML....
    {% endif %}
    ```

* La structure répétitive `for` permet de parcourir une structure itérative (par exemple, un tableau) :

    ```twig
    {% for data in tab %}
    <p>{{ data }}</p>
    {% endfor %}
    ```

* Si c'est un tableau associatif et qu'on veut accéder aux clés et aux valeurs en même temps :

    ```twig
    <ul>
      {% for key, value in tab %}
      <li>{{ key }} = {{ value }}</li>
      {% endfor %}
    </ul>
    ```

* On peut aussi faire une boucle variant entre deux bornes : 

    ```twig
    {% for i in 0..10 %}
        <p>{{ i }}ème valeur</p>
    {% endfor %}
    ```

* Une syntaxe `{% else %}` permet de traiter le cas particulier d'un tableau vide :  

   ```twig
   {% for data in tab %}
      <p>{{ data }}</p>
   {% else %}
   Pas de données dans le tableau
   {% endfor %}
   ```

Dans l'exemple donné plus tôt avec la méthode `render` de symfony, j'ai accès à deux paramètres `param1` et `param2`, que je peux directement utiliser dans le template.

### Pages simples

Pour vérifier que vous avez bien compris le fonctionnement basique de twig et comment l'utiliser avec Symfony, nous allons faire quelques petits exercices simples.

Parfois, après plusieurs changements dans un template, **il se peut qu'au rechargement de la page, les modifications ne soient pas visibles immédiatement** (cela est dû à la gestion du cache, notamment avec le conteneur docker que nous utilisons). Si cela vous arrive, vous pouvez exécuter la commande suivante à la racine du projet pour régler le problème :

```bash
php bin/console cache:clear
```

Ou, en abrégé :

```bash
php bin/console c:c
```

<div class="exercise">

1. Supprimez les templates `base.html.twig` et `demo/index.html.twig` créés par défaut par Symfony.
 
2. Créez trois templates `demo1.html.twig`, `demo2.html.twig` et `demo3.html.twig` dans le sous-dossier `demo`.

    Ces templates auront le squelette suivant :

    ```html
    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="UTF-8">
            <title>Demo</title>
        </head>
        <body>
            <!-- Zone à compléter par la suite -->
        </body>
    </html>
    ```

3. Le template `demo1` doit simplement afficher (dans son body) "Hello world". Modifiez votre route `hello_get` pour qu'elle affiche une page HTML générée avec ce template. Testez votre route dans votre navigateur. Vous pouvez notamment inspecter le code de la page ou bien l'afficher en entier avec `CTRL+U`.

4. Le template `demo2` doit afficher (dans son body) une variable `nom` passé en paramètre du template. Modifiez votre route `hello_get2` pour qu'elle affiche une page HTML générée avec ce template, en passant le nom récupéré dans l'URL au template via la méthode `render`.

5. Le template `demo3` doit afficher (dans son body) une liste non ordonnée (`<ul></ul>`) contenant des éléments (`<li></li>`) correspondant aux éléments d'un tableau `listeCourses` passé en paramètre du template. 
Ajouter une nouvelle route `courses` dans votre `DemoController` (en GET, avec le chemin de votre choix) qui initialise simplement (en dur) un tableau de chaînes de caractères contenant différents produits (par exemple, lait, pain, œufs...). Cette route doit renvoyer une page générée avec le template `demo3`. Vérifiez que votre liste de courses s'affiche correctement en résultat, sur votre site.

6. Modifiez le template `demo3` pour faire en sorte que si une chaîne de caractères du tableau `listeCourses` est égale à `"Livre sur Symfony"`, celle-ci s'affiche en gras. Vous pouvez faire cela en ajoutant une balise `<strong></strong>` autour du texte affiché. 

</div>

### Messages flash

Vous souvenez-vous du mécanisme des **messages flash** ? Pour rappel, il s'agit de messages informatifs stockés dans la session de l'utilisateur et affichés après chargement de la page. On peut s'en servir, par exemple, pour afficher un message d'erreur lié à un formulaire. Ou pour notifier l'utilisateur que son inscription est complète. Ces messages sont effacés une fois qu'ils ont été lus (ils ne seront donc pas ré-affichés) : c'est le sens de *flash*.

De ce côté, Symfony a tout prévu ! Il vous suffit d'appeler la méthode `addFlash` dans votre contrôleur :

```php
 #[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(): Response
{
    ...
    $this->addFlash(type, message);
    ...
}
```

* Le premier paramètre `type` est une chaîne de caractères qui correspond à la catégorie du message flash (par exemple, succès, erreur...).

* Le second paramètre est le message à afficher.

Côté `twig`, il n'y a pas besoin de passer explicitement les messages en paramètres template lors du `render()`. Ils sont directement accessibles via `app.flashes()` de la manière suivante :

```twig
{% for flashMsg in app.flashes(type) %}
    ...
    {{ flashMsg }}
    ...
{% endfor %}
```

L'instruction `app.flashes(type)` permet d'obtenir un tableau de tous les messages flash d'un type donné. On peut donc parcourir ce tableau avec `twig` et afficher les messages de la manière qu'on souhaite. Si on a plusieurs catégories de messages à afficher, il faut répéter l'opération en changeant de `type`.

<div class="exercise">

1. Modifiez simplement une de vos routes pour ajouter des messages flash de la catégorie de votre choix.

2. Modifiez le template lié à la route modifié à l'étape précédente pour afficher ces messages flash.

3. Vérifiez que tout fonctionne.

</div>

## Les publications

Maintenant que vous maîtrisez les bases, il est temps de commencer à développer concrètement notre application. Tout d'abord, nous allons créer une entité `Publication`. Nous ferons le nécessaire pour la relier à la base de données puis nous ajouterons les fonctionnalités de lecture du feed et de création d'une nouvelle publication.

### Création d'une entité

Comme pour les contrôleurs, Symfony propose une commande permettant de créer une entité de manière interactive. Elle va générer les propriétés de la classe, ainsi que les getters/setters. De plus, la commande va aussi configurer les **attributs** PHP de chaque propriété afin de préparer la synchronisation avec la base de données, via Doctrine :

```bash
php bin/console make:entity Nom
```

À chaque étape, la commande demande le nom du nouvel attribut à ajouter, puis son type (une liste des types possibles est affichable en tapant `?`), éventuellement sa taille maximum dans la base (si c'est une chaîne de caractères) et enfin, si l'attribut peut être `null` dans la base de données (on répond `y` ou `n` pour yes/no).

Il n'y a pas besoin de spécifier d'attribut `id` (c'est-à-dire, l'attribut correspondant à la clé primaire, en base). Cet attribut (entier) est généré et ajouté automatiquement sans avoir besoin de le préciser. Quand on ne souhaite plus ajouter de champs, on envoie simplement un message vide dans la console.

En plus de générer la classe de l'entité, un fichier de **repository** est également généré, permettant d'effectuer des opérations sur cette entité en base.

<div class="exercise">

1. À l'aide de la commande `make:entity`, créez une entité `Publication` respectant les contraintes suivantes :

    * Un attribut `message` de type `text` (non null).

    * Un attribut `datePublication` de type `datetime` (non null).

2. Une fois terminé, quittez la commande (en envoyant un message vide) puis allez observer le code des deux classes générées : `src/Entity/Publication` et `src/Repository/PublicationRepository`.

</div>

Dans votre nouvelle classe `Publication`, vous remarquerez les fameux `attributs` PHP au-dessus de la classe et de chaque propriété. Ces annotations de type `ORM` fixent les règles relatives à la base de données. Ce sont ces informations que `Doctrine` va lire pour créer et maintenir les différentes tables de votre base de données. Il est aussi indiqué quel repository est lié à cette entité.

De manière globale :

* L'attribut `#[ORM\Column]` indique que le champ doit être lié à une colonne dans la table correspondante. Différents paramètres sont configurables. Ici, "nullable" (définissant si la colonne peut être nulle ou non) n'est pas précisé, car il vaut `false` par défaut, et nous avons justement demandé que ces colonnes ne puissent pas être nulles.

* L'attribut `#[ORM\Id]` permet d'indiquer une propriété faisant partie de la clé primaire.

* L'attribut `#[ORM\GeneratedValue]` permet de demander à la base de données de générer automatiquement la valeur de cette colonne, par exemple, pour un entier en mode `AUTO_INCREMENT`.

Concernant la classe `PublicationRepository`, vous remarquerez que celle-ci est plutôt vide pour le moment, hormis quelques exemples commentés. En fait, toutes les opérations génériques du `CRUD` sont déjà prises en charge par la classe mère `ServiceEntityRepository` et un autre service appelé `EntityManagerInterface`. On peut néanmoins ajouter des méthodes plus spécifiques si besoin. Dans ce cas, nous ne codons pas les requêtes avec du `SQL`, mais avec un langage dérivé appelé le `DQL` (doctrine query langage). Cependant, les outils de base doctrine permettent déjà de faire des requêtes assez précises avec très peu de lignes de code.

Doctrine impose son propre langage pour assurer la compatibilité entre tous les SGBD et les autres sources de données possibles, ainsi nous ne dépendons jamais d'un SGBD ou d'une manière de stockage précise et il devient alors très facile d'en changer.

En l'état, tout est bon, il n'y a rien de plus à ajouter dans ces classes, l'entité est prête à être synchronisée !

Si dans le futur vous avez besoin d'ajouter de nouveaux champs, il suffit de ré-exécuter la commande `make:entity`. Symfony détectera que l'entité existe déjà et vous proposera d'ajouter de nouveaux champs.

### Mise en place de la base de données

Nous allons maintenant nous intéresser au fichier `.env` situé à la racine de votre projet. Ce fichier est un fichier de configuration contenant des variables d'environnement, notamment pour utiliser des services externes, comme une base de données.

Chaque variable est définie ainsi : `valeur="donnee"`

Il est aussi possible de créer un fichier `.env.local` où vous pouvez définir les mêmes variables ou bien écraser les variables déjà définies dans `.env`. Ce fichier n'est pas versionné (sur git), il peut donc servir si votre configuration locale change, d'un environnement de travail à l'autre, ou d'un développeur à l'autre. Par exemple, si chaque développeur travaille avec une base de données en local, il vaudrait mieux placer la configuration de la base de données dans le fichier `.env.local`. Dans notre cas, nous allons seulement travailler sur `.env`.

Nous nous intéressons au paramètre `DATABASE_URL`. Globalement, il se configure comme suit :

```yaml
DATABASE_URL=sgbd://username:password@ip:port/nom_base
```

La partie `sgbd` correspond au SGBD utilisé : `mysql`, `postgres`, `sqlite`, `oracle`, etc...

Vous aurez remarqué que votre **conteneur Docker** est divisé en deux sous-conteneurs :

* Le conteneur du serveur web dans lequel vous exécutez les commandes.

* Un conteneur **mysql** qui permet de gérer diverses bases de données.

Si vous jetez un œil au fichier `compose.yaml` vous observerez que le service `mysql` est nommé `db`. Une autre ligne dans le service `server` indique une dépendance avec le service `db` (section `depends_on`). Concrètement, cela veut dire que, dans le conteneur du serveur web, le service de base de données est accessible via le nom d'hôte `db` (qui se traduit par un certaine IP). Au lieu d'utiliser une `IP`, on utilisera donc le nom d'hôte `db` dans la configuration de `DATABASE_URL`.

Par rapport au nom d'utilisateur et au mot de passe, le service `db` est configuré pour avoir un utilisateur `root` dont le mot de passe est `root`. Le port utilisé est `3306`.

En résumé, pour utiliser le service de base de données inclut dans le multi-conteneur docker au travers du conteneur du serveur Web, on peut utiliser la configuration suivante :

```yaml
DATABASE_URL=mysql://root:root@db:3306/nom_base
```

Bien sûr, dans d'autres contextes, cette configuration devra être adaptée.

Sur la machine hôte (donc votre machine, hors du conteneur) le service est **exposé** via le port `3306`. On n'y accède pas via `db` mais directement via `localhost` (ou `127.0.0.1`). Cela sera utile pour visualiser et interagir avec la base au travers d'une interface (avec `PhpStorm`, `DataGrip` ou `DBeaver`) comme nous le ferons juste après.

<!--
Si vous utilisez la base `MySQL` de l'IUT, la configuration sera donc
`DATABASE_URL=mysql://login_iut:password@webinfo.iutmontp.univ-montp2.fr:3316/login_iut` en remplaçant `login_iut` et `password` avec vos identifiants (ceux utilisés sur `phpMyAdmin`), bien entendu. Si vous êtes sur votre machine personnelle (et que vous utilisez donc votre propre SGBD), généralement, le port MySQL est plutôt 3306.

Si vous êtes sur votre machine et que vous souhaitez utiliser une base de données locale (ip `localhost` ou `127.0.0.1`), il faut entrer vos identifiants liés à votre gestionnaire de serveur (par exemple XAMPP) et vous pouvez nommer la base comme bon vous semble. Notez qu'il est bien sûr possible d'utiliser la base de données de l'IUT, même si vous êtes sur votre machine locale (à condition d'être connecté à internet).

**Attention** : si votre mot de passe contient des **caractères spéciaux** (voir cadre en rouge [ici](https://symfony.com/doc/current/doctrine.html#configuring-the-database)), il faut changer la configuration du fichier `config/packages/doctrine.yaml` ainsi :

```diff
#config/packages/doctrine.yaml
- url: '%env(resolve:DATABASE_URL)%'
+ url: '%env(DATABASE_URL)%' 
```
-->

Une fois l'adresse de la base de données configurée, il faut la créer. Pour cela, il suffit d’exécuter une commande :

```bash
php bin/console doctrine:database:create
```

Si tout va bien, un message indique que la base a été créée.

Ensuite, il faut générer et exécuter une **migration**. Une migration est un fichier généré par doctrine contenant les requêtes nécessaires pour mettre à jour la structure de la base de données et aussi annuler ces modifications, si besoin ! Chaque migration est stockée dans un dossier dédié, ce qui permet de conserver un historique. Globalement, on peut dire que les migrations fournissent un gestionnaire de version de la structure de la base de données (comme un `git` pour la BD). 

<!--
**ATTENTION** : si vous effectuez votre première migration (par exemple, sur un nouveau projet) sur une base de données déjà existante (contenant des anciennes tables/données) cela va **écraser toutes les données** !. Si vous travaillez en local, pas de problème, il suffit de créer une nouvelle base et de travailler sur celle-ci. Cependant, si vous travaillez sur votre unique base MySQL de l'IUT, pensez à **exporter vos données** (s'il en reste de l'année dernière et qu'elles sont importantes) et à vider votre base. Il est possible de paramétrer `doctrine` afin d'ignorer certaines tables selon une expression régulière, mais nous ne verront pas cela dans le cadre de ce TD.
-->

Pour générer puis exécuter une migration, on utilise les deux commandes suivantes :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

On doit effectuer une migration dès que l'on crée ou que l'on modifie une entité existante (nom des attributs, assertions de type `ORM` modifiés...) afin de garder la structure de la base de données à jour.

Afin de visualiser votre base de données et interagir avec, deux solutions s'offrent à vous :

* Si vous utilisez `PHPStorm`, vous pouvez cliquer sur le bouton `Database` dans la barre latérale droite, puis ajouter une nouvelle connexion `MySQL` :

    {% endraw %}
    ![database-phpstorm-1]({{site.baseurl}}/assets/TD1/database-phpstorm-1.png)
    {% raw %}

    Ensuite, il faut configurer la connexion comme suit :

    {% endraw %}
    ![database-phpstorm-2]({{site.baseurl}}/assets/TD1/database-phpstorm-2.PNG)
    {% raw %}

* Sinon, vous pouvez utiliser un autre logiciel de gestion de bases de données : `DataGrip`, `DBeaver`, etc.

    La configuration est :

    * **IP** : `localhost`

    * **Utilisateur** : `root`

    * **Mot de passe** : `root`

    * **Port** : `3306`

    * **Nom de la base** : `the_feed` (ou autre si vous aviez mis un autre nom)

<div class="exercise">

1. Configurez le paramètre `DATABASE_URL` dans le fichier `.env` (remplacez celui déjà présent) afin de connecter votre application à votre service de base de données. Donnez le nom que vous souhaitez à votre base (par exemple, `the_feed`).

2. Exécutez la commande `doctrine:database:create` afin de créer la base de données.

3. Utilisez les commandes nécessaires afin de créer et d'exécuter votre première migration.

4. Connectez-vous à l'interface de gestion de votre SGBD (avec `PHPStorm` ou un autre logiciel) et observez. Vous devriez trouver votre nouvelle table `publication`!

</div>

### Contrôleur et template de base

Nous allons maintenant créer la route (et la page) qui se chargera d'afficher la liste des publications. Il s'agira en fait de la page d'accueil du site. 

Dans le prochain exercice, vous allez utiliser de fausses publications générées à la main dans le contrôleur, puis vous adapterez un template `twig` afin d'afficher ces publications. Il n'y a pas encore de `CSS`, mais cela viendra juste après.

Vous allez aussi être amené à utiliser des **filtres** de `twig`. Les **filtres** permettent de convertir une donnée en autre chose. On les utilise ainsi : `donnee|filtre(param1, param2, ...)`.

* Par exemple, le filtre `lower` permet de convertir une chaîne de caractères en minuscules.

* Le filtre `capitalize` permet de convertir la première lettre d'une chaîne en majuscule.

* Le filtre `date(format)` permet d'afficher une date selon un format désiré. Un exemple de format est `"Y-m-d"` pour avoir un affichage du style "2023-09-01".

Par exemple : 

```twig
<p> {{ texte|capitalize }}</p>
<p> {{ user.dateDeNaissance|date(...) }}</p>
```

Vous pouvez retrouver l'ensemble des filtres disponibles sur [cette page](https://twig.symfony.com/doc/3.x/filters/index.html).

<div class="exercise">

1. Créez un nouveau contrôleur `PublicationController` (vous pouvez encore une fois utiliser la commande `make:controller`, mais attention, des fichiers de templates sont générés par défaut). Ajoutez une nouvelle route nommée `feed`, pointant sur le chemin `/` (racine du site) et accessible en `GET` seulement (pour le moment). Pour le moment, n'ajoutez rien dans le corps de la méthode, nous le ferons juste après.

2. Dans le dossier `templates`, créez un dossier `publication` (s'il n'existe pas déjà), puis, à l'intérieur de ce nouveau dossier, créez un template `feed.html.twig` contenant le squelette suivant :

    ```html
    <!DOCTYPE html>
    <html lang="fr">
        <head>
            <title>The Feed</title>
            <meta charset="utf-8">
        </head>
        <body>
            <header>
                <div id="titre" class="center">
                    <a href=""><span>The Feed</span></a>
                    <nav>
                        <a href="">Accueil</a>
                    </nav>
                </div>
            </header>
            <main id="the-feed-main">
                <div id="feed">
                    <!-- Pour chaque publication, afficher (avec une boucle) le code HTML ci-dessous !-->
                        <div class="feedy">
                            <div class="feedy-header">
                            <a href="">
                                <img class="avatar"
                                src=""
                                alt="avatar de l'utilisateur">
                            </a>
                            <div class="feedy-info">
                                <span>Anonyme</span>
                                <span> - </span>
                                <span><!-- Date de la publication sous le format jour/mois/annee --></span>
                                <p><!-- Message de la publication --></p>
                            </div>
                        </div>
                    </div>
                    <!-- Fin de la boucle -->
                </div>
            </main>
        </body>
    </html>
    ```

    Nous le compléterons dans une prochaine étape.

3. Dans la méthode liée à votre route `feed`, construisez trois "fausses" publications et placez-les dans un tableau. Générez puis renvoyez une page HTML construite avec le template créé à l'étape précédente, en passant votre tableau de publications en paramètre.

    Pour construire vos publications, vous pouvez notamment utiliser les **setters** :

    ```php
    // A importer au début da la classe
    use App\Entity\Publication;

    // Dans une méthode, création d'une publication...
    $publication1 = new Publication();
    $publication1->setMessage("Coucou");
    $publication1->setDatePublication(new \DateTime());
    ```

    Il faudra importer la classe `Publication` dans le contrôleur (comme montré ci-dessus). Normalement, PHPStorm vous propose de vous le faire, en surlignant le nom de la classe en jaune.

    Il ne faut surtout pas modifier les paramètres du constructeur de la classe `Publication`. Votre ORM a besoin que ce constructeur ne prenne aucun paramètre.

4. Dans ce template, remplacez toutes les sections commentées afin d'afficher correctement la liste des publications, passée depuis le contrôleur.

    Concernant la **date de publication**, il faut la convertir une chaîne de caractères en appliquant un filtre. Le format souhaité est `d/m/Y` (jour, mois, année).

5. Testez votre route et vérifiez que vos fausses publications s'affichent bien (il n'y a pas de style ni d'image de profil pour le moment, c'est normal).

</div>

Tout cela manque un peu de style ! Et d'image de profil pour les publications ! Comme nous n'avons pas encore d'utilisateurs, nous allons utiliser une image "anonyme".

En Symfony, la gestion des **assets** (images, fichiers css, js, etc...) peut être effectuée de différentes manières :

* En plaçant simplement les assets dans le dossier `public`.

* En utilisant une librairie symfony (`bundle`) dédiée (par exemple, **Webpack Encore**).

* En utilisant le récent système `AssetMapper` installé par défaut dans une application web symfony (cela se passe au niveau du dossier `assets`).

Le système `AssetMapper` propose divers avantages **lors du développement** :

* Le versionning des fichiers : les noms de fichiers sont associés à un suffixe lors du chargement de la page, liés à leurs versions. Si un changement intervient dans un des fichiers (par exemple, modification du js ou du css), le suffixe changera et le navigateur détectera donc qu'il doit recharger ce fichier (utile si le navigateur met en cache certains fichiers).

* L'utilisation de librairies "clientes" externes disponibles sur `npm` (node package manager) sans avoir besoin d'installer `npm` sur votre machine. L'import est aussi très facile. Il en va de même pour les différents frameworks css.

* On peut créer différentes configurations et charger celle qu'on souhaite sur une page donnée, ou même plusieurs.

Cependant, pour que tout cela soit automatique, il faut obligatoirement utiliser le serveur web de Symfony pendant le développement. Sinon il faudra exécuter une commande chaque fois qu'on voudra mettre à jour un fichier.

De plus, si on souhaite faire des choses plutôt simples et basiques (comme cela va être le cas dans ce TP), la mise en place et la configuration de ce système peut être un peu lourde.

Bref, nous allons plutôt opter pour la solution la plus "simple" dans le cadre de ce TP : utiliser le dossier `public` à la racine du projet. Nous allons placer toutes nos ressources (css, js, images) dans ce dossier.

Lors de la mise en **production**, même si on utilise le `AssetMapper` l'objectif final est d'avoir nos **assets** dans le dossier `public`. Si on utilise donc ce système, il suffit d'exécuter une commande pour tout "compiler" et copier dans le dossier en question.

Dans un template `twig`, on construit le chemin vers chaque asset en utilisant la fonction `{{ asset(chemin) }}` (dans un bloc twig permettant d'afficher des données). Pour le chemin à spécifier, la racine se trouve directement dans le dossier `public`, on indique donc un sous-chemin à partir de ce dossier. Cela marche avec tous les systèmes (qu'on utilise `AssetMapper` ou directement le dossier `public`).

Par exemple, si je possède le fichier suivant : `public/exemple/coucou.jpg`, je peux construire le chemin vers cette image en utilisant l'instruction : `{{ asset("exemple/coucou.jpg") }}` dans mon template (typiquement, dans la partie `src`).

<div class="exercise">

1. Créez un dossier `css` dans `public` et importez la feuille de style {% endraw %}[styles.css]({{site.baseurl}}/assets/TD1/styles.css){% raw %} (clic-droit puis "Enregistrer la cible du lien...") à l'intérieur de ce nouveau répertoire.

2. Créez un dossier `img` dans `public`, puis `utilisateurs` dans `img` et importez l'image {% endraw %}[anonyme.jpg]({{site.baseurl}}/assets/TD1/anonyme.jpg){% raw %} (clic-droit puis "Enregistrer la cible du lien...") à l'intérieur du répertoire `utilisateurs`.

3. Dans votre template `feed.html.twig` :

    * Dans la section `head`, ajoutez et complétez la ligne suivante afin d'importer notre nouvelle feuille de style :

    ```html
    <link rel="stylesheet" type="text/css" href="A compléter!">
    ```
    * Faites en sorte d'afficher l'image anonyme sur chaque publication (il faut ajouter un attribut `src` sur la balise `img` de chaque publication).

4. Testez. C'est un peu mieux, non ?

</div>

Enfin, il reste un problème auquel nous allons faire face : construire les liens vers nos autres pages (notamment pour le menu de navigation).

Pour gérer cela, symfony propose d'utiliser la fonction `path('nomRoute')` dans twig. Cette fonction permet de générer le chemin de la route passée en paramètre.

Par exemple, si j'ai une route nommée `exemple` ayant pour chemin `/exemple/test/bonjour`, alors le template twig suivant

```twig
<a href="{{ path('exemple') }}">Mon lien</a>
```

générera la balise `<a>` suivante

```html
<a href="/exemple/test/bonjour">Mon lien</a>
```

Cela permet notamment de changer très facilement de chemin pour une route, si besoin, sans avoir besoin de modifier nos templates twig. Par contre, si le nom de la route change, il faudra mettre à jour les appels à `path` utilisant cette route dans nos templates.

De manière générale, chaque fois que vous aurez besoin de créer un lien interne à l'application dans un template, vous utiliserez toujours `path`.

<div class="exercise">

1. Dans le template `feed.html.twig`, utilisez `path` pour les deux liens "The Feed" et "Accueil" en pointant sur la route `feed`.

2. Rechargez la page et vérifiez que le lien est bien généré (un simple `/` car c'est le chemin associé à la route `feed`).

</div>

### Affichage de la liste des publications contenues dans la base

Au lieu de nous contenter de nos "fausses" publications, nous allons directement charger les publications depuis notre base de données ! Pour cela, nous devons utiliser la classe `PublicationRepository`.

Il est temps de faire un point sur les méthodes essentielles disponibles (par défaut) avec un repository lié à une entité :

* `findAll()` : renvoie toutes les entrées de l'entité sous la forme d'un tableau d'objets (du type de l'entité).

* `find($id)` : renvoie l'entrée de l'entité dont l'id passé en paramètre correspond à la valeur de sa clé primaire. L'objet renvoyé correspond au type de l'entité.

* `findBy($criteres, $tri)` : renvoie toutes les entrées de l'entité sous la forme d'un tableau d'objets (du type de l'entité) respectant tous les critères passés en paramètres et ordonnés selon les attributs précisés.

    * `$criteres` correspond à un tableau associatif qui associe des attributs de l'entité à une valeur souhaitée. En fait, cela correspond à un `WHERE column1 = ... AND column2 = ...`. S'il n'y a aucun critère de sélection, on donne un tableau vide `[]`. Dans ce cas, toutes les entités seront alors sélectionnées.

    * `$tri` (optionnel) correspond aussi à un tableau associatif qui liste les attributs selon lesquels on veut que les résultats soient triés, associés au sens (`DESC` ou `ASC`). Si on ne veut pas faire de tri, on ne précise pas ce paramètre.

    Par exemple, si j'ai une entité "Livre" possédant notamment une année de publication, un auteur et un genre, et que je souhaite trouver tous les livres de fantasy écrits par J.R.R Tolkien, ordonnés par année de publication de manière croissante, je peux utiliser :

    ```php
    $livres = $livreRepository->findBy(["genre" => "Fantasy", "auteur" => "J.R.R Tolkien"], ["anneePublication" => "ASC"]);
    ```

    Note importante : on utilise le nom des attributs de la classe de l'entité, pas ceux de la base de données (qui peuvent être nommés sous un autre format).

* `findOneBy($criteres)` : même chose que `findBy` sauf qu'elle renvoie le premier objet correspond aux critères (et non pas un tableau de plusieurs entités). Utile si on est sûr d'obtenir une entité précise selon les critères recherchés.

Ces repositories fournissent seulement des opérations de **lecture**. Les opérations de création, de modification et de suppression sont confiées à un **service** appelé `EntityManagerInterface` (dont nous reparlerons plus tard).

Mais, comment utiliser ce repository dans votre contrôleur ? Avec de l'injection de dépendances bien sûr ! Et avec Symfony, cela fonctionne très simplement grâce à un système appelé **autowiring**.

Globalement, dans votre contrôleur, dès que vous avez besoin d'un service (repositories ou autre) dans une de vos méthodes, vous avez juste à l'ajouter comme paramètre (en précisant son type) de la méthode et... c'est tout !

Par exemple, si je veux accéder à une instance de `PublicationRepository` et de `EntityManagerInterface`, j'ai juste à faire :

```php
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;

 #[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(PublicationRepository $repository, EntityManagerInterface $entityManager): Response
{
    //Je peux utiliser $repository et $entityManager, Symfony se charge de les injecter pour moi...
}
```

**Il faut donc bien préciser le type de la classe/service souhaité dans la fonction et penser à l'importer en début de fichier.**

Tout cela fonctionne sur la base d'un **conteneur IoC** que vous aviez déjà utilisé l'année dernière, configuré et géré par Symfony. **L'autowiring** est un système permettant de détecter et d'injecter automatiquement les dépendances. Et cela ne se limite pas qu'aux contrôleurs ! Il est possible d'injecter des services dans d'autres classes (généralement via le constructeur) et il est aussi très facile de construire ses propres services et de les utiliser de la même façon, comme vous le ferez un peu plus tard.

À noter que, si vous avez besoin d'un service qui est utilisé dans chaque route de votre contrôleur (ou quasiment partout), vous pouvez l'importer une seule fois de manière globale, en utilisant le **constructeur** du contrôleur :

```php
use App\Repository\PublicationRepository;

public function __construct(private PublicationRepository $publicationRepository)
{}

#[Route('/exemple', name: 'route_un_exemple_get', methods: ["GET"])]
public function methodeUnExempleGet(): Response
{
    $result = $this->publicationRepository->methodeA(...);
}

#[Route('/exemple', name: 'route_deux_exemple_get', methods: ["GET"])]
public function methodeUnExempleGet(): Response
{
    $this->publicationRepository->methodeB(...);
}
```

Avec cette configuration, dès que le contrôleur est chargé, le repository sera chargé aussi (il ne faut donc plus l'injecter dans chaque méthode). Il faut donc être sûr qu'il va bien être utile à quasiment toutes nos méthodes. Si ce n'est pas le cas, il est alors plus optimisé de l'injecter seulement là où on en a besoin.

<div class="exercise">

1. Éditez votre base de données (via `PHPStorm` ou autre) en ajoutant quelques publications avec des dates différentes.

2. Modifiez le code associé à votre route `feed` (dans `PublicationController`) : supprimez vos "fausses" publications de tests et à la place, récupérez le tableau de publications directement depuis la base de données, en utilisant `PublicationRepository`.

3. Testez votre route et vérifiez que les publications s'affichent bien.

</div>

Tout fonctionne ? Très bien ! Mais il y a un petit souci : dans un réseau social, les publications sont généralement affichées de la plus récente à la plus ancienne. Hors, ici, c'est l'inverse.

Dans votre contrôleur, vous avez sans doute utilisé la méthode `findAll`, hors, celle-ci ne permet pas trier les résultats. Il serait alors peut-être plus judicieux d'utiliser `findBy`. Il est possible d'utiliser cette méthode en précisant un tableau vide pour les critères. Cela aura pour effet de renvoyer tous les résultats, mais comme nous l'avons vu plus tôt, le deuxième paramètre vous permet de préciser l'attribut de tri.

<div class="exercise">

1. Modifiez votre route `feed` afin que la base de données renvoie les publications triées par ordre décroissant de la date de publication.

2. Testez et vérifiez que l'ordre est respecté.

3. Comme ce bout de code pourrait resservir, créez plutôt une méthode `findAllOrderedByDate` dans la classe `src/Repository/PublicationRepository` qui renvoie les publications triées, comme vous l'avez fait dans l'étape 1. Utilisez cette méthode dans votre route `feed` et vérifiez que tout fonctionne toujours.

    Ci-dessous, le squelette de cette méthode : 

    ```php
    public function findAllOrderedByDate() : array {
        //A compléter
    }
    ```

    Pour rappel : `PublicationRepository`, grâce à l'héritage, possède les méthodes `find`, `findAll`, etc.

</div>

Maintenant que nous pouvons afficher les publications, il est temps de pouvoir en créer directement depuis le site !

### Formulaire de validation

Comme vous le savez, qui dit création d'une entité, dit formulaire et validation des données transmises au serveur. Ici aussi, Symfony nous propose des outils permettant de simplifier plusieurs étapes de ce processus avec un type d'objet permettant de générer, gérer et valider les formulaires de notre site.

De manière générale, on va créer des formulaires liés à une entité (dans notre cas, Publication), mais si besoin, il est aussi possible de créer des formulaires indépendants.

#### Formulaire de validation -- Côté PHP

Pour créer un formulaire lié à une entité, on utilise la commande `make:form` :

```bash
php bin/console make:form FormulaireType Entity
```

* Le premier paramètre correspond au nom de la classe de formulaire créée (généralement, le nom de l'entité puis `Type`).

* Le second paramètre correspond au nom de l'entité à laquelle le formulaire est relié.

La commande génère la classe du formulaire dans le dossier `src/Form`.

De base, la classe générée se présente sous cette forme : 

```php
class ExempleType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('champ1')
            ->add('champ2')
            ->add('champ3')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exemple::class,
        ]);
    }
}
```

* La première méthode liste les champs disponibles dans le formulaire. Par défaut, tous les champs de la classe cible sont inclus (ce qui n'est pas forcément souhaitable !). Il est alors possible de configurer des options pour chaque champ.

* La seconde méthode configure certains aspects du formulaire, comme la classe/entité cible, s'il y en a une.

La fonction `add` du builder permet de configurer :

1. Le nom du champ. Attention, si ce champ est lié à une entité, il faut qu'il porte exactement le même nom que la propriété correspondante dans la classe cible.

2. Le type du champ (au sens d'un formulaire HTML), par exemple, `TextType`, `PasswordType`, etc... cela permettra de générer le code HTML de certaines parties du formulaire très facilement !

3. Des options éventuelles, dont nous reparlerons plus tard.

Par exemple, si je souhaite créer un champ de type "mot de passe", je vais utiliser `PasswordType` ainsi :

```php
$builder
    ->add('motDePasse', PasswordType::class);
```

Il faut aussi penser à ajouter un **bouton d'envoi du formulaire** de type `SubmitType` :

```php
$builder
    ->add('valider', SubmitType::class);
```

<div class="exercise">

1. Utilisez la commande `make:form` pour créer une classe de formulaire appelée `PublicationType` reliée à l'entité `Publication`.

2. Observez le code généré.

3. Supprimez le champ lié à la date de publication : celle-ci sera générée automatiquement côté serveur, et pas fournie par l'utilisateur.

4. Configurez le type du champ `message` pour lui assigner `TextareaType::class` (zone textuelle, avec plusieurs lignes, correspondant donc à `<textarea></textarea>` en HTML). 

    Il faudra importer la classe suivante :

    ```php
    use Symfony\Component\Form\Extension\Core\Type\TextareaType;
    ```

5. Ajoutez aussi un champ `publier` de type `SubmitType::class` (bouton d'envoi du formulaire).

    Il faudra importer la classe suivante :

    ```php
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;
    ```
</div>

#### Formulaire de validation -- Côté Twig

Votre formulaire est prêt à être utilisé ! Nous allons d'abord commencer par l'afficher sur notre page web.

Comme d'habitude, tout se passe au niveau du contrôleur. Pour créer le formulaire et le passer à notre template, on procède comme suit, dans la méthode liée à la route dont la page affichera le formulaire :

```php
use App\Form\ExempleType;

 #[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(): Response
{
    //On initialise l'objet qui sera lié au formulaire.
    //En cas de mise à jour d'une entité, cela permet de précharger les données dans le formulaire.
    $exemple = new Exemple();

    $form = $this->createForm(ExempleType::class, $exemple, [
        //On précise la méthode utilisée par le formulaire (GET, POST, ...)
        'method' => '...',
        //On précise l'URL vers lequel le formulaire sera envoyé.
        //La méthode generateURL permet de générer une URL à partir du nom d'une route (pas son chemin!) 
        'action' => $this->generateURL('nomRoute')
    ]);

    ...

    //On passe le formulaire comme paramètre du template.
    return $this->render(..., ["formulaireExemple" => $form, ...]);
}
```

Du côté de `twig`, on peut alors générer le formulaire en utilisant plusieurs fonctions :

* `form_start(formulaireExemple, {'attr' : {'id' : '...', 'class' : '...'}})` : permet de générer la balise ouvrante du formulaire. La partie `attr` permet de configurer des **attributs HTML** supplémentaires : (identifiants HTML, classes...)

* `form_end(formulaireExemple)` : génère la balise fermante du formulaire.

* `form_widget(formulaireExemple.nomChamp, {'id' : '...', 'attr' : {'class' : '...'}})` : permet de générer la balise HTML correspondant au champ (en utilisant le nom du champ de la classe du formulaire). On peut aussi configurer ses attributs HTML, son id, ses classes, etc...

* `form_rest(formulaireExemple)` : permet de générer ce qu'il "reste" : généralement, un champ caché appelé **token CSRF**. Ce token est généré aléatoirement à chaque affichage de formulaire permet de se protéger d'une attaque appelée **cross site request forgery** ou plus simplement `CSRF`. Cette attaque consiste à vous faire exécuter une requête sur un site cible depuis un site extérieur.

La variable `formulaireExemple` correspondant au nom de la variable associée au formulaire dans le tableau associatif passé au template par le contrôleur.

Vous noterez que dans le cas de `form_widget`, l'identifiant ne se place pas dans `attr`, contrairement à `form_start`. De manière assez générale, tous les attributs "normaux" d'un **input** de formulaire (par exemple `placeholder`) se placeront à l'intérieur de `attr`.

Attention, dans le cas du bouton d'envoi du formulaire, on l'affiche aussi avec `form_widget`, et on configure le message contenu dans le bouton avec le paramètre `label` : 

```twig
{{ form_widget(formulaireExemple.nomChampSubmit, {'label' : "Envoyer"}) }}
```

Voici une petite démonstration, avec l'exemple de formulaire précédent (contenant un champ `motDePasse` et un champ `valider`, correspondant au bouton d'envoi) :

```twig
{{ form_start(formulaireExemple, {'attr': {'id' : 'monForm'}}) }}
    <div class="form-elt">
        {{ form_widget(formulaireExemple.motDePasse, {'id': "mdp", 'attr' : {"placeholder": "Trouvez un mot de passe sécurisé!"}}) }}
    </div>
        {{ form_widget(formulaireExemple.valider, {'id': "exemple-submit", 'label' : "Valider le formulaire"}) }}
    {{ form_rest(formulaireExemple) }}
{{ form_end(formulaireExemple) }}
```

Concernant l'attribut **method** et **action** du formulaire, ils sont définis dans le contrôleur, lors de l'appel de la méthode `createForm`, comme montré dans un précédent exemple.

<div class="exercise">

1. Modifiez le code de votre route `feed` afin d'initialiser le formulaire de création d'une publication et de le passer au template. Il utilisera la **méthode** `POST` et l'adresse de son **action** visera la route `feed`.

2. Dans votre template `feed.html.twig`, au tout début du `div` d'identifiant `feed` insérez et complétez le template suivant :

    ```twig
    <!-- Génération de la balise <form>, possédant un id (HTML) "feedy-new" -->
    {{ form_start(...) }}
        <fieldset>
            <legend>Nouveau feedy</legend>
            <div>
                <!-- Le textarea généré, avec le placeholder "Qu'avez-vous en tête?" -->
                {{ form_widget(...) }}
            </div>
            <div>
                <!-- Le bouton de validation, possédant l'id "feedy-new-submit" et le label "Feeder!" -->
                {{ form_widget(...) }}
            </div>
        </fieldset>
    <!-- Génération des balises restantes (token CSRF) -->
    {{ form_rest(...) }}
    <!-- Génération de la balise fermante </form> -->
    {{ form_end(...) }}
    ```

    Pour rappel, le nom de votre formulaire correspond à celui que vous avez passé en paramètre du template dans votre contrôleur.

2. Rechargez votre page, le formulaire devrait maintenant s'afficher !

</div>

Il y a [d'autres méthodes utiles](https://symfony.com/doc/current/form/form_customization.html#form-field-helpers) que vous pourriez utiliser. En fait, l'intégralité du formulaire peut être générée sans écrire de HTML (on peut même utiliser une boucle), même pour les attributs liés aux balises html (id, class...). Néanmoins, Symfony nous permet de garder la main sur certains aspects, et ainsi, choisir ce qui est généré automatiquement ou non, si on souhaite customiser certaines parties. 

Afin de garder une séparation entre les données du formulaire, sa validation et son affichage, nous allons choisir de ne pas coder les aspects liés au "style" (classes, id) dans les classes définissant les formulaires, et de plutôt les définir (si besoin) nous-même dans le template twig, à l'aide du paramètre `attr`.

Si on utilise des frameworks `css` (comme bootstrap), il est facile de demander à Symfony de générer tous les formulaires en utilisant les classes de bootstrap, pour le style des inputs. Ainsi, dans certains cas, on peut générer tout le formulaire (avec `form(nomFormulaire)`) sans avoir besoin de le customiser. On peut également définir nos propres styles.

### Création d'une publication

Maintenant que vous savez générer et afficher un formulaire, vous allez pouvoir le traiter et le valider côté `back-end`.

Pour cela, on regroupe généralement la route/action qui affiche (GET) et traite (POST) le formulaire sur la même méthode, dans le contrôleur :

```php
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/exemple', name: 'route_exemple', methods: ["GET", "POST"])]
public function methodeExemple(Request $request, EntityManagerInterface $entityManager): Response
{

    $exemple = new Exemple();
    $form = $this->createForm(ExempleType::class, $exemple, [
        'method' => '...',
        'action' => $this->generateURL('nomRoute')
    ]);

    //Traitement du formulaire
    $form->handleRequest($request);
    if($form->isSubmitted() && $form->isValid()) {
        // À ce stade, le formulaire et ses données sont valides
        // L'objet "Exemple" a été mis à jour avec les données, il ne reste plus qu'à le sauvegarder
        $entityManager->persist($exemple);
        $entityManager->flush();

        //On redirige vers la page suivante
        return $this->redirectToRoute('maRoute');
    }

    return $this->render(..., ["formulaireExemple" => $form, ...]);
}
```

* On a ajouté la méthode `POST` dans le paramètre `methods` de l'attribut `Route`.

* On a ajouté la requête `Request $request` dans les paramètres de la méthode.

* La méthode `handleRequest` permet de remplir le formulaire avec les données envoyées par le client et également affecter les champs correspondants à l'objet traité par le formulaire s'il y en a un.

* La méthode `isSubmitted` vérifie que le formulaire a bien été soumis **et que sa méthode correspond bien à la méthode de l'objet formulaire** qui est `POST` par défaut. En d'autres termes, par défaut, si on soumet un formulaire sans méthode `POST`, et si l'objet correspondant au formulaire n'a pas d'options particulières, `isSubmitted` renverra `false`.

* La méthode `isValid` permet de vérifier que les données soumises sont valides, au niveau des contraintes placées par le développeur (nous y reviendrons juste après).

* L'objet `EntityManagerInterface $entityManager` permet de sauvegarder l'objet dans la base de données. La méthode `persist` prépare la sauvegarde de l'objet et `flush` l'exécute. Vous noterez qu'on utilise ici aussi l'injection de dépendances par autowiring!

* Il est possible de séparer l'affichage et le traitement du formulaire en deux méthodes distinctes, mais tout regrouper dans une seule méthode permet de conserver les données du formulaire et ainsi préremplir les champs si le formulaire doit être ré-affiché, en cas d'erreur.

* Après enregistrement, on redirige vers une autre route en utilisant la méthode `redirectToRoute`. Cela peut éventuellement être la même route.

Cependant, dans notre cas, il reste un problème : quand et comment la date de publication va être générée si l'utilisateur ne la transmet pas dans le formulaire ?

Dans certains cas, des attributs d'une classe donnée doivent être générés automatiquement avant d'être enregistrés dans la base, comme pour notre date de publication. Pour cela, il suffit d'ajouter un attribut `#[ORM\HasLifecycleCallbacks]` à votre classe-entité puis une méthode (du nom que vous souhaitez) en lui affectant l'attribut `#[ORM\PrePersist]` :

```php
#[ORM\Entity(repositoryClass: ExempleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Exemple {

    ...

    #[ORM\PrePersist]
    public function prePersistExempleChamp() : void {
        $this->exempleChamp = ...;
    }

    ...

}
```

<div class="exercise">

1. Dans votre classe `Publication`, ajoutez une méthode `prePersistDatePublication` permettant d'initialiser la date de publication avant son enregistrement en base. Pour rappel, la date de publication peut simplement s'initialiser avec un `new \DateTime()`.

2. Dans votre route `feed`, ajoutez le code pour traiter le formulaire et créer la publication (on regroupe à la fois l'affichage du feed et du formulaire, pour le `GET` et son traitement, pour le `POST`). Après sauvegarde, on redirige vers la même route : `feed` (on doit faire cela, sinon les données du formulaire ne seront pas effacées lors de l'affichage de la page...). N'oubliez pas qu'il faut autoriser **une autre méthode** dans la configuration de votre route !

3. Rechargez la page principale et tentez d'ajouter des nouvelles publications. Vos publications devraient s'afficher sur votre feed.

</div>

Comme expliqué plus haut, nous aurions aussi pu diviser ce traitement en deux méthodes, mais sans pouvoir préremplir le formulaire en cas de données invalides :

```php
#[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(): Response
{
    $exemple = new Exemple();
    $form = $this->createForm(ExempleType::class, $exemple, [
        'method' => '...',
        'action' => $this->generateURL('nomRoute')
    ]);
    return $this->render(..., ["formulaireExemple" => $form, ...]);
}

#[Route('/exemple', name: 'route_exemple_post', methods: ["POST"])]
public function methodeExemple(Request $request, EntityManagerInterface $entityManager): Response
{
    $exemple = new Exemple();
    $form = $this->createForm(ExempleType::class, $exemple);
    $form->handleRequest($request);
    if($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($exemple);
        $entityManager->flush();
    }

    return $this->redirectToRoute('route_exemple_get');
}
```

Nous aurions pu afficher la même vue que dans `route_exemple_get` (au lieu de rediriger) pour résoudre ce problème, en passant le formulaire à la vue. Cependant, notre route `route_exemple_get` exécute potentiellement d'autres actions qui sont nécessaires à l'affichage de notre vue (dans notre cas, récupérer toutes les publications) et il faudrait alors recopier toutes ces actions dans `route_exemple_post`. On obtiendrait alors plus ou moins un copié/collé de `route_exemple_get` et on sortirait du cadre logique de l'action `POST`. C'est pour cela que nous préférons plutôt rediriger vers la route (en `GET`) affichant la page. Mais dans ce cas, les données formulaire sont "perdues" et les champs ne seront pas préremplis en cas d'erreur.

### Assertions (contraintes)

Vous savez maintenant comment créer des publications via un formulaire, mais pour l'instant, vous ne vérifiez pas vraiment les données qui sont soumises. Comment faire, par exemple, pour limiter la taille du message ? Est-ce que cela se fait avec le contrôleur ? Pas du tout, pour cela, Symfony a prévu un système appelé **assertions**.

Une **assertion** est une contrainte liée à un champ du formulaire ou une propriété de l'entité. Quand un formulaire est soumis, Symfony va vérifier que toutes les contraintes sont respectées. Si une ou plusieurs annotations ne sont pas validées, des erreurs sont générées (dans le contrôleur, on vérifie cela avec `$form->isValid()`).

Du côté de l'entité, on peut directement placer des assertions au niveau de champ propriété de la classe, en utilisant des **attributs PHP**.

Cet attribut se présente ainsi : `#[Assert\...(param1 : ..., param2: ...)]`.

Quelques exemples :

* `#[Assert\Email]` : vérifie que la propriété est une adresse email bien formatée.

* `#[Assert\Count(min : x, max: y)]` : vérifie que la collection possède bien entre `x` et `y` éléments.

* `#[Assert\Length(min: x, max: y)]` : vérifie que la propriété a une taille entre `x` et `y` caractères.

* `#[Assert\Regex(pattern: ...)]` : vérifie que la propriété vérifie l'expression régulière spécifiée.

* `#[Assert\NotBlank]` : vérifie que la propriété a bien été transmise par le formulaire et possède une valeur.

* `#[Assert\NotNull]` : vérifie que la propriété n'est pas nulle (du côté de l'application). Cela signifie que la propriété est présente (transmise par le formulaire), et n'a pas la valeur `null`. Cela peut paraître redondant avec le fait que la propriété ne peut pas être nulle dans la base, mais avec cette assertion la vérification est faite au niveau de l'application et non pas du côté de la base.

Bref, il en existe des tas. Sur la plupart des assertions, on peut aussi ajouter un paramètre `message` pour préciser un message d'erreur personnalisé en cas d'échec de validation. Il est bien sûr possible d'apposer plusieurs attributs d'assertions au-dessus d'une propriété. Vous pouvez retrouver la liste des types d'assertions disponibles [ici](https://symfony.com/doc/current/reference/constraints.html).

Au niveau `PHP`, il faut importer les assertions ainsi :

```php
use Symfony\Component\Validator\Constraints as Assert;
```

Un exemple avec la classe d'une entité :

```php
use Symfony\Component\Validator\Constraints as Assert;

class Exemple {

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, minMessage: 'Il faut au moins 2 caractères!')]
    private ?string $champ1 = null;

}
```

Au niveau des classes de type formulaire, on peut aussi ajouter des propriétés au niveau des champs (qui ne sont pas liés à l'entité) en utilisant la **classe de l'assertion** ainsi :

```php
class ExempleType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ lié à ma classe Exemple -> l'assertion est dans la classe Exemple
            ->add('champ1', TextType::class)

            // Champ qui n'est pas lié à mon entité Exemple
            // On rajoute l'option "mapped => false" pour dire que ce champ n'appartient pas à la classe "Exemple"
            ->add('champ2', TextType::class, [
                "mapped" => false,
                "constraints" => [
                    new NotBlank(),
                    new NotNull(),
                    new Regex(pattern : '...', message : 'Format non respecté')
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exemple::class,
        ]);
    }
}
```

<div class="exercise">

1. Dans votre classe `Publication` rajoutez :

    * Une assertion pour vérifier que le message n'est pas blanc (non transmis) et une autre pour vérifier qu'il n'est pas `null`.

    * Une assertion vérifiant que la taille du message est comprise entre 4 et 200 caractères. Spécifiez également les paramètres `minMessage` et `maxMessage` pour configurer les messages d'erreurs si le contenu est trop court ou trop long.

2. Essayer de créer (sur le site) une publication avec moins de 4 caractères ou plus de 200. Vérifiez qu'elle ne s'affiche pas après soumission du formulaire.

</div>

Les messages d'erreurs ne s'affichent pas encore, c'est normal, nous allons régler cela à la prochaine étape.

### Gestion des erreurs

Les erreurs du formulaire sont générées et stockées dans l'objet lié au formulaire lors de l'appel à la méthode `handleRequest`. Il est donc possible de s'en servir pour les afficher dans le template via ce même objet.

Néanmoins, dans notre cas, nous allons plutôt utiliser ces messages comme des messages flash.

De manière générale, nous allons définir deux types de messages flash :

* `success` : quand l'utilisateur a terminé un processus avec succès (par exemple, inscription/connexion...)

* `error` : pour tout type de messages d'erreurs (notamment, ceux liés aux formulaires...)

Dans notre template `twig`, on utilisera le design suivant pour nos messages flash :

```html
<div id="flashes-container">
    <!-- Boucle, pour chaque message du type "success" -->
        <span class="flashes flashes-success">Message</span>
    <!-- Fin boucle -->
    <!-- Boucle, pour chaque message du type "error" -->
        <span class="flashes flashes-error">Message</span>
    <!-- Fin boucle -->
</div>
```

Pour obtenir chaque message d'erreur d'un formulaire côté PHP/contrôleur, on peut utiliser une boucle, comme dans l'exemple qui suit :

```php
$errors = $form->getErrors(true);
foreach ($errors as $error) {
    $errorMsg = $error->getMessage();
}
```

On peut notamment utiliser ce bout de code après avoir vérifié qu'un formulaire n'est pas valide, pour ajouter les erreurs sous la forme de messages flash.

<div class="exercise">

1. Dans votre template `feed.html.twig`, prenez en charge l'affichage des messages flash en ajoutant (et en adaptant) la div `flashes-container` présenté précédemment. Placez cette div dans le `body`, juste après le `header`. Il faut que les messages des types `error` et `success` puissent être affichés.

2. Dans votre route `feed`, faites en sorte d'enregistrer les messages d'erreurs du formulaire comme messages flash du type `error` si le formulaire n'est pas valide.

3. Rechargez la page puis essayer à nouveau de créer une publication avec moins de 4 caractères ou plus de 200. Vérifiez que le message d'erreur que vous aviez configuré plus tôt avec les assertions apparaît bien.

</div>

#### Gestion des erreurs - Création d'un service

Le bout de code que vous avez ajouté à votre route `feed` va potentiellement être réutilisé à chaque fois que nous aurons à formulaire. Il serait donc judicieux de centraliser cela dans un `service` dédié !

Pour créer un service, il suffit de créer une classe dans `src/Service` (par convention). Nous pourrons ensuite l'injecter dans une des méthodes du contrôleur comme nous le faisons pour les autres services. Il est d'ailleurs tout à fait possible d'injecter et d'utiliser d'autres services dans notre service (par exemple, nous allons avoir besoin d'accéder à la structure de données contenant les messages flash).

Par exemple, imaginons un service qui utilisera le service `EntityManagerInterface` et `ExempleRepository`. Je peux le créer simplement ainsi :

```php
#src/Service/ExempleService.php

class ExempleService {

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExempleRepository $exempleRepository
    ) {}

    public function maFonction() : void {
        // ...
        $ex = $this->exempleRepository->findAll();
        // ...
        $this->entityManager->persist(...)
        // ...
    }
}
```

La syntaxe utilisée pour définir ce constructeur ne doit pas vous être inconnue. Pour rappel, avec cette syntaxe, on signifie qu'on souhaite enregistrer directement les paramètres comme attributs de la classe (en précisant, au passage, leur visibilité). Ainsi, il n'y a pas de code basique à écrire pour déclarer manuellement ces attributs et les affecter dans le constructeur. Le corps du constructeur peut rester vide. En fait, on peut voir cela comme une version compacte entre la déclaration et l'affectation d'un attribut de la classe.

Ainsi, si j'ai une classe avec le constructeur suivant :

```php
class Exemple {

    public function __construct(
        private string $attr
    ) {}
}
```

J'ai accès à un attribut `$this->attr` dans ma classe (qui sera affecté lors de la construction de l'objet). On peut bien entendu mettre d'autres niveaux de visibilité, comme `public` ou bien `protected`.

Concernant le service, une fois votre classe construite, vous pouvez l'injecter où vous le souhaiter (dans un contrôleur, ou bien dans un autre service) et vous en servir :

```php
#[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(ExempleService $exempleService): Response
{
    $exempleService->maFonction();
}
```

Dans le service que vous allez créer, vous aurez besoin du service `RequestStack` qui permet d'ajouter des messages flash dans la session de l'utilisateur :

```php
$flashBag = $this->requestStack->getSession()->getFlashBag();
$flashBag->add(categorie, message);
```

<div class="exercise">

1. Créez un dossier `Service` dans `src`.

2. Ajoutez et complétez le service suivant (dans `Service`) :

    ```php
    namespace App\Service;

    use Symfony\Component\Form\FormInterface;
    use Symfony\Component\HttpFoundation\RequestStack;

    class FlashMessageHelper
    {

        public function __construct(/* Injection de RequestStack */){}

        public function addFormErrorsAsFlash(FormInterface $form) : void
        {
            $errors = $form->getErrors(true);
            //Ajouts des erreurs du formulaire comme messages flash de la catégorie "error".
        }
    }
    ```

3. Dans votre route `feed`, injectez votre nouveau service puis utilisez-le à la place du code que vous utilisiez précédemment pour gérer les erreurs du formulaire.

4. Rechargez votre page et vérifiez que l'affichage des erreurs fonctionne toujours.
</div>

#### Gestion des erreurs - Interfaçage du service

Comme vous l'aurez peut-être constaté, certains services comme `EntityManagerInterface` s'utilisent au travers d'une interface, et non pas d'une classe concrète. Pour permettre une meilleure modularité et substitutions des services de votre application (si vous décidez de changer la classe qui assure tel ou tel service), il est plus judicieux de définir vos services en les accompagnant d'une interface puis d'injecter et utiliser l'interface (dans les contrôleurs et autres) plutôt que la classe concrète.

Dans ce cas, il faut éditer le fichier `config/services.yaml` afin de préciser quelle est la classe concrète actuellement liée à cette interface. Ce fichier permet de configurer différents aspects des services de notre application (par exemple, quand on a besoin d'injecter des paramètres de notre application dans certains services...).

Imaginons que je crée une interface `ExempleServiceInterface` pour mon service défini dans un exemple précédent :

```php
#src/Service/ExempleServiceInterface.php
interface ExempleServiceInterface {
    public function maFonction() : void;
} 

#src/Service/ExempleServiceA.php
class ExempleServiceA implements ExempleServiceInterface {
    ...
}
```

S'il n'y a qu'une seule classe qui implémente l'interface en question, Symfony va la trouver automatiquement et faire le lien.

Ainsi, quand je veux injecter ce service, je peux maintenant utiliser son interface (`ExempleA` sera alors utilisé):

```php
 #[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(ExempleServiceInterface $exempleService): Response
{
    $exempleService->maFonction();
}
```

Cependant, si **deux classes** (ou plus) implémentent la même interface (ce qui peut arriver : par exemple, une classe gérant la connexion à une base de données MySQL contre une classe gérant la connexion à une base de données Oracle), Symfony ne saura donc pas laquelle choisir.

Par exemple, si j'ai aussi : 

```php
#src/Service/ExempleServiceB.php
class ExempleServiceB implements ExempleServiceInterface {
    ...
}
```
Dans ce cas, il faut éditer le fichier `config/services.yaml` ainsi, en précisant le service qu'on souhaite associer à l'interface :

```yaml
#Dans config/services.yaml
parameters:
    ...
services:
    #Je créé le service abstrait ExempleServiceInterface qui se réfère au service concret ExempleServiceB
    App\Service\ExempleServiceInterface: '@App\Service\ExempleServiceB'
    
```

Si jamais je souhaite changer de classe concrète, j'ai juste à changer le fichier `services.yaml`. Il n'y aura pas de changements à faire dans les classes qui utilisaient mon service jusqu'à présent.

Il peut être intéressant de créer une **interface** pour son service, même si on ne prévoit pas immédiatement d'avoir plusieurs classes concrètes différentes pour ce service. C'est une bonne pratique car, on injectera alors systématiquement le service via l'interface, dans les contrôleurs, dans les autres services. Si jamais on vient finalement à ajouter une autre classe concrète implémentant la même interface (variante de ce service), il suffira alors d'éditer `services.yaml` sans toucher au reste du code si on souhaite en changer.

<div class="exercise">

1. Créez une interface `FlashMessageHelperInterface` (toujours dans le dossier `Service`) contentant la signature de la méthode `addFormErrorsAsFlash` puis faites-la implémenter à `FlashMessageHelper`. Pour rappel, dans **PHPStorm**, vous pouvez faire cela automatiquement : clic droit sur le nom de classe `FlashMessageHelper` > `Refactor` >` Extract Interface`.

2. Dans votre route `feed`, utilisez votre nouvelle interface à la place du service concret.

3. Rechargez votre page et vérifiez que l'affichage des erreurs fonctionne toujours.

4. Pour bien vérifier que vous avez compris les explications précédentes, ajoutez une classe `TestService.php` dans le dossier `Service` qui implémente aussi `FlashMessageHelperInterface`. Laissez le corps de la méthode `addFormErrorsAsFlash` vide. Essayez d'envoyer un message. Symfony renvoie alors une erreur qui explique qu'il n'a pas pu trouver le service en question. Comme dans l'exemple, éditez le fichier `services.yaml` afin de faire pointer le service `FlashMessageHelperInterface` vers `FlashMessageHelper`. Testez que tout fonctionne à nouveau, puis, supprimez les modifications apportées dans `services.yaml` et supprimez également `TestService.php`.

</div>

#### Gestion des erreurs - Validation côté client

Il serait bien de détecter les erreurs de saisies avec des contraintes côté "client" (au niveau du HTML) pour ne pas envoyer la requête inutilement. Par exemple, avec les attributs `minlength` et `maxlength`, pour la longueur du champ.

Il serait possible de paramétrer ces contraintes sur le template, en utilisant le paramètre `attr` de `form_widget` :

```
{{ form_widget(form.champ1, {attr : {'minlength' : 4, 'maxlength' : 10}}) }}
```

Mais on peut faire encore mieux et les ajouter dans la classe du formulaire, au niveau du champ associé :

```php
class ExempleType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('champ1', TextType::class, [
                'attr' => [
                    'minlength' => 4,
                    'maxlength' => 10
                ]
            ])
        ;
    }
}
```

Ainsi, quand j'utiliserai `form_widget`, ces contraintes seront automatiquement générées :

```
{{ form_widget(form.champ1) }}
```

<div class="exercise">

1. Modifiez la classe `PublicationType` afin d'ajouter les contraintes `minlength` et `maxlength` sur votre champ `message` (min 4, max 200).

2. Rechargez la page principale et vérifiez que les contraintes sont bien appliquées (vous pouvez notamment vérifier cela en inspectant le code HTML, pour voir si les attributs sont bien présents, ou bien même en vérifiant que le formulaire n'est pas envoyé si ces contraintes ne sont pas respectées).

</div>

### Architecture des templates

Bientôt, nous allons ajouter de nouvelles pages à notre site. Mais il serait assez peu concevable que nous devions répéter le code HTML de certaines parties de nos pages, comme le bandeau, le head, le menu de navigation, le footer... Heureusement, pour palier à ce problème, `twig` possède une fonctionnalité appelée **blocks**.

Un `block` est une zone d'un template qui pourra être **redéfini** dans un sous-template. On délimite la zone du block simplement en utilisant `{% block nom_block %}` et `{% endblock %}` :

```twig
{% block nom_block %}
   Contenu du bloc...
{% endblock %}
```

Dans un template, on peut **étendre** un autre template. Il suffit d'ajouter dans notre template l'instruction suivante :

```twig
{% extends "nomFichier.html.twig" %}
```

Le chemin à spécifier pour le template étendu est le même que quand on génère la page HTML à renvoyer depuis le contrôleur : on se base par rapport à la racine du dossier `templates`.

Par exemple, imaginons le template suivant, `test.html.twig` :

```twig
<html>
   <head>
      <title>{% block titre %}Test {% endblock %}</title>
   </head>
   <body>
      <header>...</header>
      <main>{% block main %} ... {% endblock %}</main>
      <footer>...</footer>
   </body>
</html>
```

Vous pouvez alors créer le sous-template suivant qui copiera exactement le contenu de `test.html.twig` et modifiera seulement le titre et le contenu du main : 

```twig
{% extends "test.html.twig" %}
{% block titre %}Mon titre custom{% endblock %}
{% block main %} <p>Coucou!</p> {% endblock %}
```

Il n'est pas obligatoire de redéfinir tous les blocks quand on étend un template. Dans l'exemple ci-dessus, on aurait pu seulement redéfinir le bloc `main` sans changer le titre de la page, par exemple.

Il est tout à fait possible d'utiliser un block de structure à l'intérieur d'un autre block de structure. Il est aussi tout à fait possible de créer un block redéfinissable à l'intérieur d'un autre block... Il est aussi possible de faire des sous-templates de sous-templates...

Voyez ce système comme une hiérarchie entre classes ! On étend une classe parente et les blocks sont comme des méthodes qu'il est possible de redéfinir !

Pour notre site, nous allons donc adopter la stratégie suivante :

* Un template de base, qui sera étendu par chaque page. Il contiendra notamment le bandeau, le menu de navigation et définira un block dans son `body` qui pourra être récrit par les sous-templates.

* Chaque page étend le template de base, réécrit le block définissant le contenu du body (et d'autres blocks si nécessaire...)

<div class="exercise">

1. Créez un template `base.html.twig` à la racine du dossier `templates`. À l'intérieur, déplacez globalement tout ce qui se trouve dans le template `feed.html.twig` sauf le `main` (en gros, tout ce qui sera a priori commun à toutes les pages de notre site...).

2. Dans le `body` de ce template, juste après la zone affichant les messages flash, créez un block `page_content`.

3. Dans `feed.html.twig`, faites en sorte d'étendre `base.html.twig` puis de récrire le block `page_content` de manière adéquate, en ne gardant que le contenu propre à cette page (le `main`). Si ce n'est pas déjà fait, supprimez tout le reste (qui est redondant avec ce qui est déjà contenu `base.html.twig`).

4. Rechargez votre page principale et vérifiez qu'elle s'affiche toujours correctement. Vérifiez le code HTML généré pour être sûr qu'il n'y a pas d'erreur.

5. On souhaite que le titre (contenu de `<title></title>`) de chaque page puisse être redéfini par chaque sous-template étendant `base.html.twig`. Faites cela en créant un block (vide) nommé `page_title` dans `base.html.twig`, puis, dans le template `feed.html.twig`, faites en sorte de nommer la page "The Feed" en utilisant ce nouveau block. Vérifiez que cela fonctionne bien.

</div>

## Conclusion

Vous maîtrisez maintenant les fondamentaux du framework Symfony : son système de routing, ses commandes, le moteur de template **twig**, les services et l'utilisation de l'ORM **Doctrine**. Vous pouvez déjà construire un petit site assez facilement. Il reste cependant un aspect majeur à aborder : la gestion des utilisateurs. L'objectif du second TD sera donc de mettre en place tout ce qu'il faut pour inscrire, connecter, déconnecter et gérer les permissions (basiques) d'un utilisateur.

### Lien avec le cours de complément web (RACDV)

Une petite dernière section supplémentaire à l'attention du parcours **RACDV**. Dans ce TD, vous avez réutilisé des concepts que vous aviez abordés en cours de **complément web**. Le but de cette section est de faire lien avec tout cela pour comparer la façon de faire de **Symfony** :

 * En cours de complément web, nous avions configuré un **autoloader** afin de charger nos classes. Symfony gère aussi ce chargement automatique à partir du nom de classe complet. Le **namespace** de base est `App` et pointe sur le dossier `src` de l'application. Vous pouvez notamment retrouver cette configuration dans le fichier `composer.json` à la source du projet!

 * Symfony réécrit l'URL pour appeler toujours le script de base public/index.php en transmettant l'information de l'URL relative. En utilisant le serveur de **Symfony**, cela se fait tout seul, sinon, le fichier `.htaccess` du dossier `public` est utilisé. Vous aviez vous-même inclut un fichier similaire pour obtenir le même comportement, dans votre framework "maison".

 * Symfony contient un **routeur**. Dans notre projet, la déclaration des routes s'est faite en lisant les attributs `#[Route(...)]` comme nous le faisions (à terme) l'année dernière. Mais il est tout à fait possible de les déclarer avec du code PHP (ou bien un fichier de configuration). Si on regarde la [documentation officielle](https://symfony.com/doc/current/routing.html#matching-http-methods), on constate qu'il est possible de switcher entre quatre manières de faire. L'onglet `PHP` devrait vous remémorer certaines choses ! D'ailleurs, dans le framework `Laravel` il est obligatoire d'utiliser du code PHP pour déclarer les routes (sauf si vous installez quelques librairies). Cela a pour avantage de centraliser le code des routes au lieu de les disperser dans divers contrôleurs. 
 
 Fait amusant : si vous êtes amené à utiliser **Laravel**, vous pourrez constater que le framework importe le routeur de Symfony et l'étend ! Et c'est une bonne chose : on évite de réinventer la roue.

 * Symfony utilise un `ControllerResolver` et un `ArgumentResolver` pour appeler la bonne action (bon controller, bonne méthode) avec les bons arguments. Vous aviez déjà utilisé exactement ces classes (issues de Symfony) dans votre framework maison ! En effet, nous les avions installées avec composer.

 * La majeure partie de la technologie `twig` (syntaxe, blocs...) a été vue l'année dernière.

 * Nous avions ajouté deux fonctions à `twig` : `asset` pour récupérer les assets (images, fichiers...) de notre application et `route` pour générer le lien d'une route à partir de son nom (et éventuellement ses paramètres). Ici, Symfony inclut directement ces fonctions avec `asset` et `path` (à la place de `route`).

 * Le fichier de configuration `config/services.yaml` est très proche du fichier de configuration du conteneur de services que certains d'entre vous aviez codé (lors du TD4 de complément web, au semestre 4). Nous avions aussi codé un équivalent de ce fichier sous la forme d'une classe de configuration PHP.

 * La notion d'injection de dépendances et le concept de dépendre d'interfaces plutôt que de classes concrètes a aussi été abordé lors du TD4 de complément web. Cela permet à votre application d'être plus modulable et plus facilement testable ! Nous avions utilisé un **conteneur IoC** afin d'enregistrer et configurer toutes nos dépendances et les injecter de manière adéquate dans chaque classe (nous avions d'ailleurs utilisé celui de Symfony, donc c'est le même qui gère tout cela en arrière-plan ici aussi).

 * Nous avions déjà vu la syntaxe des constructeurs avec visibilité devant les arguments, afin de déclarer un attribut (comme nous le faisons dans `FlashMessageHelper`, par exemple).

{% endraw %}
