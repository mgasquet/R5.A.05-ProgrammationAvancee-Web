---
title: TD3 &ndash; Amélioration du site
subtitle: Javascript, Symfony UX, Stimulus, Turbo, Premium, Permissions, Administration, Commandes
layout: tutorial
lang: fr
---

## Introduction

Dans ce nouveau TD, nous allons améliorer le site en ajoutant diverses fonctionnalités qui vont vous permettre d'affiner et de renforcer votre maîtrise de Symfony.

Voici les nouveaux objectifs pour "The Feed" :

* Ajout de fonctionnalités **dynamiques** avec **JavaScript** via **Symfony UX** (pour supprimer et ajouter des publications).
* Ajout d'un système de **membres premium**.
* Gestion avancée des **permissions**.
* Ajout de **commandes Symfony** dans la console.
* Ajout d'un nouveau **rôle** (administrateur).

**On rappelle que les commandes dans le terminal doivent être exécutées à l'intérieur de votre conteneur Docker**.

## Fonctionnalités dynamiques

L'année dernière, vous avez découvert la possibilité d'avoir certaines fonctionnalités dynamiques afin d'effectuer des actions et de recevoir des réponses sans avoir besoin de recharger la page courante, afin de rendre votre site plus dynamique. C'est d'ailleurs la logique au cœur des frameworks réactifs (Vue.js, Angular, React) qui seront abordés dans de prochains TD.

Avec Symfony, il existe plusieurs moyens d'ajouter des fonctionnalités dynamiques. La première, que vous connaissez, consiste à coder ces fonctionnalités via JavaScript. La seconde utilise une bibliothèque `Turbo` qui est déjà chargée et active sur votre site.

Par exemple, comme on souhaite ajouter la possibilité aux utilisateurs de supprimer leurs publications, on pourrait ajouter du JavaScript à notre site et une route prévue pour être utilisée de manière asynchrone (qui n'utilise pas Twig et qui ne renvoie pas de page, mais éventuellement des données JSON).

Aussi, actuellement, notre route `deconnexion` est accessible en `GET`. Nous avions évoqué le fait qu'il serait plus judicieux et sécurisé d'avoir cette route en mode `POST` (ce qui n'est pas possible avec un lien simplement généré). Nous pourrions également gérer cela avec JavaScript.

### Prise en charge du JavaScript avec Symfony UX

Traditionnellement, l'ajout de **JavaScript** à une page s'effectue dans la partie `head` de la page, avec la balise suivante :

```html
<script defer type="text/javascript" src="chemin/fichier.js"></script>
```

On pourrait donc tout à fait charger un fichier placé dans `assets` (comme pour les images) grâce à la fonction `asset` de twig. Une autre solution serait de créer [un entry point dédié](https://symfony.com/doc/current/frontend/asset_mapper.html#page-specific-css-javascript) pour charger tous les fichiers `js` (et éventuellement `css`) relatifs à cette page (préférable à la première solution).

Cependant, Symfony inclut un ensemble de bibliothèques et d'outils nommé **Symfony UX** qui permet de gérer le JavaScript d'une manière particulière, en s'appuyant sur le **framework** JavaScript nommé **Stimulus**. Cet outil facilite l'intégration de JavaScript sans avoir besoin d'importer manuellement de fichiers ou de définir de configuration particulière. C'est la méthode à privilégier dans la mesure du possible, notamment quand on utilise la bibliothèque `Turbo` (incluse dans Symfony UX) dont nous allons parler un peu plus tard.

Pour utiliser du JavaScript avec **Stimulus** dans une page, il faut définir un (ou plusieurs) **controller Stimulus** en JavaScript (à ne pas confondre avec un **controller** Symfony comme `PublicationController.php`). Ce controller doit être nommé `xxx_controller.js` (`xxx` étant un nom custom que l'on donne) et placé dans le dossier `assets/controllers`. Ce fichier a cette allure :

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    maFonction1() {
        //...
    }

    maFonction2(event) {
        //...
    }

    connect() {
        //...
    }

    disconnect() {
        //...
    }
}
```

Comme vous pouvez le constater, à l'intérieur du **controller**, on peut définir diverses fonctions optionnelles, dont certaines peuvent recevoir des événements (un clic, par exemple). Les fonctions `connect` et `disconnect` sont des fonctions optionnelles spéciales qui sont appelées quand le controller est chargé ou déchargé (au chargement d'une page, par exemple). On peut s'en servir pour initialiser certains éléments au chargement/déchargement de la page, au besoin.

Ensuite, nous pouvons attacher le **controller** à une **zone** d'une page. Cela peut être par exemple sur le `body` dans `base.html.twig` si l'on veut un controller général, actif sur toutes les pages. Ou bien sur le `main` d'un des templates, pour un controller actif seulement sur une page donnée. Ou bien sur une `div` pour activer le controller seulement sur une sous-zone de la page, etc. Ou bien même juste sur un input simple, etc. Une page peut accueillir autant de controllers que l'on souhaite.

Le controller est défini comme attribut `data-controller` d'une balise html (simple, ou qui contient d'autres balises, comme `<div>`) du template twig (avec le nom `xxx` donné au fichier `js` contenant le controller). Par exemple :

```html
<div data-controller="xxx">
    ...
</div>
```

Ou bien

```html
<input data-controller="xxx"/>
```

Enfin, on peut attacher des **gestionnaires d'événements** sur la balise sur laquelle est attribué un **controller** ou sur les éléments qu'elle contient (dans le cas d'une balise "conteneur", comme `<div>`, `<form>`, etc) à l'aide de l'attribut `data-action`. Par exemple, imaginons le controller suivant nommé `exemple_controller.js` :

```js
//assets/controllers/exemple_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    monActionA() {
        console.log("Clic!");
    }

    monActionB(event) {
        console.log("Changement de valeur: " + event.target.value);
    }
}
```

Et, dans un template twig :

```html
<div data-controller="exemple">
    <button data-action="click->exemple#monActionA"></button>
    <input type="text" data-action="input->exemple#monActionB"/>
</div>
```

À chaque clic sur le bouton, la console affichera « Clic ! » et à chaque saisie dans le champ textuel, elle affichera la nouvelle valeur dans la console. Il n'y a rien d'autre à faire : il suffit de marquer les zones et les événements, puis de définir le fichier controller.

Comme on le remarque, la valeur de `data-action` se décompose ainsi : `nomAction->nomController#nomFonction`. Il peut sembler étrange d'avoir à préciser le nom du controller, mais n'oubliez pas que ce fragment de code est potentiellement contenu dans un autre fragment de code pour lequel on a spécifié un autre controller, donc, on pourrait aussi préciser un autre controller dans nos balises.

Tous les événements que vous connaissez (click, change, focus, etc) sont disponibles.

Il est important **de ne pas passer par le système classique d'événements js** (par exemple, onclick, oninput, etc) quand on utilise ce système, notamment si on utilise la librairie `Turbo`, comme nous le verrons après. Cependant, comme nous l'avons vu, il reste toujours possible de charger un fichier JavaScript "classique" si on ne souhaite pas utiliser ce système pour certaines raisons.

Si on souhaite utiliser du JavaScript globalement, on peut définir un controller global et l'attacher sur `body`, par exemple.

Bref, nous allons commencer par mettre en place une fonctionnalité JavaScript simple sur la page principale et la page personnelle des utilisateurs. Pour l'instant, cela permettra simplement de supprimer "visuellement" une publication de la page (mais pas encore réellement, elle sera toujours là au rechargement de la page).

<div class="exercise">

1. Dans le dossier `assets/controllers`, créez un fichier `publications_controller.js`, avec le contenu suivant :

   ```js
   import { Controller } from '@hotwired/stimulus';
   
   export default class extends Controller {
       supprimerPublication(event) {
           const button = event.target;
           const publication = button.closest(".feedy");
           publication.remove();
       }
   }
   ```

2. Créez un template `publication/liste_publications.html.twig` avec le contenu suivant que vous complèterez.
   {% raw %}
   ```twig
   <div>
   {# Boucle sur les publications #}
     {# Inclusion de 'publication/publication.html.twig' sur la publication du tour de boucle #}
   {# fin boucle #}
   </div>
   ```
   {% endraw %}
   Mettez à jour `publication/feed.html.twig` et `utilisateur/page_perso.html.twig` afin d'utiliser votre nouveau template. Vérifiez que vos deux pages fonctionnent toujours.

3. Faites en sorte d'attacher le controller `publications` au `div` contenant les publications dans `liste_publications.html.twig`.

4. Modifiez le template `publication.html.twig` afin de rajouter le bout de code HTML suivant, juste après l'élément `<p>...</p>` contenant le message de la publication : 

    ```html
    <button class="delete-feedy">Supprimer</button>
    ```

    Ce bouton ne doit apparaître que si l'utilisateur connecté est l'auteur de la publication ! Pour rappel, vous avez accès à la variable `app.user` dans vos templates `twig`... Attention, avant d'y accéder, il faut d'abord bien vérifier que l'utilisateur est bien connecté !

5. Faites en sorte que la fonction `supprimerPublication` du controller se déclenche lors du **clic** sur le bouton.

6. Allez sur la page principale de votre site et vérifiez que :

    * Le bouton "Supprimer" apparaît seulement sur les publications dont vous êtes l'auteur.
    * Le bouton fonctionne, c'est-à-dire que la publication est retirée de la page (seulement visuellement pour le moment).

    Vérifiez également que tout fonctionne de même sur votre page personnelle.

    Si cela ne fonctionne pas, vérifiez la console (`F12`) pour chercher d'éventuels messages d'erreur. 
    Vérifiez aussi que vous avez bien supprimé le dossier `public/assets` (*cf.* TD1) pour ne pas utiliser une version ancienne de vos `assets` qui ne contiendrait pas `publications_controller.js`.

</div>

### Suppression d'une publication

Maintenant que nous avons de quoi supprimer visuellement une publication de manière dynamique, il faut confirmer cette suppression côté back-end. Il faut aussi pouvoir générer le lien de la route dans notre fichier JavaScript, comme nous le faisons avec `path` dans nos templates.

Nous allons procéder par étapes : d'abord une route de suppression toute simple (sans gestion des cas d'erreur), puis l'outillage nécessaire pour l'appeler depuis JavaScript, puis la requête `fetch` elle-même, et enfin la sécurisation de la route.

#### Une première route de suppression

Pour créer une route accessible par une requête HTTP exécutée en JavaScript (et qui ne renvoie pas de page, mais plutôt des données), quelques éléments diffèrent :

```php
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/exemple', name: 'route_exemple', methods: ["POST"])]
public function methodeExemple(Request $request): Response
{
    //Récupération des données fournies dans le payload JSON
    $donnee = $request->get('donnee');

    //Traitement...

    //Renvoie d'une réponse au format JSON
    return new JsonResponse(contenu, codeReponse);
}
```

* Si des données `JSON` (ou autre) sont envoyées et doivent être lues, on peut les récupérer avec l'objet `Request`. En fait, cela marche de la même façon que pour récupérer des données depuis query string, ou bien même d'un formulaire...

* On renvoie un objet `JsonResponse` contenant éventuellement des données au format `JSON` (qui peuvent être `null`) et un code de réponse HTTP (200, 400, etc.).

Dans les premiers TD, nous n'avons fait que lire ou créer des entités ! Pour en supprimer une, il faut là aussi utiliser `EntityManagerInterface` (en l'injectant dans la méthode de la route) et utiliser la méthode `remove` (au lieu de `persist`, qui crée ou met à jour une entité).

```php
$entityManager->remove($entity);
$entityManager->flush();
```

<div class="exercise">

1. Dans `PublicationController.php`, créez une route `supprimerPublication` possédant une route paramétrée `/publications/{id}`, accessible via la méthode `DELETE` et **exposée**. Pour l'instant, on reste simple : pas besoin de vérifier si la publication existe ou si l'utilisateur courant en est l'auteur (nous ajouterons ces vérifications plus tard). Concrètement, la route doit :

    * Récupérer la publication visée par l'identifiant donné dans la route (souvenez-vous, lors du TD2, nous avions vu une méthode très simple pour récupérer une entité précisée à partir d'une route paramétrée, sans utiliser explicitement son repository !).
    * Supprimer la publication.
    * Renvoyer une réponse au format `JSON` ne contenant rien (**null**) avec le code `Response::HTTP_NO_CONTENT` (204) (ce code signifie simplement que l'opération s'est bien passée, mais que la réponse ne contient aucune donnée).

2. Avant de brancher quoi que ce soit côté JavaScript, testez directement votre route avec la commande `curl` suivante dans le terminal qui affiche uniquement les en-têtes de réponse (adaptez l'identifiant à une publication existante dans votre base) :

   ```bash
   curl --url 'http://localhost/the_feed/public/publications/1' -X 'DELETE' --head
   ```

   Vérifiez que la réponse a bien le code `204` et que la publication a disparu de votre base de données. Vous pouvez répéter la commande sur le même id : la seconde fois devrait échouer (puisque la publication n'existe plus), mais comme nous ne gérons pas encore ce cas, l'erreur obtenue ne sera pas très explicite pour l'instant.

</div>

#### Génération des routes en JavaScript avec FOSJsRoutingBundle

Du côté de notre **controller Stimulus**, nous n'avons pas accès à la fonction `path` comme dans nos templates twig! Pour remédier à cela, il suffit d'installer un `bundle` qui est un composant PHP prévu pour s'intégrer spécifiquement à Symfony.

Le bundle que nous allons utiliser s'appelle [FOSJsRoutingBundle](https://github.com/FriendsOfSymfony/FOSJsRoutingBundle/tree/master) et permet d'accéder à une fonction similaire à `path`, mais directement en JavaScript.

Comme pour tous les composants, il faut commencer par l'installer :

```bash
composer require friendsofsymfony/jsrouting-bundle
php bin/console importmap:require fos-router
```

Lors de l'installation, il vous est demandé si vous souhaitez exécuter une "recette". Répondez **oui**.

> **Remarque :** Si jamais vous avez oublié de dire oui, exécutez les deux commandes suivantes
> 
> ```bash
> composer remove friendsofsymfony/jsrouting-bundle
> composer require friendsofsymfony/jsrouting-bundle
> ```

Ensuite, nous devons **configurer l'URL de base du site**. Ce paramètre est utile, car nous allons devoir **exporter nos routes** exposées (pour pouvoir y accéder depuis JavaScript).

Pour cela, il faut simplement éditer le paramètre `DEFAULT_URI` du fichier `.env`. Par exemple, dans notre cas (avec le site dans le conteneur Docker), cet URL est `http://localhost/the_feed/public` :

```yaml
DEFAULT_URI=http://localhost/the_feed/public
```

Afin que le chemin d'une route puisse être généré à partir de son nom (côté JavaScript), il faut **exposer la route** en ajoutant `options: ["expose" => true]` dans l'attribut contenant les métadonnées de la route, comme nous l'avons fait un peu plus tôt sur la route `supprimerPublication`. En effet, seules les routes exposées peuvent être générées de cette manière.

```php
#[Route('/publications/{id:publication}', name: 'supprimerPublication', options: ["expose" => true], methods: ["DELETE"])]
public function supprimerPublication(Publication $publication, EntityManagerInterface $entityManager): Response
{
    //...
}
```

Après cela, il faut **générer** le fichier qui contiendra toutes nos routes exposées. Pour cela, on utilise la commande suivante :

```bash
php bin/console fos:js-routing:dump --format=js --target=assets/routes/fos_routes.js --callback="export default"
```

Ce qui génère un fichier dans le dossier `assets/routes`. Lors de l'ajout ou la modification d'une route exposée, **il faudra appeler cette commande de nouveau** pour maintenir ce fichier à jour.

Enfin, il faut importer et enregistrer les routes du côté de notre point d'entrée `assets/app.js` pour les rendre accessibles dans nos controllers Stimulus.

```js
//assets/app.js
import './stimulus_bootstrap.js';
import './css/styles.css';

//Nouveau
import Routing from 'fos-router';
import routes from './routes/fos_routes.js';
Routing.setRoutingData(routes);
```

Une fois ces étapes complétées, nous avons alors accès (au niveau du controller JavaScript) à la fonction `Routing.generate`, sensiblement équivalente à `path` dans son utilisation :

```javascript
//Au début du fichier
import Routing from 'fos-router';

//Dans une fonction
let URL = Routing.generate('maRoute');
//Et si on a une route paramétrable:
let URL = Routing.generate('maRoute', {"param": val, ...});
```

<div class="exercise">

1. Installez `FOSJsRoutingBundle` et configurez tout ce qu'il faut (URL par défaut, export des routes, import dans `app.js`) pour pouvoir utiliser la fonction `Routing.generate` dans votre controller Stimulus. Vous pouvez supprimer le dossier `public/bundles` qui ne nous servira pas.

2. Pour vérifier que tout fonctionne, modifiez temporairement la fonction `supprimerPublication` de `publications_controller.js` afin qu'elle affiche dans la console le résultat de `Routing.generate('supprimerPublication', {"id": 123})`.  
   
   Rechargez la page, cliquez sur un bouton "Supprimer" et vérifiez dans la console (`F12`) que l'URL générée correspond bien à celle de votre route (par exemple `http://localhost/the_feed/public/publications/123`).

</div>

#### Récupérer l'id de la publication à supprimer

Lors de l'appel de la route `/publications/{id}` depuis `supprimerPublication` de `publications_controller.js`, un problème subsiste : comment récupérer l'id de la publication associée au bouton "Supprimer" sur lequel on clique pour le passer en paramètre de la route ?

Pour cela, nous pouvons utiliser un attribut `data-xxx` qui permet de créer des attributs "dynamiques" sur un élément HTML. **Attention**, le nom custom donné (`xxx`) suit des règles lexicographiques précises :

* Les mots sont séparés par des tirets.
* Pas de majuscules, ni de point virgules.

On pourra ensuite récupérer la valeur de cet attribut en JavaScript.

Par exemple :

```html
<button data-exemple-machin="test">Coucou</button>
```

Côté JavaScript, on utilise l'attribut `dataset` puis le nom `xxx` donné après le `data-` :

```javascript
//On considère que la fonction "exemple" est attaché au bouton...
function exemple(event) {
    const button = event.target;

    //Attention, le nom de l'attribut est à préciser en *camel case*
    const exemple = button.dataset.exempleMachin;
    //exemple contient "test"
}
```

Dans le HTML, mon attribut était nommé `data-exemple-machin`, ce qui donne en **camel case** : `exempleMachin`.

<div class="exercise">

1. Modifiez le template `publication.html.twig` afin d'inclure un attribut `data-publication-id` contenant l'identifiant de la publication dans les attributs du bouton de suppression.

2. Dans `publications_controller.js`, remplacez l'identifiant codé en dur (`123`) utilisé dans l'exercice précédent par la valeur récupérée via `dataset` sur le bouton cliqué. Vérifiez, toujours dans la console, que l'URL générée contient bien l'id de la publication sur laquelle vous avez cliqué (et pas toujours la même).

</div>

#### Envoyer la requête de suppression

Notre prochain objectif est de modifier la fonction `supprimerPublication` de `publications_controller.js` afin qu'elle effecturune requête asynchrone vers la route nommée `supprimerPublication` de méthode `DELETE`.
Vous pouvez notamment utiliser la fonction `fetch` et l’instruction `await` que vous devez maîtriser depuis les cours de JavaScript de l’année dernière ! Quelques petits rappels (et nouvelles précisions) dans le contexte d'une requête simple sans corps de requête :

```javascript
import { Controller } from '@hotwired/stimulus';
import Routing from 'fos-router';

export default class extends Controller {
    //Comme on utilise le mot clé "await" dans le corps de la fonction, on doit rendre la fonction asynchrone.
    //Pour cela, on utilise le mot clé "async"
    async maFonction(event) {

        //On précise l'URL de la requête.
        const URL = Routing.generate('...');

        //On utilise le mot clé "await" pour "attendre" que la requête soit complètement exécutée avant d'exécuter les prochaines instructions.
        //Par conséquent, la fonction "maFonction" doit être asynchrone pour ne pas bloquer la page.
        const response = await fetch(URL, {
            //La méthode utilisée (GET, POST, PUT, PATCH ou DELETE)
            method: "...",
        });

        //Ici, on a la garantie que la requête a fini de s'exécuter (on a un code de réponse, et éventuellement un résultat)
        if(response.status === ...) {
            //response.status permet d'accéder au code de réponse HTTP (200, 204, 403, 404, etc.).
        }
    }
}
```

<div class="exercise">

1. Dans `publications_controller.js`, modifiez la fonction `supprimerPublication` afin de remplacer le `console.log` par une véritable requête asynchrone `DELETE` vers la route `supprimerPublication`, en utilisant `fetch` et `await` comme rappelé ci-dessus.

2. Au chargement de la réponse, déclenchez la suppression (visuelle) de la publication sur la page (vous avez déjà le code pour cela dans le fichier) **si et seulement si le serveur a bien supprimé la publication** (code `204`).

3. Testez que la suppression des publications fonctionne bien (elles ne réapparaissent pas après avoir rechargé la page). Si rien ne se passe, jetez un œil à la console (`F12`) pour lire les éventuels messages d'erreurs.

</div>


Si un jour vous avez besoin d'envoyer des données (un `payload`) avec votre requête, vous pourrez utiliser en plus les `headers` et le `body` :

```javascript
//Les "headers" de la requête: on indique le type de données qu'on envoie
const headers = new Headers();
headers.append("Content-Type", "application/json");

//Le payload contient les données (sous la forme d'un objet clé-valeur) qu'on souhaite envoyer avec la requête
const payload = {donnee1: ..., donnee2: ..., ...};

const response = await fetch(URL, {
    method: "...",
    //On transforme le "payload" en chaîne de caractères.
    body: JSON.stringify(payload),
    headers: headers,
});
```

#### Sécuriser la route de suppression

Notre route est fonctionnelle, mais actuellement, n'importe qui (même déconnecté) peut supprimer n'importe quelle publication en devinant simplement son identifiant et en exécutant une requête `DELETE` (par exemple avec `curl`, comme nous l'avons fait plus tôt) ! Il est temps de corriger cela.

<div class="exercise">

1. Complétez la route `supprimerPublication` de `PublicationController.php` afin de :

    * Vérifier que la publication existe et que l'utilisateur courant en est bien l'auteur.
    * Renvoyer une réponse au format `JSON` ne contenant rien (**null**) et soit renvoyer le code :
        * `Response::HTTP_NOT_FOUND` (404) si la publication n'existe pas (ressource non trouvée).
        * `Response::HTTP_FORBIDDEN` (403) si l'utilisateur n'est pas auteur de la publication (opération interdite).
        * `Response::HTTP_NO_CONTENT` (204) si tout se passe bien, comme avant.

2. En utilisant l'attribut `IsGranted`, faites en sorte que cette route soit seulement accessible aux utilisateurs connectés (possédant le rôle `ROLE_USER`). Allez consulter le TD2 si vous ne savez plus comment faire.

3. Vérifiez avec `curl` qu'un identifiant inexistant renvoie bien un code `404`.

   Désactiver temporairement dans `publication.html.twig` la condition `if` afin d'afficher le bouton *Supprimer* sur toutes les publications. Testez sur le site Web la suppression d'une publication dont vous n'êtes pas l'auteur alors que vous êtes connecté. Observez la réponse `403 (Forbidden)` dans l'onglet `Console` des outils de développement. Réactivez le `if`.

   Vérifiez que la suppression fonctionne toujours normalement depuis le site pour l'auteur d'une publication. 

</div>

### Ajout d'une publication avec Turbo

N'avez-vous pas remarqué quelque-chose d'étrange depuis le milieu du TD1 ? Naviguez entre les différentes pages de votre site depuis le menu de navigation, postez une publication... Vous ne verrez jamais le logo de chargement sur votre onglet ! Tout cela, grâce à **Turbo**.

**Turbo** est l'une des bibliothèques incluses dans **Symfony UX**, qui permet d'offrir une expérience `SPA` (Single-page application) à l'utilisateur. Par défaut, lors de l'envoi d'une requête "classique" qui ne passe pas par notre JavaScript (donc des liens, envoi de formulaire...), **Turbo** intercepte la demande et effectue la requête. Il récupère ensuite le résultat (code HTML) et met à jour le contenu de la page sans rechargement complet du `DOM`, tout cela via du JavaScript intégré à la bibliothèque.

Ce système, activé par défaut, permet de fluidifier l'expérience utilisateur, car le navigateur "reste" sur la même page : le document html n'est chargé qu'une seule fois lors du premier accès au site et ensuite seul le contenu nécessaire est mis à jour, sans rechargement complet de la page. Ce système est utilisé dans beaucoup de frameworks Web modernes.

Par défaut, comme nous renvoyons du code complet de page à chaque requête, Turbo met à jour une grande partie de la page à chaque requête. Mais il est possible d'optimiser cela en ciblant les zones et les éléments de la page à mettre à jour selon la requête effectuée (comme nous l'avons fait juste avant avec la suppression d'une publication, par exemple, mais avec notre propre code JavaScript). À terme, cela permet même de se passer d'écrire du JavaScript pour certaines opérations `CRUD`.

Nous allons explorer les deux mécanismes principaux de **Turbo** : **turbo frame** et **turbo stream**.

#### Turbo Frame

Un **turbo frame** est une simple balise html que nous pouvons placer n'importe où dans un template. Il faut donner un **identifiant** à cette balise. Lors de l'exécution d'une requête classique, Turbo permet de cibler et de remplacer le contenu de cette balise par le code html (ou une partie du code) résultat de la requête.

Par exemple, prenons l'exemple d'une page `HTML` renvoyée par le serveur.

```html
<html>
    <head>...</head>
<body>
    <header>...</header>
    <main>
        <h1>Films</h1>
        <ul>
            <li>Interstellar</li>
            <li>Kill Bill</li>
            <li>Le vent se lève</li>
        </ul>
        <form action="/films" method="post">
            <label for="nomFilm">Nom Film</label>
            <input id="nomFilm" type="text"/>
            <input type="submit" value="Ajouter film">
        </form>
    </main>
</body>
```

On imagine qu'après soumission du formulaire, on est redirigé sur la même page.

Par défaut, si je soumets le formulaire, **Turbo** va recevoir en réponse à la requête une page HTML avec le `html`, le contenu du `head`, le `h1`, les films, le formulaire, etc... Et va mettre à jour la page grâce à JavaScript. Cependant, à priori, seul la liste des films change ! On pourrait donc faire en sorte que seulement cette partie soit mise à jour (et même que le serveur ne renvoie que ça).

Dans ce cas, on pourrait mettre en place la logique suivante :

```twig
{% raw %}
<!-- Template film/liste_films.html.twig, n'étend pas de template de base -->
<turbo-frame id="films">
    <ul id="films">
        {% for film in films %}
            <li>{{ film.nom }}</li>
        {% endfor %}
    </ul>
</turbo-frame>
{% endraw %}
```

```twig
{% raw %}
<!-- Template film/accueil.html.twig -->
extends 'base.html.twig'
<main>
    {{ include('film/liste_films.html.twig', {'films': films}) }}
    <form action="/films" method="post" data-turbo-frame="films">
        <label for="nomFilm">Nom Film</label>
        <input id="nomFilm" type="text"/>
        <input type="submit" value="Ajouter film">
    </form>
</main>
{% endraw %}
```

Et du côté back-end, dans l'action qui gère le traitement de la requête **POST**, on renvoie une page générée avec `film/liste_films.html.twig` et pas la page entière (`film/accueil.html.twig`) !

```php
...
if($form->isSubmitted() && $form->isValid()) {
    $entityManager->persist($film);
    $entityManager->flush();
    return $this->render("film/liste_films.html.twig", ["films" => $repositoryFilm->findAll()]);
}
```

Et le tour est joué ! Dorénavant, seul la partie qui correspond à la liste de films sera mise à jour. Ce qui relie tout cela est l'id `films` utilisé :
* Sur la balise **turbo-frame** (afin que Turbo sache "où" aller chercher et insérer les données de la réponse du serveur).
* Sur le formulaire dans `data-turbo-frame` (pour informer Turbo que ce formulaire déclenche la mise à jour du frame). À noter que si le formulaire/lien est inclus dans le contenu du frame, on n'a pas besoin de préciser cet attribut.

La puissance des frames va plus loin : si la page renvoyée vers le serveur contient plusieurs données (voir une page complète), il ira chercher juste le bout dont l'id correspond à l'id du `turbo-frame` pour ne mettre à jour que cette partie.

Bien que puissant, ce système n'est pas vraiment adapté au problème que nous venons de présenter : nous n'avons pas besoin de recharger toute la liste des films lors de l'ajout ! Seulement d'ajouter le nouveau film à la liste. C'est le même problème pour nos publications. Ici, ce système serait adapté s'il y avait de la pagination par exemple, et que nous cliquions sur un bouton pour aller à la page suivante. Il y a aussi d'autres problématiques que ce système permet de gérer (par exemple, remplacé une zone de la page par le formulaire présent sur une autre page...)

Pour gérer notre problème d'ajout "simple", nous allons plutôt utiliser le second mécanisme : Turbo Stream.

#### Turbo Stream

Un **turbo stream** peut être vu comme une **instruction** qui permet d'informer Turbo de la manière d'insérer certains éléments générés dans le code HTML généré par le serveur par le biais d'un template spécial (contenant une ou plusieurs balises turbo stream) dans la page courante.

Ici aussi, le back-end va seulement générer le bout de code HTML désiré et turbo se chargera du reste.

Par exemple, reprenons notre exemple d'avant. On souhaite toujours insérer un film dans la page.

```twig
{% raw %}
<!-- Template film/accueil.html.twig -->
extends 'base.html.twig'
<main>
    <ul id="films">
        {% for film in films %}
            <li>{{ film.nom }}</li>
        {% endfor %}
    </ul>
    <form action="/films" method="post">
        <label for="nomFilm">Nom Film</label>
        <input id="nomFilm" type="text"/>
        <input type="submit" value="Ajouter film">
    </form>
</main>
{% endraw %}
```

On peut créer un template "d'instructions" contenant des balises `<turbo-stream>` :

```twig
{% raw %}
<!-- Template film/stream/film_create_stream.html.twig -->
<turbo-stream action="append" target="films">
    <template>
        <li>{{ film.nom }}</li>
    </template>
</turbo-stream>
{% endraw %}
```

Ce template twig contient une instruction qui dit "ajoute à la fin de l'élément qui a pour id **films** le html suivant (code avec le `<li>`, utilisant les données d'un nouveau film soumis par le formulaire).

Tout cela se configure via les deux paramètres :
* `target` : dans quel conteneur effectuer l'opération (son id).
* `action` : l'action à réaliser : `append` signifie un ajout à la fin du conteneur. `prepend`, au début. D'autres instructions spéciales (remove, update, etc) existent.

L'id de `target` et du conteneur (ici `ul`) doivent correspondre.

Il est tout à fait possible de combiner plusieurs instructions !

```twig
{% raw %}
<!-- Ajoute le film et un message flash... -->
<turbo-stream action="append" target="films">
    ...
</turbo-stream>
<turbo-stream action="prepend" target="flashes">
    <p>Film ajouté!</p>
</turbo-stream>
{% endraw %}
```

Côté back-end, il suffit de configurer la requête puis de renvoyer le template lors du traitement de l'opération :

```php
use Symfony\UX\Turbo\TurboBundle;

...
if($form->isSubmitted() && $form->isValid()) {
    $entityManager->persist($film);
    $entityManager->flush();
    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    return $this->render('film/stream/film_create_stream.html.twig', ["film" => $film]);
}
```

<div class="exercise">

1. Mettez en place un système d'ajout de publications en utilisant le système **turbo stream**. Comme nous souhaitons ajouter la publication au début de la liste, il faut utiliser l'action `prepend`. Contrairement à l'exemple, l'affichage d'une publication est complexe. Heureusement, nous avons déjà isolé ce code dans un template dédié : il suffira de l'importer (avec `include`) dans votre `<turbo-stream>`...

2. Vérifiez que tout fonctionne. Vous pouvez notamment visualiser le contenu de la réponse du serveur (`F12` → `Réseau`) pour vérifier le contenu de la réponse renvoyée par le serveur.

</div>

Bien que ce système fonctionne, un détail gênant apparaît : le formulaire n'est plus vidé après l'envoi. Il y a plusieurs moyens de gérer cela. Par exemple, en écoutant l'événement `turbo:submit-end` sur le formulaire d'envoi d'un message (dans la balise `form`, avec `data-action`) en reliant cela à un nouveau controller stimulus attaché au formulaire qui appelle la fonction `reset` sur la cible de l'événement. Si le temps le permet, vous pouvez explorer cette piste.

Bref, comme vous venez de le voir, **Turbo** nous permet de réaliser des modifications chirurgicales sur notre page, tout en interagissant avec le back-end, sans avoir besoin d'écrire de JavaScript. Le mécanisme de suppression aurait aussi pu être réalisé d'une manière similaire :

```twig
{% raw %}
<!-- Template publication/stream/publication_remove_stream.html.twig -->
<turbo-stream action="remove" target="publication_{{ idPublication }}"></turbo-stream>
{% endraw %}
```

```twig
{% raw %}
<!-- Template publication/publication.html.twig -->
<div class="feedy" id="publication_{{ publication.id }}">
    <div class="feedy-header">
        ...
        <div class="feedy-info">
            ...
            {% if app.user and publication.auteur.id == app.user.id %}
                <form method="post" action="{{ path('supprimerPublication', {'id': publication.id}) }}">
                    <input type="submit" class="delete-feedy" value="Supprimer">
                </form>
            {% endif %}
        </div>
    </div>
</div>
{% endraw %}
```

```php
#[IsGranted('ROLE_USER')]
#[Route('/publications/supprimer/{id:publication}', name: 'supprimerPublication', methods: ['POST'])]
public function supprimerPublication(?Publication $publication, EntityManagerInterface $entityManager): Response {
    ...
    $idPublication = $publication->getId();
    $entityManager->remove($publication);
    $entityManager->flush();
    return  new TurboStreamResponse(
        $this->renderView('publication/stream/publication_remove_stream.html.twig', ["idPublication" => $idPublication])
    );
}
```

Bien que Turbo soit pratique, il ne permet pas un contrôle aussi libre que d'utiliser du JavaScript. Mais il peut être très utile si on ne veut pas s'encombrer de JavaScript et que l'on reste dans des opérations `CRUD` simples (pas besoin de route qui renvoi du JSON, pas besoin de code précis pour traiter la réponse, pas besoin du bundle spécial pour le routage, pas besoin d'exposer nos routes...).

Cependant, vous pouvez faire tout ce qu'il est possible de faire avec **turbo** (comme nous l'avons vu avec la suppression d'une publication) avec du JavaScript, il faudra juste potentiellement plus de code. Au premier abord, Turbo peut aussi sembler un peu dur à appréhender, contrairement à du JavaScript qui est plus "familier". L'utilisation de Turbo est aussi moins évolutif si l'on souhaite développer une application hybride qui sert aussi d'API pour des systèmes externes.

Bref, dans vos futures applications, vous pouvez choisir d'utiliser l'un ou l'autre, ou les deux en même temps, selon vos préférences et la complexité de l'opération à réaliser.

## The Feed Premium

Nous allons maintenant mettre en place un système de membre "premium" qui donne accès à des avantages sur le site, comme :

* Une couleur dorée au niveau du pseudonyme (sur les publications)

* Un plus grand nombre de caractères autorisés pour les publications.

Nous allons tout d'abord commencer par inclure toutes les fonctionnalités "premium". Plus tard, il sera possible de mettre en place un système de paiement pour permettre à nos utilisateurs d'acheter ce statut (TP bonus que vous pourrez faire après ce TD).

### Accès premium et pseudonyme doré

Il faut maintenant choisir la stratégie pour gérer le système "premium". Il y a deux possibilités :

* Définir et utiliser un nouveau rôle (par exemple, `ROLE_PREMIUM`).

* Définir un attribut (booléen) "premium" dans la classe Utilisateur.

Les deux solutions fonctionnent, mais la première est assez discutable et peut-être bonne ou mauvaise selon le contexte. Il faut bien distinguer la notion d'autorisation et l'accès à de nouvelles fonctionnalités. Une autorisation peut être par exemple de pouvoir supprimer un compte donné, ou alors, supprimer n'importe quel message (pour un rôle type "admin"). 

Dans notre cas, le fait d'afficher le pseudonyme en doré et de pouvoir écrire de plus longs messages relève plus de fonctionnalités qui deviennent "accessibles" au membre premium plutôt que d'une autorisation particulière. Nous allons donc plutôt nous orienter vers la deuxième solution. Si plusieurs formes de "premium" étaient possibles (différents plans) il faudrait plutôt créer une nouvelle entité "Plan" avec les informations, le prix, etc... Ici, nous n'aurons qu'un seul plan premium, donc l'attribut booléen suffit.

La question d'attribuer un rôle ou non pour ce genre de situation fait débat dans la communauté, et il n'y a pas vraiment de solution précise. Cependant, comme montré dans [ce fil de discussion](https://github.com/symfony/symfony/issues/39763#issuecomment-757493411), l'avis général des développeurs de Symfony est plutôt de ne pas faire de rôles dans ce genre de cas.

Ne pas avoir de rôle ne signifie pas que nous ne pourrons pas utiliser l'attribut `IsGranted` pour vérifier l'accès à certaines pages, par exemple, car il est possible d'accéder aux données l'utilisateur dans ce contexte (et donc vérifier s'il est premium ou non). Cependant, pour des permissions plus "avancées", il faudra utiliser le système de [*voter*](https://symfony.com/doc/current/security/voters.html) (électeur en français) dont nous reparlerons plus tard.

<div class="exercise">

1. Utilisez la commande `make:entity`, afin de rajouter un attribut de type `boolean` nommé `premium` à la classe `Utilisateur` qui ne doit pas pouvoir être **null** dans la base de données. Avant de mettre à jour la base de données, il faut penser à faire deux choses :

    * Donner la valeur `false` (au lieu de **null**) à votre propriété. Cela constitue sa valeur par défaut. Comme pour la date de publication, cette donnée doit être générée automatiquement par l'application quand un utilisateur s'inscrit. Pour la date, nous avions dû utiliser une méthode spéciale, car nous avions besoin d'utiliser un objet `DateTime`. Ici, comme c'est un booléen simple, on peut le faire directement lors de la définition de la propriété dans la classe.

    * Rajoutez le paramètre `options: ["default" => false]` dans l'attribut `ORM\Column` lié à cette propriété. Comme nous allons modifier la structure de la base, nous allons nous retrouver avec plusieurs utilisateurs qui ne possédaient pas cette propriété avant. Cette option permet d'effectuer la migration et indiquer à notre base de données quelle valeur placer pour `premium` pour les utilisateurs déjà existant. Ici, tous les utilisateurs déjà enregistrés ne sont pas membres premium, par défaut. Cette option est très utile pour ne pas "casser" la base en cas de mise à jour !

    Quand tout est prêt, mettez à jour votre base de données avec `make:migration` puis `doctrine:migrations:migrate`.

2. Modifiez le template `publication.html.twig` pour faire en sorte d'ajouter la classe `premium-login` (qui affiche le pseudonyme en doré) à l'élément `<span></span>` contenant le pseudonyme de l'auteur **si celui-ci est un membre premium**.

3. Dans votre base de données, modifiez un utilisateur pour lui donner le statut premium (dans la base de données, 0 == `false`, 1 == `true`). Observez que son pseudonyme est bien affiché différemment sur ses publications.

</div>

### Longueur des publications dépendante du premium

Nous souhaitons maintenant pouvoir fixer une limite plus grande pour le nombre de caractères autorisés sur une publication, selon si l'utilisateur est premium ou non. Pour cela, nous allons utiliser des **groupes de validation**.

Sur tous les attributs de contraintes/assertions, il est possible de définir un paramètre `groups`. Ce paramètre permet de lister ce qu'on nomme **groupes de validation**. La contrainte ne sera vérifiée que si elle possède un des groupes de validation actif.

Par défaut, le groupe `Default` est activé. Il n'y a pas besoin de le préciser au niveau des attributs, car toutes les contraintes qui ne précisent pas de groupes particuliers possèdent ce groupe, par défaut.

Cependant, il est tout à fait possible d'activer d'autres groupes de validation selon la situation, notamment dans la classe permettant de construire un formulaire, au niveau de la méthode `configureOptions`.

Prenons l'exemple suivant : on possède une entité "Message" qui possède une image. Au début, seuls les fichiers `.png` et `.jpg` sont autorisés. Je possède donc les classes suivantes

```php
class Message {

    #[File(
        maxSize: "5M",
        maxSizeMessage: "L'image ne peut pas dépasser 5Mo.",
        extensions: ["jpg", "png"],
        extensionsMessage: "Les seuls formats autorisés sont jpg et png."
    )]
    private ?UploadedFile $image = null;

}
```

```php
class MessageType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class
        ]);
    }
}
```
Maintenant, j'aimerais que le week-end, les utilisateurs puissent poster de plus grosses images et aussi des images au format `.gif`. La solution est de créer deux contraintes possédant des groupes de validation différents ! Puis, dans `MessageType`, je vérifie le jour de la semaine (avec un service, par exemple) et j'active le groupe adéquat. On configure le paramètre `validation_groups` dans les options du formulaire.

```php
class Message {

    #[File(
        groups: ["message:normal"]
        maxSize: "5M",
        maxSizeMessage: "L'image ne peut pas dépasser 5Mo (en semaine).",
        extensions: ["jpg", "png"],
        extensionsMessage: "Les seuls formats autorisés sont jpg et png (en semaine)."
    )]
    #[File(
        groups: ["message:weekend"]
        maxSize: "10M",
        maxSizeMessage: "L'image ne peut pas dépasser 10Mo.",
        extensions: ["jpg", "png", "gif"],
        extensionsMessage: "Les seuls formats autorisés sont jpg, png et gif"
    )]
    private ?UploadedFile $image = null;

}
```

```php
class MessageType extends AbstractType
{

    public function __construct(
        // Service fictif
        private DateServiceInterface $dateService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $group = $this->dateService->isWeekend() ? 'message:weekend' : 'message:normal';
        $resolver->setDefaults([
            'data_class' => Message::class,
            'validation_groups' => ['Default', $group]
        ]);
    }
}
```

Notez qu'il faut bien ajouter le groupe `Default` si l'on souhaite activer les contraintes sans groupes (il n'y en a pas dans notre exemple, mais il y aurait pu avoir d'autres propriétés, bien entendu). Attention Symfony est sensible à la casse à ce niveau (`Default` avec un 'D' majuscule).

Dans un formulaire, quand on crée le formulaire, il est aussi possible de configurer le groupe (si on ne le fait pas dans `configureOptions`). Cela peut être utile si on a des règles différentes entre la création d'une entité et sa mise à jour (par exemple, le mot de passe est obligatoire lors de la création d'un utilisateur, mais pas forcément pour la mise à jour...).

```php
//Dans une méthode d'un contrôleur
$form = $this->createForm(MonType::class, $entity, [
    "method" => '...',
    "action" => $this->generateUrl('maRoute'),
    "validation_groups" => ["Default", ...]
]);
```

<div class="exercise">

1. Modifiez les contraintes de votre entité `Publication` afin que le message puisse contenir jusqu'à 200 caractères si un des groupes de validation activé est `publication:write:premium` et jusqu'à 50 caractères si un des groupes activés est `publication:write:normal`.

2. Modifiez la classe `PublierType` pour activer le bon groupe selon la situation de l'utilisateur (premium ou non). Vous aurez besoin du service `Security`. Ce service vous permet de récupérer l'utilisateur courant. Attention, il faudra vérifier s'il n'est pas `null`, car le formulaire peut être généré (mais pas forcément montré) via la route `feed`, même pour un utilisateur déconnecté (si l'utilisateur n'est pas connecté ou non premium, on utilisera le groupe `publication:write:normal`) :

    ```php
    use Symfony\Bundle\SecurityBundle\Security;
    
    $user = $this->security->getUser();
    ```

    L'autocomplétion ne vous montrera pas forcément les attributs/méthodes de la classe `Utilisateur`, car on nous renvoie un objet de type `UserInterface`. Ce n'est pas grave, car en réalité, c'est bien notre entité `Utilisateur` qui est utilisée (et qui implémente justement cette interface).

    Comme d'habitude, il faudra penser à ajouter un constructeur dans `PublierType` afin de réaliser l'injection de dépendance nécessaire.

3. Utilisez un compte non premium et vérifiez que l'erreur apparaît bien si vous faites un message dépassant 50 caractères. Vérifiez également que l'erreur n’apparaît pas si vous faites la même chose sur un compte premium (mais que dans ce cas, la limite à 200 est toujours présente) !

4. Vous pouvez suivre la même logique afin d'adapter la contrainte cliente `maxlength` selon le statut de l'utilisateur (toujours dans `PublierType`).

</div>

### Page de présentation

Nous allons ajouter une simple page de présentation des fonctionnalités premium contenant un lien permettant de réaliser l'achat de ce statut.

Sur cette page, nous afficherons également le **prix** de vente. Comme ce prix est susceptible de changer (et pourra potentiellement être utilisé autre part), il serait judicieux de l'enregistrer comme paramètre (comme vous l'avez fait dans `services.yaml` pour le dossier d'upload des photos de profil) mais aussi de l'utiliser dans vos templates.

Vous savez déjà comment définir un paramètre :
```yaml
#config/services.yaml
parameters:
    serviceParameter: valeur
```
Pour pouvoir l'utiliser dans un template twig, il faudra l'injecter dans le template, depuis l'action utilisant le template dans le contrôleur.
Pour cela, il existe deux solutions :

* Utiliser l'attribut `#[Autowire(...)]` que nous avons déjà utilisé lors du précédent TP (mais cette fois, dans un contrôleur au lieu d'un service) :

    ```php
    #[Route('/maRoute', name: 'routeName', methods: ['GET'])]
    public function routeExemple(#[Autowire('%nom_parametre%')] $parametre): Response
    {
        return $this->render('chemin/vue.html.twig', [
            'parametre' => $parametre,
        ]);
    }
    ```

* Ou bien en utilisant la fonction `getParameter` dans le corps de la fonction :

    ```php
    #[Route('/maRoute', name: 'routeName', methods: ['GET'])]
    public function routeExemple(): Response
    {
        $parametre = $this->getParameter('nom_parametre');
        return $this->render('chemin/vue.html.twig', [
            'parametre' => $parametre,
        ]);
    }
    ```

<div class="exercise">

1. Créez un paramètre `premium_price` qui aura la valeur `100` (100 euros).

2. Créez un contrôleur `PremiumController` contenant le code d'une route `premiumInfos` qui possède pour chemin `/premium` et est seulement accessible avec la méthode `GET`. Cette route doit simplement générer et renvoyer une réponse en utilisant le template `premium/premium-infos.html.twig` (que nous allons créer juste après) en lui injectant le paramètre `premium_price`.

3. Dans `templates`, créez un dossier `premium` et à l'intérieur, un template `premium-infos.html.twig` qui devra reprendre la structure habituelle de notre site (donc qui étend un certain template...). La page aura pour titre `Premium` et aura pour contenu principal la structure suivante :

    ```html
    <main>
        <div id="premium-infos" class="center">
            <h3>Devenez membre premium et accédez aux avantages suivants:</h3>
            <p>Messages jusqu'à 200 caractères.</p>
            <p>Un superbe pseudonyme doré !</p>
            <a href=""><button id="btn-buy-premium">ACHETER MAINTENANT (prix €)</button></a>
        </div>
    </main>
    ```

    Dans le contenu du bouton, remplacez le **prix** par le prix (actuel) du premium en utilisant le paramètre injecté dans le template.

4. Dans votre template `base.html.twig`, ajoutez un lien vers la page d'infos sur le statut premium visible uniquement par les utilisateurs connectés, mais qui ne sont pas premium.

5. Vérifier que votre page s'affiche bien (la page en elle-même et son lien) quand vous êtes connectés avec un compte non-premium.

</div>

Actuellement, même si nous masquons le lien dans le menu de navigation, un utilisateur qui possède le statut premium peut quand même accéder à la page des informations et d'achat du premium. Ce qui ne devrait pas être le cas, un utilisateur étant déjà premium n'a pas à voir cette page. Mais comme nous n'avons pas de rôle "premium" (au sens des rôles de Symfony) nous ne pouvons pas utiliser l'attribut `IsGranted` comme auparavant... Ou peut-être que si ?

Rappelez-vous, sur les routes `connexion` et `inscription`, nous avions évoqué la possibilité d'utiliser `IsGranted` en formulant une condition complexe :

```php
#[IsGranted(new Expression("!is_granted('ROLE_USER')"))]
#[Route('/connexion', name: 'connexion', methods: ['GET', 'POST'])]
public function connexion(AuthenticationUtils $authenticationUtils) : Response {
    ...
}
```

Mais nous avions choisi de plutôt rediriger l'utilisateur. Cette fois-ci, nous allons utiliser `IsGranted` avec cette méthode afin de gérer l'accès aux pages relatives à l'achat du mode premium.

Dans cet exemple, on utilise un objet `Expression` pour construire notre condition. En fait, dans cette expression, il est même possible d'utiliser une variable `user` et d'accéder aux méthodes publiques de notre utilisateur ! Par exemple :

```php
#[IsGranted(new Expression("is_granted('ROLE_USER') and user.getAge() >= 12 and user.getAge() < 18"))]
#[Route('/forum/ado', name: 'forumAdo', methods: ['GET'])]
public function forumAdo() : Response {
    ...
}
```

Dans l'exemple ci-dessus, l'âge est stocké dans l'entité représentant nos utilisateurs. L'accès à cette page ne pourra se faire que si l'utilisateur est connecté, et s'il a entre 12 et 18 ans.

<div class="exercise">

1. Faites en sorte que votre route `premiumInfos` soit accessible aux utilisateurs possédant le rôle `ROLE_USER`, mais pas ceux qui sont déjà premium.

    Classes à importer :

    ```php
    use Symfony\Component\ExpressionLanguage\Expression;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    ```

2. Connectez-vous à un compte premium et vérifiez que la page n'est plus accessible (cela génère un message d'erreur détaillé en mode développement, mais en mode production, la page d'erreur que vous avez configuré lors du dernier TD sera affichée à la place). Connectez-vous ensuite à un compte non-premium et vérifiez que la page est accessible.

</div>

## Des permissions plus avancées

Dans cette section, nous allons voir comment affiner la gestion des permissions avec Symfony.

### Utilisation d'expressions dans l'attribut IsGranted

Actuellement, votre route `supprimerPublication` doit à peu près ressembler à ça :

```php
#[IsGranted('ROLE_USER')]
#[Route('/publications/{id:publication}', name: 'supprimerPublication', options: ["expose" => true], methods: ["DELETE"])]
public function supprimerPublication(?Publication $publication, EntityManagerInterface $entityManager) : Response {
    if($publication === null) {
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
    if($this->getUser() !== $publication->getAuteur()) {
        return new JsonResponse(null, Response::HTTP_FORBIDDEN);
    }
    $entityManager->remove($publication);
    $entityManager->flush();
    return new JsonResponse(null, Response::HTTP_NO_CONTENT);
}
```

Ici, nous avons notamment besoin de vérifier que l'utilisateur est bien l'auteur de la publication... Mais saviez-vous que nous pouvons aussi faire tout cela dans l'attribut `IsGranted` ? En effet, nous avons vu précédemment que nous pouvions accéder au paramètre `user` représentant l'utilisateur courant en utilisant un objet `Expression` dans l'attribut `IsGranted`. Il est aussi possible d'accéder à un des paramètres de la méthode et de l'utiliser dans notre condition. Pour cela, on ajoute un second paramètre (le `subject`) à notre attribut `IsGranted` en précisant le nom d'un de nos paramètres.

Par exemple :

```php
#[IsGranted(attribute: new Expression("is_granted('ROLE_USER') and subject.method() == user.method()"), subject: "monObjet")]
#[Route('/exemple/{id}', name: 'route_exemple'], methods: ["POST"])]
public function supprimerPublication(Exemple $monObjet) : Response {
    ...
}
```

Deux notes importantes :

* Le second paramètre de `IsGranted` est nommé `subject` et fait référence à un des paramètres de la méthode. Dans notre exemple, il s'agit donc dans `monObjet`. Ensuite, dans l'objet `Expression`, on fait référence à cet objet en utilisant le mot clé `subject`. Ici, `subject` représente donc `monObjet`. Et donc, quand on appelle `subject.method()` dans l'expression, c'est comme si on appelait `monObjet.method()`.

* Il faut enlever le `?` du type de l'objet (`Exemple` et pas `?Exemple`) Pour rappel, `?` autorise une valeur nulle. Ici, le fait de ne pas autoriser cela générera automatiquement une réponse **404** (not found) si l'utilisateur essaye d'accéder à un objet qui n'existe pas (identifiant invalide).

Normalement, vous devriez maintenant être en mesure de retravailler la logique de vérification du "propriétaire" d'une publication.

<div class="exercise">

1. Au niveau de la route `supprimerPublication`, utilisez vos nouvelles connaissances pour déplacer la logique vérifiant que l'utilisateur courant est bien le propriétaire de la publication vers votre attribut `IsGranted`.

2. Vérifiez que tout fonctionne comme attendu (supprimez des publications sur votre compte).

</div>

### Les voters

L'utilisation de `IsGranted` fonctionne bien, mais on reste encore dans des cas assez simples. Si la condition grandit (de nouveaux rôles, comme un administrateur, ayant tous les droits...) ou bien que la vérification devient plus compliquée (appel à des services, plusieurs lignes de code...), que doit-on faire ? Tout mettre dans le contrôleur ? Non ! Comme évoqué précédemment, Symfony possède un système avancé pour gérer les permissions : les **voters**.

Un **voter** est une classe listant des **permissions** (généralement liées à une entité, mais pas obligatoirement.). Lorsque le système vérifie une permission avec `isGranted` (avec une fonction ou un attribut), les **voters** sont sollicités au travers de deux méthodes :

* Une méthode qui détermine si la classe du **voter** peut traiter cette vérification (est-ce que c'est une permission qui lui est liée ou non...).

* Une méthode qui effectue la vérification et renvoie `true` ou `false` selon sa décision (accepte / refuse).

Comme plusieurs **voters** peuvent "voter" sur la décision à prendre pour une même permission, on peut configurer une stratégie au niveau de l'application :

* Si un seul des voters répond "oui", on accepte.

* Si un seul des voters répond "non", on refuse.

* Si la majorité des voters répondent "oui", on accepte.

* On retient le vote du voter ayant la priorité la plus haute.

Par défaut, la première stratégie est choisie. Il est aussi possible de configurer ses propres stratégies !

Dans la classe du Voter, on liste (généralement) les permissions gérées par la classe du Voter avec des constantes. La première méthode `supports` vérifiera que la permission vérifiée est bien une des constantes listées, et que le sujet de la permission (s'il y en a un) correspond au type d'entité géré par la classe (ce n'est pas obligatoirement le cas).

Les **voters** se placent dans le dossier `src/Security/Voter`. Il est possible d'injecter des services (et autres paramètres) via le constructeur.

```php
class ExempleVoter extends Voter
{
    //On fait la liste des permissions gérées par le Voter.
    public const EXEMPLE = 'PERM_EXEMPLE';

    public function __construct(/* Injection de services, si besoin*/)
    {
    }

    /*
    $attribute correspond à la permission vérifiée
    $subject correspond au sujet sur lequel la vérification est effectuée (par exemple, une publication, un utilisateur)
    Le sujet peut être éventuellement null!
    La méthode renvoie true si ce Voter est habilité à voter pour cette permission (et ce subject)
    */
    protected function supports(string $attribute, mixed $subject): bool
    {
        ...
    }

    /*
    Vote pour accorder la permission (ou non).
    Le paramètre $token nous donne accès à l'utilisateur.
    Le paramètre $vote permet d'ajouter des messages d'erreur personnalisés selon la raison pour laquelle une permission est refusée.
    */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        ...
        switch ($attribute) {
            case self::EXEMPLE:
                if(...) {
                    $vote?->addReason("...");
                    ...
                }
                return ...
            ...
        }
        ...
    }
}
```

Prenons l'exemple suivant : une application web permet à ses utilisateurs d'uploader et de partager des vidéos. Les données de la vidéo ne peuvent être modifiées que par l'utilisateur ayant uploadé la vidéo, pareil pour la suppression. Les vidéos peuvent être vues par tous les utilisateurs, sauf si la vidéo est privée. Certaines vidéos peuvent être inadaptées aux mineurs (contenu sensible, langage grossier...). Dans ce cas la vidéo ne peut pas être visionnée par un utilisateur ayant moins de 18 ans. Enfin, la vidéo peut éventuellement ne pas être visionnable dans certains pays.

Pour gérer ces permissions, je vais construire un voter `VoterVideo` qui contiendra deux permissions : `VIDEO_VIEW` (permission pour regarder une vidéo donnée) et une autre `VIDEO_EDIT` (pour avoir le droit d'éditer ou de supprimer une vidéo).

```php
//src/Security/Voter/VideoVoter.php
class VideoVoter extends Voter
{
    public const VIEW = 'VIDEO_VIEW';
    public const EDIT = 'VIDEO_EDIT';

    public function __construct()
    {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        //Je vote si la permission vérifiée est soit VIDEO_VIEW ou VIDEO_EDIT et que $subject est une instance de la classe Video.
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Video;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        //a ce stade, comme `supports` oblige $subject à être du type Video, je sais que $subject est une vidéo.

        //Je récupère l'utilisateur (null s'il n'est pas connecté)
        $user = $token->getUser();

        switch ($attribute) {
            case self::VIEW:
                if($subject->isPrivate() && ($user == null || $subject->getAuthor() != $user)) {
                    $vote?->addReason("La vidéo est privée.");
                    return false;
                }
                else if($subject->isAdultOnly() && ($user == null || $user.getAge() < 18)) {
                    $vote?->addReason("Vous n'avez pas l'âge requis pour regarde la vidéo.");
                    return false;
                }
                else if(!empty($subject->getBannedCountries()) && ($user == null || in_array($user->getCountry(), $subject->getBannedCountries()))) {
                    $vote?->addReason("La vidéo ne peut pas être vue dans votre pays.");
                    return false;
                }
                return true;
            case self::EDIT:
                return $user != null && $subject->getAuthor() == $user;
        }

        return false;
    }
}
```

L'appel à la méthode `addReason` sur `$vote` est tout à fait optionnel. Cela permet d'ajouter des messages personnalisés en cas de permission refusée. Cela peut servir dans certains cas au niveau de l'interface pour afficher des messages d'erreur (même si généralement, on fera en sorte de cacher à l'utilisateur les fonctions auxquelles il n'a pas accès) et surtout dans le cadre du développement d'une API.

Avez-vous remarqué la syntaxe `$objet?->methode(...)` ? Ici, on peut un opérateur dit **Null-safe** qui permet de faire en sorte que la méthode ne s'exécute que si `$objet` n'est **pas null** (autrement, cela provoquerait une erreur). C'est ce qu'on fait ici avec `$vote?->addReason(...)` car `$vote` peut être null.

Si toutes les permissions relatives à l'objet sont refusées automatiquement dans le cas où l'utilisateur n'est pas connecté, on peut ajouter ce bout de code au début de la fonction `voteOnAttribute` :

```php
if (!$user instanceof UserInterface) {
    $vote?->addReason('Vous devez être connecté pour réaliser des opérations sur cet objet...');
    return false;
}
```

Ce qui n'est pas pertinent dans notre exemple, parce qu'une vidéo publique peut être vue par tout le monde.

Enfin, dans mon contrôleur (ou ailleurs) dès que je veux contrôler l'autorisation, par exemple, quand un utilisateur accède à une vidéo, j'utilise la permission `VIDEO_VIEW` :

```php
#[IsGranted(attribute: 'VIDEO_VIEW', subject: 'video')]
#[Route('/watch/{id}', name: 'videoWatch', methods: ["GET"])]
public function watchVideo(Video $video): Response
{
    ...
}
```

On voit bien qu'il aurait été difficile de mettre toute la logique de la permission `VIDEO_VIEW` dans l'attribut `IsGranted` ! On peut aussi utiliser, à la place, la méthode `denyAccessUnlessGranted` :

```php
#[Route('/watch/{id}', name: 'videoWatch', methods: ["GET"])]
public function watchVideo($id, VideoRepository $videoRepository): Response
{
    $video = $videoRepository->find($id);
    $this->denyAccessUnlessGranted(`VIDEO_VIEW`, $video);
    ...
}
```

Ou bien :

```php
#[Route('/watch/{id}', name: 'videoWatch', methods: ["GET"])]
public function watchVideo($id, VideoRepository $videoRepository): Response
{
    $video = $videoRepository->find($id);
    if(!$this->isGranted(`VIDEO_VIEW`, $video)) {
        //Réponse customisée...
    }
    ...
}
```

Il est aussi tout à fait possible d'utiliser cette permission avec la méthode `is_granted` dans nos templates twig.

```twig
{% raw %}
{% if is_granted('VIDEO_VIEW', video) %}

{% endif %}
{% endraw %}
```

Si on a ajouté des messages personnalisés (en cas de permission refusée) et que l'on souhaite les afficher sur l'interface, on peut les récupérer ainsi :

```twig
{% raw %}
{% set voter_decision = access_decision('VIDEO_VIEW', video) %}
{% if voter_decision.isGranted %}
    {# ... #}
{% else %}
    <p>{{ voter_decision.message }}</p>
{% endif %}
{% endraw %}
```

Cependant, comme nous l'avons expliqué plus tôt, on utilisera plutôt rarement ces messages sur l'interface, donc, la première méthode avec `is_granted` sera largement suffisante pour la majorité des cas.

La commande suivante permet de générer une classe `NomEntiteVoter` contenant du code basique pour un **Voter**, lié à l'entité `NomEntite` :

```php 
php bin/console make:voter NomEntiteVoter
``` 
Cependant, encore une fois, il n'est pas obligatoire d'avoir des permissions liées spécifiquement à une entité !

<div class="exercise">

1. Créez un voter `PublicationVoter`, pour les permissions relatives aux objets de type `Publication` (facilitez-vous la vie, utilisez la commande !). Ce **voter** ne gérera qu'une permission (pour le moment) nommée `PUBLICATION_DELETE` (pour vérifier si l'utilisateur a le droit de supprimer une publication ou non, s'il en est bien l'auteur). Complétez la classe de manière adéquate : l'utilisateur a le droit de supprimer la publication seulement s'il est connecté et qu'il en est l'auteur.

2. Utilisez votre nouvelle permission au niveau de la route `supprimerPublication`.

3. Modifiez le template `publication.html.twig` pour utiliser `is_granted` pour afficher le bouton de suppression de la publication au lieu du code que vous utilisiez avant.

4. Vérifiez que tout fonctionne toujours.

</div>

### Rôle Admin

Nous allons maintenant créer et utiliser un véritable nouveau **rôle** qui aura plus de permissions. Ce rôle sera un administrateur qui aura tous les droits ! Et la mise en place de tout cela va être grandement facilité par le système de voter.

Il n'y a pas vraiment de procédure pour créer un nouveau rôle sur Symfony. En fait, on peut ajouter les rôles que l'on souhaite aux utilisateurs. Cependant, il faut impérativement que le nom du rôle débute par `ROLE_`.

Néanmoins, il faut penser à **hiérarchiser** les rôles. Cela consiste à dire que tel ou tel rôle est une version dérivée d'un rôle existant. Ainsi, un utilisateur possédant un rôle particulier aura ses propres privilèges en plus de ceux de tous les sous-rôles duquel le rôle est dérivé.

Tout cela se configure dans le fichier `config/packages/security.yaml` :

```yaml
#config/packages/security.yaml
security:
    
    ...

    role_hierarchy:
        ROLE_CUSTOM: ROLE_USER
        ROLE_SUPER_CUSTOM: ROLE_CUSTOM, ROLE_CUSTOM_2
```

Dans l'exemple ci-dessus, un utilisateur possédant le rôle `ROLE_CUSTOM` possède automatiquement tous les privilèges de `ROLE_USER` (en plus des siens). Enfin, `ROLE_SUPER_CUSTOM` possède les privilèges de `ROLE_CUSTOM`, `ROLE_CUSTOM_2` et aussi `ROLE_USER` (car `ROLE_CUSTOM` a les privilèges de `ROLE_USER`...).

<div class="exercise">

1. Dans le fichier `security.yaml`, définissez une hiérarchie pour le rôle `ROLE_ADMIN` (nouveau rôle) en faisant en sorte que celui-ci hérite de tous les privilèges du rôle de base : `ROLE_USER`.

2. Modifiez le voter `PublicationVoter` afin de voter favorablement si l'utilisateur possède le privilège `ROLE_ADMIN`. Pour cela, il vous faudra injecter et utiliser le service `AccessDecisionManagerInterface` afin d'utiliser la méthode `decide` sur l'objet `$token` donné dans `voteOnAttribute` :

    ```php
    use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

    $this->accessDecisionManager->decide($token, ["ROLE", "..."]);
    ```

    La fonction `decide` a le même objectif que `isGranted` : déterminer si l'utilisateur à une (ou plusieurs) permission(s) (ou certains rôles). La documentation de Symfony précise que si l'on souhaite vérifier des permissions à l'intérieur d'un voter, il faut impérativement utiliser cette méthode, et ne surtout pas appeler la méthode `isGranted` sur l'utilisateur récupéré via `getUser` dans le service **Security** (comme montré dans certains tutoriels, ou la documentation de versions antérieures de Symfony). De plus, la méthode `decide` permet aussi de vérifier les permissions d'autres utilisateurs.

3. Dans votre base de données, ajoutez le rôle `ROLE_ADMIN` à un utilisateur : affectez la valeur `["ROLE_ADMIN"]` dans le champ `roles`. Si vous êtes connecté avec ce compte, vous serez déconnecté après le changement de rôle, par mesure de sécurité.

4. Connectez-vous avec le compte admin. Si vous avez bien configuré votre voter, le bouton de suppression devrait alors apparaître sur toutes les publications !

</div>

Comme vous le constatez, les voters sont assez puissants ! L'intérêt est encore assez limité ici, mais nous pourrions rajouter plus de permissions dans la classe publication (edit, delete, etc...). Cette classe permet de centraliser toute la logique de vérification des permissions. Nous n'avons pas eu à répéter le code vérifiant le statut de l'utilisateur (propriétaire ou admin) à la fois dans le contrôleur et à la fois dans le template twig. On utilise simplement notre permission `PUBLICATION_DELETE`.

## Créer ses propres commandes

Dans cette section, nous allons voir comment créer nos propres commandes qui seront utilisables comme les autres commandes de Symfony, dans le terminal :

```php
php bin/console macommande ...
```

Il peut être très utile de créer des commandes pour assurer certaines opérations de maintenance ou d'administration du site. On peut aussi relier cela à un système qui exécutera périodiquement des commandes (par exemple, chaque semaine, chaque mois...). L'avantage (par rapport à un script classique) c'est qu'on est déjà dans l'environnement de l'application. On peut donc injecter et utiliser des services, des paramètres, etc...

Pour créer une commande, on va d'abord ajouter un dossier `src/Command` afin de placer nos commandes à l'intérieur.

Ensuite, on peut créer une classe dédiée pour chaque commande :

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    /* Nom de la commande, tel qu'on l'utilisera lors de l'exécution de php bin/console ... */
    name: 'nomcommande',
    /* Pour décrire ce que fait la commande, si l'utilisateur utilise l'option --help, par exemple. */
    description: '...',
)]
class MaCommande
{

    public function __construct(
        /* Injection de dépendances... */
    ) {}

    //C'est la fonction qui définit les paramètres de la commande et qui l'exécute
    public function __invoke(
        //Permet de gérer les messages d'entrées/sorties
        //Doit être placé avant les autres paramètres
        SymfonyStyle $io,
        
        //Premier argument (obligatoire)
        #[Argument(description: "...")] string $arg1,

        //Deuxième argument (obligatoire): pose une question en console à l'utilisateur
        #[Ask("Question...")] int $arg2,

        //Troisième argument (obligatoire): pose une question en console à l'utilisateur, et cache la valeur saisie (par exemple, pour un mon de passe...)
        #[Ask("Question...", hidden: true)] int $arg3,
                            
        //Quatrième argument (optionnel): à mettre après les arguments obligatoires.
        //Il est optionnel car on donne une valeur par défaut
        #[Argument(description: "...")] string $arg4 = "Valeur par défaut...",
                           
        //On peut configurer des options qui s'utilisent ainsi `--nomOption` n'importe où dans la commande, et qui donnent une valeur booléenne (activer, désactiver)
        #[Option(description:"...")] bool $option1 = false,
                        
        //On peut aussi définir une option à laquelle on associe une valeur `--nomOption=valeur`
        #[Option(description:"...")] int $option2 = 5
    ): int
   {

        //Exécution de la logique de la commande...

        //Pour afficher un message normal.
        $io->writeln("message");

        //Pour afficher un message de succès.
        $io->success("message");

        //Pour afficher un message d'erreur.
        $io->error("message");

        //Pour afficher un message d'avertissement.
        $io->warning("message");

        //On peut aussi utiliser $io->ask pour poser une question et récupérer des arguments de manière interactive... $io contient plein de méthodes utiles!

        /* 
        On retourne une des trois valeurs possibles:
        * Command::SUCCESS: la commande s'est bien exécutée (de bout en bout)
        * Command::INVALID: il y a un problème par rapport aux arguments passés.
        * Command::FAILURE: il y a eu un problème lors de l'exécution.
        */
        return Command::SUCCESS;

   }
}
```

Bien sûr, dans l'exemple, on utilise `$arg1`, `$option1`, etc, mais vous pouvez nommer les arguments et options comme vous voulez ! La description d'une commande (et de ses paramètres) peut être affichée avec :

```bash
php bin/console macommande --help
```

Dans `#[Argument]`, `#[Option]`, etc, il est aussi possible de changer le nom du paramètre affiché en console avec la propriété `name`, et de suggérer des valeurs avec `suggestedValues`. Il est aussi possible de créer une classe externe pour regrouper plusieurs arguments puis les utiliser dans la commande grâce à [l'attribut `#[MapInput]`](https://symfony.com/doc/7.4/console/input.html#mapping-input-to-objects).

{% comment %}
Pour initialiser la classe d'une commande, on exécute :

```bash
php bin/console make:command MaCommande
```
{% endcomment %}

Reprenons l'exemple du site de partage de vidéos. Je veux créer une commande qui me permet de supprimer une vidéo dont le code unique est passé en paramètre (pas l'id, mais une chaîne de caractère unique du style `whIu75m`, comme sur YouTube par exemple).

```php
#[AsCommand(
    name: 'delete:video',
    description: 'Delete a video (by using its unique identifier)',
)]
class DeleteVideoCommand
{
    public function __construct(
       private VideoRepository $videoRepository,
       private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: "The unique identifier of the video.")] string $videoCode,
    ): int
    {
        $video = $this->videoRepository->findOneBy(["videoCode" => $videoCode]);
        if($video === null) {
            $io->error("Video $videoCode not found.");
            return Command::FAILURE;
        }
        $this->entityManager->remove($video);
        $this->entityManager->flush();
        $io->success("The video $videoCode has been deleted !");
        return Command::SUCCESS;
    }
}
```

<div class="exercise">

1. Créez et testez la commande `GivePremiumCommand` nommée `give:premium` qui prend en paramètre le login d'un utilisateur et le rend membre premium. Pour récupérer l'utilisateur en question, il faudra utiliser `UtilisateurRepository`. Pour mettre à jour les données de l'utilisateur en base de données, il faudra utiliser le service `EntityManagerInterface`, comme quand vous créez une entité. Après avoir modifié les données de l'utilisateur, il suffit d'appeler `flush`.

2. Créez et testez la commande `RevokePremiumCommand` nommée `revoke:premium` qui prend en paramètre le login d'un utilisateur et le lui enlève le statut premium.

3. Créez et testez la commande `PromoteAdminCommand` nommée `promote:admin` qui prend en paramètre le login d'un utilisateur et lui donne le rôle `ROLE_ADMIN`. Vous aurez besoin d'ajouter la méthode suivante (pour ajouter un rôle) à la classe `Utilisateur` :

    ```php
    public function addRole($role) : void {
        if(!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
    }
    ```

4. Créez et testez la commande `RevokeAdminCommand` nommée `revoke:admin` qui prend en paramètre le login d'un utilisateur et lui enlève le rôle `ROLE_ADMIN`. Vous aurez besoin d'ajouter la méthode suivante (pour retirer un rôle) à la classe `Utilisateur` :

    ```php
    public function removeRole($role) : void {
        $index = array_search($role, $this->roles);
        //array_search renvoie soit l'index (la clé) soit false si rien n'est trouvé 
        //Préciser le !== false est bien nécessaire, car si le rôle se trouve à l'index 0, utiliser un simple if($index) ne vérifie pas le type! Et donc, si l'index retourné est 0, la condition ne passe pas...!
        if ($index !== false) {
            unset($this->roles[$index]);
        }
    }
    ```
</div>

## Conclusion

Avec ce TD, vous avez pu consolider votre maîtrise de Symfony et vous connaissez maintenant une grande partie du framework. À vous de vous lancer dans des projets plus complets afin de développer votre expérience avec cet outil.

Si vous avez un peu de temps, vous pouvez effectuer ce [TP bonus]({{site.baseurl}}/tutorials/tutorial_bonus1) qui consiste à la mise en place d'un système de paiement (pour obtenir le statut premium) sur notre site à l'aide de **Stripe**. Ce TP aborde aussi la notion de **webhook** qui est un mécanisme courant dès qu'on utilise un service externe.

Vous pouvez également consulter [cette note complémentaire]({{site.baseurl}}/complements/deploiement) et vous entraîner en déployant **The Feed** sur le serveur de l'IUT (pour avoir une première expérience à ce niveau, avant le premier projet).

Dans le prochain TD (le dernier consacré à Symfony), nous allons voir comment créer une **API REST** pour notre application "The Feed" avec un outil dédié : **API Platform.** Nous allons transposer tout ce que nous avons fait jusqu'ici sous la forme d'une API (donc, sans rendu HTML) qui pourra être utilisée par n'importe quel application : une application mobile, un autre service, ou bien une application **Vue.js**, ce qui sera l'objet des futurs TDs !