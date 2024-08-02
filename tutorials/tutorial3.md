---
title: TD3 &ndash; Amélioration du site
subtitle: Javascript, Premium, Système de paiement, Permissions, Administration, Commandes
layout: tutorial
lang: fr
---

## Introduction

Dans ce nouveau TD, nous allons améliorer le site en rajoutant diverses fonctionnalités qui vont vous permettre d'affiner et de renforcer votre maîtrise de Symfony.

Voici les nouveaux objectifs pour "The Feed" :

* Ajout de fonctionnalités asynchrones avec JavaScript (pour supprimer des publications).

* Ajout d'un système de membres "premium", vendu par le site via l'API Stripe.

Puis, en bonus :

* Système avancé de permissions.

* Ajout de fonctionnalités concernant l'administration du site.

* Ajout de commandes.

## JavaScript et fonctionnalités asynchrones

L'année dernière, vous avez découvert la possibilité d'avoir certaines fonctionnalités dîtes asynchrones afin d'effectuer des actions et recevoir des réponses sans avoir besoin de quitter la page courante, afin de rendre votre site plus dynamique. C'est d'ailleurs la logique au cœur des frameworks réactifs (Vue.js, Angular, React) qui seront abordés dans de prochains TPs.

Comme vous le savez, tout cela passe par JavaScript. Comme on souhaite ajouter la possibilité aux utilisateurs de supprimer leurs publications, il faudrait donc ajouter du JavaScript à notre site et une route prévue pour être utilisée de manière asynchrone (qui n'utilise pas twig et qui ne renvoie pas de page, mais éventuellement des données JSON). En effet, cette fonctionnalité est bien plus adaptée à un système asynchrone plutôt qu'à un formulaire qui ferait recharger la page entière.

Aussi, actuellement, notre route `deconnexion` est accessible en `GET`. Nous avions évoqué le fait qu'il serait plus judicieux et sécurisé d'avoir cette route en mode `POST` (ce qui n'est pas possible avec un lien simplement généré). Nous pourrons également gérer cela avec JavaScript.

### Prise en charge du JavaScript

Pour rappel, l'ajout de JavaScript à une page se passe de la même manière que l'ajout d'un fichier `css`, c'est-à-dire dans la partie `head` de la page, avec la balise suivante :

```html
<script defer type="text/javascript" src="chemin/fichier.js"></script>
```

On rappelle que, dans le cadre du framework, on ne doit toujours pas utiliser directement le **chemin** du fichier, mais plutôt la fonction `asset` pour générer le bon chemin depuis un template twig.

L'attribut `defer` permet de charger le fichier après que la page ait été chargée (par exemple, si le JavaScript doit aller récupérer des éléments HTML particuliers...)

Nous allons commencer par importer un fichier JavaScript simple sur la page principale et la page personnelle des utilisateurs. Pour l'instant, ce fichier permettra simplement de supprimer "visuellement" une publication de la page (mais pas encore réellement, elle sera toujours là au rechargement).

<div class="exercise">

1. Dans le dossier `public` (où sont rangés vos assets) créez un dossier `js` et importez [le fichier suivant]({{site.baseurl}}/assets/TD3/publications.js) (clic-droit puis "Enregistrer la cible du lien...").

2. Faites en sorte de charger ce fichier JavaScript, **uniquement dans les templates** `feed.html.twig` **et** `page_perso.html.twig`. Nous allons ajouter de nouvelles pages dans le futur, et il ne faut pas que ce fichier soit chargé dans ces autres pages. Indice : il vous faudra sans doute modifier `base.html.twig` en ajoutant un nouveau `block` redéfinissable !

3. Modifiez le template `publication.html.twig` afin de rajouter le bout de code HTML suivant, juste après l'élément `<p>...</p>` contenant le message de la publication : 

    ```html
    <button class="delete-feedy">Supprimer</button>
    ```

    Ce bouton ne doit apparaître que si l'utilisateur connecté est l'auteur de la publication ! Pour rappel, vous avez accès à la variable `app.user` dans vos templates `twig`... Attention, avant d'y accéder il faut d'abord bien vérifier que l'utilisateur est bien connecté !

4. Allez sur la page principale de votre site et vérifiez que :

    * Le bouton "Supprimer" apparait seulement sur les publications dont vous êtes l'auteur.

    * Le bouton fonctionne, c'est-à-dire que la publication est retirée de la page visuellement.

    Vérifiez également que tout fonctionne de même sur votre page personnelle.

    Si l'import du JavaScript ne fonctionne pas, vérifiez la console (`F12`) et également le code source de la page (`CTRL+U`)

</div>

### Suppression d'une publication

Maintenant que nous avons de quoi supprimer visuellement une publication de manière dynamique, il faut confirmer cette suppression côté back-end. Il faut aussi pouvoir générer le lien de la route dans notre fichier JavaScript, comme nous le faisons avec `path` dans nos templates.

Pour créer une route accessible par une requête HTTP exécutée en JavaScript (et qui ne renvoie pas de page mais plutôt des données), quelques éléments diffèrent :

```php
use Symfony\Component\HttpFoundation\JsonResponse;

 #[Route('/exemple', name: 'route_exemple', options: ["expose" => true], methods: ["POST"])]
public function methodeExemple(Request $request): Response
{
    //Recupération de données fournies dans le payload JSON
    $donnee = $request->get('donnee');

    //Traitement...

    //Renvoie d'une réponse au format JSON
    return new JsonResponse(contenu, codeReponse);
}
```

* Afin que le chemin de la route puisse être généré à partir de son nom (côté JavaScript), il faut **exposer la route** en ajoutant `options: ["expose" => true]` dans l'attribut contenant les meta-données de la route.

* Si des données `JSON` (ou autre) sont envoyées et doivent être lues, on peut les récupérer avec l'objet `Request`. En fait, cela marche de la même façon que pour récupérer des données depuis query string, ou bien même d'un formulaire...

* On renvoie un objet `JsonResponse` contenant éventuellement des données au format `JSON` (peut être `null`) et un code de réponse HTTP (200, 400, etc...)

Dans les premiers TDs, nous n'avons que lu ou créé des entités ! Pour en supprimer une, il faut là-aussi utiliser `EntityManagerInterface` (en l'injectant dans la méthode de la route) et utiliser la méthode `remove` (au lieu de `persist` qui créée / met à jour une entité).

```php
$entityManager->remove($entity);
$entityManager->flush();
```

Du côté de notre fichier JavaScript, nous n'avons pas accès à la fonction `path` comme dans nos templates twig! Pour remédier à cela, il suffit d'installer un `bundle` qui est un composant PHP prévu pour s'intégrer spécifiquement à Symfony.

Le bundle que nous allons utiliser s'appelle [FOSJsRoutingBundle](https://github.com/FriendsOfSymfony/FOSJsRoutingBundle/tree/master) et permet d'accéder à une fonction similaire à `path`, mais directement en JavaScript.

Comme pour tous les composants, il faut commencer par l'installer avec `composer` :

```bash
composer require friendsofsymfony/jsrouting-bundle
```

Selon votre configuration, il se peut que le bundle ne soit pas activé par défaut. Si ce n'est pas le cas, il suffit de rajouter une petite ligne de code dans le fichier `config/bundles.php` :

```php
//config/bundles.php
return [
    ...
    FOS\JsRoutingBundle\FOSJsRoutingBundle::class => ['all' => true],
];
```

Si la ligne est déjà présente, c'est que le bundle est déjà actif !

Ensuite, comme le bundle défini certaines `routes` qui lui sont spécifiques, il faut les enregistrer dans notre application. Pour notre nouveau bundle, on édite le fichier `config/routes.yaml` en ajoutant la ligne suivante :

```yaml
fos_js_routing:
    resource: "@FOSJsRoutingBundle/Resources/config/routing/routing-sf4.xml"
```

Enfin, certains **bundles** contiennent des assets (fichiers css, js, images, etc...) qu'il faut importer dans notre propre dossier d'assets, afin de pouvoir les utiliser. Pour cela, Symfony a prévu une commande :

```bash
php bin/console assets:install --symlink public
```

Vous remarquerez alors de nouvelles ressources dans votre dossier `public`. Il ne reste plus qu'à importer les fichiers JavaScript de ce bundle dans nos templates afin de pouvoir utiliser la fonction de routing. Il y a deux fichiers à importer :

```twig
{% raw %}
<script src="{{ asset('bundles/fosjsrouting/js/router.min.js') }}"></script>
<script src="{{ path('fos_js_routing_js', { callback: 'fos.Router.setData' }) }}"></script>
{% endraw %}
```

Une fois ces étapes complétées, nous avons alors accès (niveau JavaScript) à la fonction `Routing.generate`, sensiblement équivalente à `path` dans son utilisation :

```javascript
let URL = Routing.generate('maRoute');

//Et si on a une route paramétrable :
let URL = Routing.generate('maRoute', {"param": val, ...});
```

Nous allons mettre en place une route : `/feedy/{id}` qui sera accessible via la méthode `DELETE`.

Néanmoins, un problème subsiste : comment récupérer l'id de la publication associée au bouton "Supprimer" sur lequel on clique, pour le passer en paramètre de la route ?

Pour cela, nous pouvons utiliser un attribut `data-xxx` qui permet de créer des attributs "dynamiques" sur un élément HTML. **Attention**, le nom custom donné (`xxx`) suit des règles lexicographiques précises :

* Les mots sont séparés par des tirets.

* Pas de majuscules, ni de point virgules.

On pourra ensuite récupérer la valeur de cet attribut en JavaScript.

Par exemple :

```html
<button data-exemple-machin="test">Coucou</button>
```

Côté JavaScript, on utilise l'attribut `dataset` puis le nom `xxx` donné après le `data-` :

**Attention**, le nom de l'attribut sera à préciser en **camel case** :

```javascript
//On considère que la fonction "exemple" est attaché au bouton...
function exemple(event) {
    const button = event.target;

    //Contient "test"
    const exemple = button.dataset.exempleMachin;
}
```

Dans le HTML, mon attribut était nommé `data-exemple-machin`, ce qui donne en **camel case** : `exempleMachin`.

<div class="exercise">

1. Dans `PublicationController`, créez une route `deletePublication` possédant une route paramétrée `/feedy/{id}`, accessible via la méthode `DELETE` et **exposée**. Concrètement, il n'y a aucune donnée à lire (pas de payload, c-à-d de corps de requête) mais vous devez :

    * Récupérer la publication visée par l'identifiant donné dans la route.

    * Vérifier que la publication existe et que l'utilisateur courant en est bien l'auteur.

    * Supprimer la publication.

    * Renvoyer une réponse au format `JSON` ne contenant rien (**null**) et soit renvoyer le code :

        * `404` si la publication n'existe pas (ressource non trouvée).
        * `403` si l'utilisateur n'est pas auteur de la publication (opération interdite).
        * `204` si tout se passe bien (ce code signifie simplement que l'opération s'est bien passée, mais que la réponse ne contient aucune donnée)

    Souvenez-vous : lors du TD2, nous avions vu une méthode très simple pour récupérer une entité préciser à partir d'une route paramétrée, sans utiliser explicitement son repository !

2. En utilisant l'attribut `IsGranted`, faites en sorte que cette route soit accessible seulement aux utilisateurs connectés (possédant le rôle `ROLE_USER`). Allez consulter le TD2 si vous ne savez plus comment faire.

3. Installez `FOSJsRoutingBundle` et configurer tout ce qu'il faut pour pouvoir utiliser la fonction `Routing.generate`. Les deux fichiers JavaScript à importer sont globaux et devront donc être chargés sur toutes les pages (quel template faut-il modifier ?).

4. Modifiez le template `publication.html.twig` afin d'inclure un attribut `data-publication-id` contenant l'identifiant de la publication dans les attributs du bouton de suppression.

5. Dans le fichier `publications.js`, modifiez la fonction `supprimerFeedy` afin d'ajouter une requête asynchrone vers la route `deletePublication`. Vous pouvez notamment utiliser la fonction `fetch` et l'instruction `await` que vous devez maîtriser depuis les cours de JavaScript de l'année dernière ! Quelques petits rappels (et nouvelles précisions) :

    ```javascript
    //Comme on utilise le mot clé "await" dans le corps de la fonction, on doit rendre la fonction asynchrone.
    //Pour cela, on utilise le mot clé "async"
    async function maFonction() {

        //Les "headers" de la requête : on indique le type de données qu'on envoie
        const headers = new Headers();
        headers.append("Content-Type", "application/json");

        //Le payload contient les données (sous la forme d'un objet clé-valeur) qu'on souhaite envoyer avec la requête
        const payload = {donnee1 : ..., donee2: ..., ...};

        //On utilise le mot clé "await" pour "attendre" que la requête soit complètement éxécutée avant d'éxécuter les prochaines instructions.
        //Par conséquent, la fonction "maFonction" doit être asynchrone pour ne pas bloquer la page.
        //On précise l'URL de la requête.
        const response = await fetch(URL, {
            //La méthode utilisée (GET, POST, PUT, PATCH ou DELETE)
            method: "...",
            //On transforme le "payload" en chaîne de caractères.
            body: JSON.stringify(payload),
            headers: headers,
        });

        //Ici, on a la garantie que la requête a fini de s'éxécuter (on a un code de réponse, et éventuellement un résultat)
        if(response.status === ...) {
            //response.status permet d'accèder au code de réponse HTTP (200, 204, 403, 404, etc...)
        }


    }
    ```
    Comme la requête que nous souhaitons exécuter (suppression simple) n'a pas besoin de `payload`, on peut se passer de `headers` et de `body` :

    ```javascript
    async function maFonction() {
        const response = await fetch(URL, {method: "..."});
        if(response.status === ...) {/*...*/}
    }
    ```

    Au chargement de la réponse, il faudra déclencher la suppression (visuelle) de la publication sur la page (vous avez déjà le code pour cela dans le fichier) **si et seulement si le serveur a bien supprimé la publication**.


6. Testez que la suppression des publications fonctionne bien (ils ne réapparaissent pas après avoir rechargé la page). Si rien ne se passe, jetez un œil à la console (`F12`) pour lire les éventuels messages d'erreurs.
</div>

## The Feed Premium

Nous allons maintenant mettre en place un système de membre "premium" qui donne accès à des avantages sur le site, comme :

* Une couleur dorée au niveau du pseudonyme (sur les publications)

* Un plus grand nombre de caractères autorisé pour les publications.

Nous allons tout d'abord commencer par inclure toutes les fonctionnalités "premium" avant de mettre en place un système de paiement pour permettre à nos utilisateurs d'acheter ce statut.

### Accès premium et pseudonyme doré

Il faut maintenant choisir la stratégie pour gérer le système "premium". Il y a deux possibilités :

* Définir et utiliser un nouveau rôle (par exemple, `ROLE_PREMIUM`).

* Définir un attribut (booléen) "premium" dans la classe Utilisateur.

Les deux solutions fonctionnent, mais la première est assez discutable et peut être bonne ou mauvaise selon le contexte. Il faut bien distinguer la notion d'autorisation et l'accès à de nouvelles fonctionnalités. Une autorisation peut être par exemple de pouvoir supprimer un compte donné, ou alors, supprimer n'importe quel message (pour un rôle type "admin"). 

Dans notre cas, le fait d'afficher le pseudonyme en doré et de pouvoir écrire de plus longs messages relève plus de  fonctionnalités qui deviennent "accessibles" au membre premium plutôt que d'une autorisation particulière. Nous allons donc plutôt nous orienter vers la deuxième solution. Si plusieurs formes de "premium" étaient possibles (différents plans) il faudrait plutôt créer une nouvelle entité "Plan" avec les informations, le prix, etc... Ici, nous n'aurons qu'un seul plan premium, donc l'attribut booléen suffit.

La question d'attribuer un rôle ou non pour ce genre de situation fait débat dans la communauté, et il n'y a pas vraiment de solution précise. Cependant, comme montré dans [ce fil de discussion](https://github.com/symfony/symfony/issues/39763#issuecomment-757493411), l'avis général des développeurs de Symfony est plutôt de ne pas faire de rôles dans ce genre de cas.

Ne pas avoir de rôle ne signifie pas que nous ne pourrons pas utiliser l'attribut `IsGranted` pour vérifier l'accès à certaines pages, par exemple, car il est possible d'accéder aux données l'utilisateur dans ce contexte (et donc vérifier s'il est premium ou non). Cependant, pour des permissions plus "avancées", il faudra utiliser le système de [*voter*](https://symfony.com/doc/current/security/voters.html) (électeur en français) dont nous reparlerons plus tard.

<div class="exercise">

1. Utilisez la commande `make:entity`, afin de rajouter un attribut de type `boolean` nommé `premium` à la classe `Utilisateur` qui ne doit pas pouvoir être **null** dans la base de données. Avant de mettre à jour la base de données, il faut penser à faire deux choses :

    * Donner la valeur `false` (au lieu de **null**) à votre propriété. Cela constitue sa valeur par défaut. Comme pour la date de publication, cette donnée doit être générée automatiquement par l'application quand un utilisateur s'inscrit. Pour la date, nous avions dû utiliser une méthode spéciale, car nous avions besoin d'utiliser un objet `DateTime`. Ici, comme c'est un booléen simple, on peut le faire directement lors de la définition de la propriété dans la classe.

    * Rajoutez le paramètre `options: ["default" => false]` dans l'attribut `ORM\Column` lié à cette propriété. Comme nous allons modifier la structure de la base, nous allons nous retrouver avec plusieurs utilisateurs qui ne possédaient pas cette propriété avant. Cette option permet d'effectuer la migration et indiquer à notre base de données quelle valeur placer pour `premium` pour les utilisateurs déjà existant. Ici, tous les utilisateurs déjà enregistrés ne sont pas membre premium, par défaut. Cette option est très utile pour ne pas "casser" la base en cas de mise à jour !

    Quand tout est prêt, mettez à jour votre base de données avec `make:migration` puis `doctrine:migrations:migrate`.

2. Modifiez le template `publication.html.twig` pour faire en sorte d'ajouter la classe `premium-login` (qui affiche le pseudonyme en doré) à l'élément `<span></span>` contenant le pseudonyme de l'auteur **si celui-ci est un membre premium**.

3. Dans votre base de données, modifiez un utilisateur pour lui donner le statut premium (dans la base de données, 0 == `false`, 1 == `true`). Observez que son pseudonyme est bien affiché différemment sur ses publications.

</div>

### Longueur des publications dépendante du premium

Nous souhaitons maintenant pouvoir fixer une limite plus grande pour le nombre de caractères autorisés sur une publication, selon si l'utilisateur est premium ou non. Pour cela, nous allons utiliser des **groupes de validation**.

Sur tous les attributs de contraintes/assertions, il est possible de définir un paramètre `groups`. Ce paramètre permet de lister ce qu'on nomme **groupes de validation**. La contrainte ne sera vérifiée que si elle possède un des groupes de validation actif.

Par défaut, le groupe `Default` est activé. Il n'y a pas besoin de le préciser au niveau des attributs, car toutes les contraintes qui ne précisent par de groupes particuliers possèdent ce groupe, par défaut.

Cependant, il est tout à fait possible d'activer d'autres groupes de validation selon la situation, notamment dans la classe permettant de construire un formulaire, au niveau de la méthode `configureOptions`.

Prenons l'exemple suivant : on possède une entité "Message" qui possède une image. Au début, seuls les fichiers `.png` et `.jpg` sont autorisés. Je possède donc les classes suivantes

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

2. Modifiez la classe `PublicationType` pour activer le bon groupe selon la situation de l'utilisateur (premium ou non). Vous aurez besoin du service `Security`. Ce service vous permet de récupérer l'utilisateur courant. Attention, il faudra vérifier s'il n'est pas `null`, car le formulaire peut être généré (mais pas forcément montré) via la route `feed`, même pour un utilisateur déconnecté (si l'utilisateur n'est pas connecté ou non premium, on utilisera le groupe `publication:wdrite:normal`) :

    ```php
    use Symfony\Bundle\SecurityBundle\Security;
    
    $user = $this->security->getUser();
    ```

    L'autocomplétion ne vous montrera pas forcément les attributs/méthodes de la classe `Utilisateur`, car on nous renvoie un objet de type `UserInterface`. Ce n'est pas grave, car en réalité, c'est bien notre entité `Utilisateur` qui est utilisé (et qui implémente justement cette interface).

    Comme d'habitude, il faudra penser à ajouter un constructeur dans `PublicationType` afin de réaliser l'injection de dépendance nécessaire.

3. Utilisez un compte non premium et vérifiez que l'erreur apparaît bien si vous faites un message dépassant 50 caractères. Vérifiez également que l'erreur n'apparait pas si vous faites la même chose sur un compte premium (mais que dans ce cas, la limite à 200 est toujours présente) !

</div>

### Page de présentation

Nous allons ajouter une simple page de présentation des fonctionnalités premium contenant un lien permettant de réaliser l'achat de ce statut.

Sur cette page, nous afficherons également le **prix** de vente. Comme ce prix est susceptible de changer (et pourra potentiellement être utilisé autre part), il serait judicieux de l'enregistrer comme paramètre (comme vous l'avez fait dans `services.yaml` pour le dossier d'upload des photos de profil) mais également de l'utiliser dans vos templates.

Vous savez déjà comment définir un paramètre :
```yaml
#config/services.yaml
parameters:
    serviceParameter: valeur
```
Pour pouvoir l'utiliser dans un template twig, il faudra l'injecter dans le template, depuis l'action utilisant le template dans contrôleur.
Pour cela, il existe deux solutions :

* Utiliser l'attribut `#[Autowire(...)]` que nous avons déjà utilisé lors du précédent TP (mais cette fois, dans un contrôleur au lieu d'un service) :

    ```php
    #[Route('/maRoute', name: 'routeName', methods: ['GET'])]
    public function routeExemple(#[Autowire('%nom_parametre%')] $parametre): Response
    {
        return $this->render('chemin/vue.html.twig', [
            'parametre' => $parametre,
        ]);
    }
    ```

* Ou bien en utilisant la fonction `getParameter` dans le corps de la fonction :

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

3. Dans `templates`, créez un dossier `premium` et à l'intérieur, un template `premium-infos.html.twig` qui devra reprendre la structure habituelle de notre site (donc qui étend un certain template...). La page aura pour titre `Premium` et aura pour contenu principal la structure suivante :

    ```html
    <main>
        <div id="premium-infos" class="center">
            <h3>Devenez membre premium et accèdez aux avantages suivants :</h3>
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

Même si nous masquons le lien dans le menu de navigation, un utilisateur qui possède le statut premium peut quand même accéder à la page des informations et d'achat du premium. Ce qui ne devrait pas être le cas, un utilisateur étant déjà premium n'a pas à voir cette page. Mais comme nous n'avons pas de rôle "premium" (au sens des rôles de Symfony) nous ne pouvons pas utiliser l'attribut `IsGranted` comme auparavant... Ou peut-être que si ?

Rappelez-vous, sur les routes `connexion` et `inscription`, nous avions évoqué la possibilité d'utiliser `IsGranted` en formulant une condition complexe :

```php
#[IsGranted(new Expression("!is_granted('ROLE_USER')"))]
#[Route('/connexion', name: 'connexion', methods: ['GET', 'POST'])]
public function connexion(AuthenticationUtils $authenticationUtils) : Response {
    ...
}
```

Mais nous avions choisi de plutôt rediriger l'utilisateur. Cette fois-ci, nous allons utiliser `IsGranted` avec cette méthode afin de gérer l'accès aux pages relatives à l'achat du mode premium.

Dans cet exemple, on utilise un objet `Expression` pour construire notre condition. En fait, dans cette expression, il est même possible d'utiliser une variable `user` et d'accéder aux méthodes publiques de notre utilisateur ! Par exemple :

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

    Classes à importer :

    ```php
    use Symfony\Component\ExpressionLanguage\Expression;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    ```

2. Connectez-vous à un compte premium et vérifiez que la page n'est plus accessible. Connectez-vous à un compte non-premium et vérifiez que la page est accessible (cela génère un message d'erreur détaillé en mode développement, mais en mode production, la page d'erreur que vous avez configuré lors du dernier TD sera affichée à la place).

</div>

## Stripe

Nous allons maintenant mettre en place un système de paiement (en mode "test" mais qui peux réellement fonctionner). Pour cela, nous allons utiliser la plateforme de paiement `Stripe`.

Au même titre que PayPal, Stripe est un outil permettant aux entreprises de recevoir de l'argent en reliant leur compte à divers systèmes de paiement, par exemple, sur un site web. Ce genre d'outil dispose généralement d'une `API` qui permet son intégration à de nombreux systèmes de manière assez flexible. Et bien sûr, avant de recevoir de "vrai" paiement, ces plateformes mettent à disposition des développeurs des outils de tests pour simuler des paiements et vérifier que tout fonctionne.

Au niveau de PayPal, cet outil se nomme "PayPal Sandbox" et représente une copie complète de l'API et de l'environnement de PayPal, mais avec des comptes dédiés aux tests.

Quant à Stripe qui fonctionne avec un système de clés (publique/privées), il est simplement possible de passer en mode "test" et d'obtenir des clés dédiées aux tests pour simuler des paiements. Il est également possible de créer des faux clients, dans divers pays et la plateforme nous met à disposition des cartes bancaires factices qui fonctionnent uniquement en mode test.

Bien sûr, il existe bien d'autres plateformes, mais celles-ci sont les plus populaires. Hors des outils et des possibilités proposées par chaque plateforme, la principale différence va se situer au niveau du `fee` (frais de transaction) prélevé sur chaque transaction réelle (c'est ainsi que la plateforme fait du profit). On retrouve généralement un système de frais fixe (par exemple, 15 centimes) + un pourcentage sur le paiement reçu (par exemple, 3% du paiement reçu). Une entreprise doit donc prendre en compte le fait que si elle fait payer un produit ou un service, par exemple 15 euros, elle ne recevra pas ces 15 euros en totalité, car la plateforme prendra sa part.

Aujourd'hui nous allons uniquement utiliser Stripe en mode "test", donc aucune transaction réelle sera réalisée (inutile de vous munir de votre CB :D). Si dans le futur vous avez pour projet de monter un business réel, gardez en tête que vous devez avoir une entreprise (microentreprise, association ou autre) déclarée. Toute plateforme similaire vous demandera des informations et des documents légaux avant que vous puissiez transférer l'argent vers votre compte bancaire (le compte bancaire de l'entreprise). Cependant, ce que vous allez apprendre aujourd'hui sera facilement reproductible dans un cas concret. Il suffira de changer vos clés API de test par des clés réelles.

Dans le cadre de notre application, nous allons travailler avec un système de "webhook" qui suit la logique suivante :

* L'utilisateur souhaite acheter un produit / service sur notre site.

* L'application (côté back-end) génère un tableau contenant toutes les informations de la commande (noms des produits, quantité, prix...) et utilise la librairie de Stripe en donnant ces informations afin de générer un lien (vers Stripe) qui permettra de finaliser le paiement.

* L'utilisateur est redirigé vers le lien généré à l'étape précédente qui le mène hors du site (sur Stripe) pour finaliser le paiement.

* Lorsque le formulaire paiement est complété et validé, Stripe utilise un **webhook**. Cela signifie que Stripe envoi une requête à notre site web (pas depuis votre navigateur, mais depuis les serveurs de Stripe) informant que le paiement est valide (ou non, d'ailleurs).

* L'application reçoit la requête de Stripe sur une route dédiée. Elle vérifie l'état du paiement et **doit capturer le paiement** ou bien l'annuler. En effet, à ce stade, le client n'est pas encore réellement débité. Si pour certaines raisons, le service ou le produit n'est plus disponible à ce moment-là (rupture de stock, places limitées à un concert) alors l'application peut annuler le paiement. Si tout est bon, on demande à Stripe de "capturer" le paiement ce qui finalise l'opération. On peut alors réaliser diverses tâches annexes, comme envoyer un mail de confirmation.

* Une fois que la requête émise par Stripe a été traitée par notre application, l'utilisateur (qui est toujours sur la page de paiement Stripe) est redirigé sur notre site (sur une page configurée au préalable).

Globalement, tout cela peut s'illustrer avec le schéma ci-dessous.

<div style="margin:auto;width:75%">
 ![processus de paiement en utilisant stripe]({{site.baseurl}}/assets/TD3/stripe-payment-process.PNG)
</div>

Ce processus présente divers avantages :

* Aucune donnée bancaire ne transite sur notre application (le formulaire de paiement n'est pas affiché et traité sur notre site).

* Les actions post-paiement ne sont pas dépendantes du client (la requête de finalisation est envoyée par Stripe vers notre application, et non une requête émise par le client).

* Le système de **webhook** permet d'avoir une dernière étape pour confirmer le paiement côté back-end. Ainsi, s'il y a eu des problèmes divers entre temps, on peut annuler le paiement. Aussi, s'il y a un bug ou une panne du site, comme le paiement ne sera pas confirmé dans ce cas, l'argent ne sera pas débité.

Attention, dans un contexte réel, vous devez penser à la concurrence : pour rappel, en PHP, les requêtes sont traitées de manière parallèle. Imaginez la situation suivante : 

* On met en place une billetterie pour un concert, il ne reste plus qu'une place.

* Deux clients ont payé quasiment en même temps (à moins d'une seconde près). Stripe envoie donc deux requêtes à notre back-end, qui s'exécutent en parallèle !

* On va potentiellement se retrouver dans une situation où les deux clients se voient attribuer la même place ! Car le code gérant cette partie s'exécutera en même temps.

Généralement, pour régler ce genre de problème, on utilise des **verrous** pour que les autres requêtes attendent que la requête ayant déclenché le verrou termine son traitement. Ainsi, on aura un ordre d'exécution synchrone pour les requêtes qui accèdent à cette partie "sensible" du code, et on pourra annuler le paiement si on se rend compte qu'il ne reste plus de place...

Même si dans le cas de "The Feed", nous ne rencontrerons pas cette situation (pas de rupture de stock pour le premium) dans un contexte réel, il faut vous poser ces questions ! À partir du moment où vous manipulez de l'argent, les enjeux sont très sérieux.

Bref, après cette longue introduction, la première étape va être de récupérer notre clé privée de test.

<div class="exercise">

1. Pour récupérer votre clé de test, nous vous proposons deux choix (créer un compte ou non) :

    * Soit créer un compte Stripe [à cette adresse](https://dashboard.stripe.com/register). Attention, Stripe vous demandera éventuellement des informations sur votre entreprise après votre inscription, ne remplissez donc rien si c'est le cas (seule la partie "test" de l'application sera disponible, mais c'est ce qu'on veut !).

    * Soit utiliser [cette clé de test](https://gitlabinfo.iutmontp.univ-montp2.fr/progweb-but3/documents-utiles/-/blob/main/Cl%C3%A9_API_secr%C3%A8te__test__partag%C3%A9e_-_Stripe.txt) que nous avons créé pour vous, notamment si vous ne souhaitez pas créer de compte et communiquer vos informations personnelles. Cette clé sera partagée par tous vos collègues, mais ce n'est pas trop contraignant. Néanmoins, vous n'aurez pas accès au dashbord de Stripe pour visualiser les transactions, mais rien de gênant, nous pourrons suivre cela sur un terminal, à la place.

2. **Si vous avez décidé de créer un compte Stripe**, rendez-vous dans **Développeurs** (en haut à droite). Ensuite, sur la nouvelle page, accédez à l'onglet **Clés API** et cliquez sur "Révéler la clé secrète". Cette clé débute par `sk_test_`. Notez-la quelque part.

3. Stripe met à disposition des librairies dans de nombreux langages afin d'utiliser plus facilement son API. Installez celle destinée à PHP avec composer :

    ```bash
    composer require stripe/stripe-php 
    ```

</div>

### Création d'un paiement

La première étape est de générer un lien permettant de rediriger l'utilisateur vers Stripe qui permettra de procéder au paiement. Afin d'obtenir ce lien, il faut préciser les données de la transaction :

```php
use Stripe\Stripe;
use Stripe\Checkout\Session;

$paymentData = [
    'mode' => 'payment',
    'payment_intent_data' => ['capture_method' => 'manual', 'receipt_email' => '...'],
    'customer_email' => '...',
    'success_url' => '...',
    'cancel_url' => '...',
    "metadata" => ["cle" => 'valeur', '...' => '...'],
    "line_items" => [
        [
            "price_data" => [
                "currency" => "eur",
                "product_data" => ["name" => '...'],
                "unit_amount" => '...'
            ],
            "quantity" => '...'
        ],
        [
            '...'
        ]
    ]
];
Stripe::setApiKey(...);
$stripeSession = Session::create($paymentData);
$url = $stripeSession->url;
```

La partie `paymentData` est un tableau contenant toutes les informations sur la transaction que nous souhaitons réaliser :

* `mode` : définit le type de transaction : un paiement simple (`payment`), ou bien par exemple, la souscription à un abonnement. Dans notre cas, nous allons donc choisir l'option `payment` (sur notre site, on achète le statut premium une seule fois, et on le reste à vie).

* `payment_intent_data` : on indique qu'on veut capturer le paiement manuellement (c'est-à-dire avec un webhook, dans notre application) il faut aussi indiquer où sera envoyé le ticket de réception de la transaction (à quelle adresse email). En mode test, aucun n'email ne sera envoyé par Stripe.

* `customer_email` : Stripe a besoin de connaître l'adresse email de l'utilisateur (pour être affichée au vendeur, dans l'historique Stripe). Elle peut être différente de l'adresse où sera envoyé le ticket de caisse (mais c'est généralement la même). Nous ne sommes pas obligés de la remplir ici. Cela permet simplement de préremplir le champ correspondant sur le formulaire de Stripe.

* `success_url` : L'URL vers laquelle est redirigé l'utilisateur après que le paiement ait été traité (par Stripe et notre application).

* `cancel_url` : L'URL vers laquelle est redirigé l'utilisateur s'il décide d'annuler la transaction (via un bouton).

* `metadata` : un tableau contenant des données supplémentaires sur la transaction, qui pourront notamment être récupérées par notre back-end lors du déclenchement du **webhook**. Par exemple, on peut placer ici l'identifiant de l'utilisateur réalisant la transaction, pour le récupérer ensuite (c'est même quasiment obligatoire, car on rappelle que c'est Stripe qui utilise notre **webhook**, et pas l'utilisateur. Il faut donc un moyen d'identifier qui a payé).

* `line_items` : un tableau contenant plusieurs tableaux décrivant les produits de la transaction. Pour chaque produit, on remplit donc un tableau avec les informations suivantes :

    * `price_data` : Un tableau précisant la devise (dans notre cas, `eur` pour "euros"), des donnés supplémentaires, comme le nom du produit (dans un sous-tableau) et enfin, le prix unitaire (donc le prix d'un produit). Attention, le prix s'exprime en centimes. Donc, si je veux vendre un produit 25 € je mets 2500.

    * `quantity` : La quantité vendue pour ce produit.

On initialise ensuite le service `Stripe` avec la clé privée (celle récupérée dans l'exercice précédent) puis on génère l'URL vers laquelle rediriger l'utilisateur.

Nous allons construire un service dédié à la gestion des paiements. On aura une première méthode permettant de générer un lien de paiement pour un utilisateur donné. En plus des classes de Stripe (que vous devez simplement importer), vous aurez besoin du service `UrlGeneratorInterface` permettant de générer des URLs (relatives ou absolues) à partir d'un nom de route. Son fonctionnement est similaire à la fonction `path` que nous utilisons dans nos templates `Twig` :

```php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

//Pour générer une URL absolue
$url = $generator->generate(nomRoute, ["param" => ..., ], UrlGeneratorInterface::ABSOLUTE_URL)
```

Bien sûr, si la route n'est pas paramétrable, il suffit de préciser un tableau vide comme second argument. Le dernier paramètre `UrlGeneratorInterface::ABSOLUTE_URL` permet de générer une URL absolue. Vous en aurez besoin pour générer les liens pour les paramètres `success_url` et `cancel_url`.

Concernant notre clé secrète, il s'agit d'une donnée sensible à placer dans notre fichier `.env` ou `.env.local` (si on ne veut pas que la clé soit prise en compte par git). Dans un contexte réel, on pourrait aussi avoir un fichier `.env.dev` et placer la clé de test là-dedans et notre clé réelle dans `.env`. Faites **très attention** à ce que vous envoyez sur github/gitlab si votre repository est public. Pour rappel `.env` est versionné, mais pas `.env.local`. Dans le cas où vous manipuliez une véritable clé d'API, si elle se trouve dans `.env` et que votre repository est public, tout les personnes ayant accès au repository pourront la récupérer (comme vous identifiants BDD, etc...).


Bref, la clé pourra se déclarer comme n'importe quelle variable de ce fichier, par exemple :

```
MA_VAR=test
```

À partir de là, comme nous l'avions fait pour le service `UtilisateurManager`, le paramètre peut facilement être injecté via le constructeur d'un service avec l'attribut `#[Autowire]` en précisant toutefois le paramètre `env` : `#[Autowire(env : '...')]`.

Par exemple :

```php
class MonService {

    public __construct(#[Autowire(env: 'MA_VAR')] private $maVar) {}

}
```

<div class="exercise">

1. Dans le fichier `.env` (ou `.env.local`), créez une variable contenant la clé secrète de test.

2. Créez un service `PaymentHandler` contenant la méthode suivante :

    ```php
    //Génère et renvoie un lien vers Stripe afin de finaliser l'achat du statut Premium pour l'utilisateur passé en paramètre.
    public function getPremiumCheckoutUrlFor(Utilisateur $utilisateur)  : string {

    }
    ```
    * Pour les deux adresses emails à spécifier, on utilise celle de l'utilisateur.
    * `success_url` doit être un lien vers notre route `feed` (pour le moment).
    * `cancel_url` doit être un lien vers notre route `premiumInfos`.
    * Dans `metadata`, il faut placer l'identifiant de l'utilisateur, afin de pouvoir le récupérer plus tard et lui donner le statut premium lorsque Stripe fera appel à notre webhook.
    * Il n'y aura qu'un seul produit nommé "The Feed Premium" (et une quantité de "1" pour ce produit/service)
    * Pour le prix, vous pourrez réutiliser le paramètre `premium_price` défini dans `services.yaml`.

    Pensez à bien injecter tous les services/paramètres dont vous avez besoin.

3. Définissez une interface `PaymentHandlerInterface` pour votre service.

4. Dans `PremiumController`, ajoutez une route `premiumCheckout` ayant pour chemin `/premium/checkout` et accessible en `GET` qui devra simplement **générer le lien de paiement** en utilisant notre nouveau service et rediriger l'utilisateur vers ce lien. La route ne devra être accessible qu'aux membres connectés qui ne sont pas encore membres premium (comme pour la route `premiumInfos`).


    Pour rappel, dans un contrôleur, on peut directement accéder à l'utilisateur (`$this->getUser()`).

    Pour la redirection, il faudra utiliser la méthode `redirect` (du contrôleur) et non pas `redirectToRoute`. La méthode `redirect` permet de rediriger vers une URL (absolue) :

    ```php
     #[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
    public function methodeExemple(): Response
    {
        return $this->redirect("http://coucou.com");
    }
    ```

5. Modifiez le template `premium-infos.html.twig` en modifiant l'élément `<a></a>` attaché au bouton afin de placer un lien vers notre nouvelle route.

6. Rendez-vous sur la page d'informations du statut premium et cliquez sur le bouton. Vous devriez alors être redirigé sur Stripe. Vérifiez que le récapitulatif présenté est correct (email, prix, quantité, nom...). Vérifiez également que le bouton d'annulation vous renvoie bien sur la bonne page (informations du premium).

</div>

### Webhook

Il ne nous reste plus qu'à finaliser le paiement et la demande en créant une route type `webhook` accessible par Stripe. Pour cela, il va falloir donner à Stripe une `route` vers laquelle envoyer la requête de confirmation quand la plateforme reçoit un paiement. Aussi, nous allons devoir sécuriser cette route afin que seul Stripe puisse y envoyer des requêtes !

Mais, dans le cas où vous développez votre application sur un serveur local (localhost), comment Stripe peut-il envoyer des requêtes vers votre site ? Pour régler ce problème, Stripe met à disposition un outil très complet à utiliser sur votre machine (en ligne de commande) pour simuler différents événements et aussi spécifier vers quelle adresse envoyer les requêtes post-paiement.

Cet outil est connecté à votre compte Stripe à distance et, dès qu'un paiement est reçu, l'événement est transmis à votre machine, au niveau de l'outil qui s'occupera alors de transmettre la requête vers l'adresse précisée. Comme votre outil tourne en local, sur votre machine, il est donc possible d'accéder à des adresses locales, comme `localhost` !

Dans un premier temps, nous allons voir comment installer et configurer cet outil.

<div class="exercise">

1. Suivez les instructions pour [télécharger Stripe CLI](https://stripe.com/docs/stripe-cli?locale=fr-FR#install) sur votre machine, selon votre système d'exploitation.

2. Une fois l'exécutable de Stripe extrait, placez le n'importe où sur votre machine (par exemple dans un dossier **Stripe**).

3. Ouvrez un terminal dans le répertoire où se trouve l'exécutable puis exécutez la commande suivante :

    ```bash
    ./stripe login --interactive
    ```

    Il s'agit de la syntaxe Linux. Sur Windows, il faut bien sur rajouter `.exe`.

4. À l'étape d'après, collez votre clé secrète de test (clic-droit pour coller dans le terminal). Par sécurité, la clé n'est pas affichée (comme quand vous tapez un mot de passe dans un terminal, sous Linux), validez simplement après avoir collé la clé. On vous demande ensuite un nom pour votre machine. Un nom est proposé par défaut, vous pouvez valider ou le changer.

5. Exécutez la commande suivante :

    ```bash
    ./stripe listen
    ```

    Une **signature** secrète de webhook vous est donné. Conservez-la quelque-part.

6. Pour vérifier que tout est bien connecté, allez sur votre site puis accédez à la page du paiement sur Stripe. Validez le paiement en entrant les informations [d'une des cartes bancaires de test](https://stripe.com/docs/testing?locale=fr-FR#cards) mises à disposition par Stripe. Une fois validé, regardez votre terminal, vous devriez capter l'événement ! (si vous utilisez le compte "commun", vous capterez aussi les paiements des autres étudiants, mais ça ne sera pas trop gênant pour la suite.)

7. Quittez le programme (pour le moment).

</div>

Le client est prêt à être utilisé et est bien relié à votre compte Stripe (ou au compte commun si vous n'avez pas créé de compte). La **signature** secrète qui vous a été donnée est très importante. Elle permettra de vérifier dans le code de votre route (le webhook) que la requête a bien été émise par Stripe.

Nous allons diviser la gestion de la requête en deux parties :

* Vérification de l'identité de la requête (avec la signature) et extraction des données (dans un contrôleur).

* Traitement de la demande, vérification d'éventuels problèmes, capture du paiement et changement de rôle de l'utilisateur (dans un service).

Globalement, au niveau du contrôleur, pour la route traitant le **webhook** on va avoir la structure de code suivante :

```php
use Stripe\Webhook;

//On extrait le contenu de la requête (format imposé par Stripe, on utilise pas les outils de Symfony dans ce cas)
$payload = @file_get_contents('php://input');

//On extrait la signature de la requête
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
try {
    /*
    On construit l'événement.
    On utilise $secretSignature qui contient la signature secrète récupérée plus tôt (dans le terminal)
    Si la signature n'est pas bonne (vérifié avec la signature de la requête et celle secrète), une exception est déclenchée.
    */
    $event = Webhook::constructEvent($payload, $sig_header, $secretSignature);

    /*
    On vérifie le type d'événement.
    Pour l'instant, nous ne traitons que l'événement checkout.session.completed qui est déclenché quand l'utilisateur valide le formulaire et le paiement est prêt à être capturé
    Si l'application vient à évoluer, on pourrait traiter d'autres événements
    */
    if ($event->type == 'checkout.session.completed') {
        /*
        $session contient les données du paiement.
        On pourra notamment accèder aux meta-données que nous avions initialement placé lors de la création du paiement
        */
        $session = $event->data->object;
        //On imagine que $service est un service contenant une méthode permettant de traiter la suite de la requête.
        $service->traitement($session);
        //Si on arrive là, tout s'est bien passé, on renvoie un code de succès à Stripe.
        return new Response(null, 200);
    }
    else {
        //Si on arrive là, c'est qu'on ne gère pas l'événement déclenché, on renvoie alors un code d'erreur à Stripe.
        return new Response(null, 400);
    }
} catch(\Exception $e) {
    /*
    Ici, la signature n'est pas vérifiée, ou une autre erreur est survenue pendant le traitement.
    On renvoie donc un code d'erreur à Stripe.
    */
    return new Response($e->getMessage(), 400);
}
```

Pour le reste du traitement (qui sera délégué à un service), plusieurs objets et méthodes nous intéressent :

```php
use Stripe\StripeClient;

//On peut récupérer les meta-données depuis les données de la session transmises par Stripe
$metadata = $session["metadata"];

//Avant d'extraire une donnée, on peut bien sûr vérifier sa présence...
if(!isset($metadata["dataExemple"])) {
    throw new \Exception("dataExemple manquant...");
}

//L'objet "paymentIntent" permet de capturer (confirmer) ou d'annuler le paiement.
$paymentIntent = $session["payment_intent"];
//Pour réaliser ces opérations, on a besoin d'un objet StripeClient initialisé avec notre clé secrète d'API.
$stripe = new StripeClient(cleSecreteApi);

//On récupère de données
$dataExemple = $metadata["dataExemple"];

//Pour "capturer" et valider le paiement
$paymentCapture = $stripe->paymentIntents->capture($paymentIntent, []);
//On peut ensuite vérifier si le paiement a bien été capturé (si oui, on dispose de l'argent sur le compte Stripe, à ce stade).
if($paymentCapture == null || $paymentCapture["status"] != "succeeded") {
    throw new \Exception("Le paiement n'a pas pu être complété...");
}

//Après avoir fait diverses vérifications et avoir capturé le paiement avec succès, on peut réaliser nos actions complémentaires (dans notre cas, mettre l'attribut "premium" de l'utilisateur cible à true, puis sauvegarder).
```

Pour récupérer l'utilisateur ciblé à partir de son identifiant, vous pourrez bien sûr injecter et utiliser le **repository** de l'entité.

Pour mettre à jour les données de l'utilisateur en base de données, il faudra utiliser le service `EntityManagerInterface`, comme quand vous créez une entité. Après avoir modifié les données de l'utilisateur, il suffit d'appeler `persist` et `flush`, comme d'habitude.

Il n'y a pas (encore) de méthode dans notre entité `Utilisateur` permettant d'ajouter un rôle. Nous l'ajouterons dans le prochain exercice.

<div class="exercise">

1. Dans votre service `PaymentHandler`, ajoutez et complétez la méthode suivante :

    ```php
    use Exception;

    /**
    * @throws Exception
    */
    public function handlePaymentPremium(Session $session) : void {
        
    }
    ```

    Pour l'instant, cette méthode doit simplement : 
    
    * Récupérer l'identifiant de l'utilisateur dans les métadonnées de l'objet `$session` (le nom correspond à celui que vous aviez donné dans `getPremiumCheckoutUrlFor`), 
    
    * Récupérer l'objet utilisateur lié à cet id.

    * Valider le paiement (et vérifier qu'il est bien confirmé) puis changer la propriété "premium" de l'utilisateur (en la passant à `true`).
    
    * Sauvegarder ces modifications en base de données.

    N'oubliez pas d'ajouter les injections de dépendances nécessaires dans votre service. Vous aurez notamment besoin de quoi aller récupérer des utilisateurs dans la base de données, de quoi les sauvegarder, la clé d'api, le prix du premium, de quoi générer des URL... Et pensez également à mettre à jour son **interface** avec la signature de la méthode `handlePaymentPremium`.

2. **Si vous utilisez le compte commun** et pas votre propre compte Stripe, nous avons un "léger" soucis à régler. En effet, comme expliqué plus tôt, vous allez recevoir les événements déclenchés par tous les autres étudiants utilisant le compte commun. Il faut donc trouver un moyen d'identifier vos requêtes de manière unique et d'ignorer celles des autres. Nous vous proposons donc les ajouts suivants :

    * Dans la méthode `getPremiumCheckoutUrlFor`, ajoutez dans le tableau `metadata` un attribut `studentToken` avec un pseudonyme ou un code que vous choisissez (assurez-vous de ne pas avoir le même qu'un autre étudiant...)

    * Dans la méthode `handlePaymentPremium`, vérifiez que cet attribut est bien là et possède la valeur que vous aviez configurée. Sinon, on lève une exception :

    ```php
    $metadata = $session["metadata"];
    if(!isset($metadata["studentToken"]) || $metadata["studentToken"] !== "votre_code_perso") {
        throw new \Exception("Requête d'un autre étudiant...");
    }
    ```

    Ce bricolage permet de rejeter les requêtes qui ne vous sont pas destinées. Bien sûr, dans un cas réel, il n'y a pas de tel système à mettre en place, vous aurez votre propre compte et ne capterez pas d'événement indésirable. Il s'agit juste d'une astuce pour que le TD se passe bien si vous utilisez le compte partagé !

3. Enregistrez la signature secrète que vous aviez récupéré dans le terminal (lors de l'exécution du client Stripe) comme une **variable** de l'application définie dans `.env` ou `.env.local`.

4. Créez un nouveau contrôleur `WebhookController`. À l'intérieur, ajoutez une nouvelle route nommée `stripeWebhook` ayant pour chemin `/webhook/stripe` accessible en `POST` (Stripe envoie sa requête en `POST`). Dans le code de cette route, vous devrez vérifier la signature de la requête, puis extraire les données (l'objet "session") et enfin appeler la méthode `handlePaymentPremium` de notre service. Vous pourrez reprendre la structure du code présenté plus tôt.

    Niveau injection de dépendance, vous devrez bien sûr injecter notre service `PaymentHandlerInterface`, mais nous devons aussi accéder à la signature secrète que nous avons défini à l'étape précédente. Pour cela, deux possibilités :
    
    Vous pouvez injecter ce paramètre comme dans les services, en utilisant l'attribut `#[Autowire(env: '...')]` dans les paramètres de la méthode liée à la route.

</div>

Tout est prêt pour finaliser notre système de paiement ! Avant de traiter les cas d'erreurs, nous allons vérifier que dans un scénario normal, le système fonctionne comme attendu.

Pour que Stripe utilise notre **webhook**, nous devons lui indiquer l'URL à laquelle il se trouve. Pour le mode "test", cela se fait donc avec le client que vous avez utilisé plus tôt, en ligne de commandes. Il suffit d'exécuter la commande suivante :

```bash
./stripe listen --forward-to http://exemple.com
```

Par défaut, nous écoutons **tous les événements** en lien avec notre compte Stripe. Pour filtrer et n'utiliser que celui qui nous intéresse pour ce webhook, on utilise l'option `--events` :

```bash
./stripe listen --events=checkout.session.completed --forward-to http://exemple.com
```

Ici, dès que l'événement correspondant à la validation du formulaire de paiement est émis, Stripe enverra une requête à l'adresse précisée (à travers notre machine, car le client est connecté à notre compte).

Ainsi, il est possible d'avoirs plusieurs **webhooks** différents, pour plusieurs événements.

<div class="exercise">

1. Utilisez la commande `listen` du client Stripe en précisant l'URL pointant vers la route de votre site correspondant au **webhook** créé lors de l'exercice précédent (quelque chose comme `http://adressedusite/webhook/stripe`).

2. Testez d'acheter du mode premium (comme tout à l'heure, en utilisant une [carte bancaire de test](https://stripe.com/docs/testing?locale=fr-FR#cards)).

3. Jetez un coup d'œil au terminal, si le code `200` apparaît quelque part, cela doit être bon. Attention toutefois, si vous utilisez le compte partagé, peut-être que vous verrez le résultat de la requête d'un autre étudiant qui a effectué un paiement en même temps que vous.

   **Aide pour déboguer :** Si vous possédez votre propre compte Stripe et que le code `400` apparaît et que vous n'arrivez pas à savoir pourquoi, allez dans le terminal exécutant `stripe listen` et cliquez sur le lien ressemblant à `evt_xxx`. Après connexion à Stripe, vous trouverez en bas de la page dans la section *Réponses webhook CLI* la réponse HTTP que vous avez envoyé à Stripe. En la déroulant, vous verrez le message de l'exception qui a causé le code `400`.

4. Vérifiez sur le site que l'utilisateur est bien devenu membre premium.

</div>

Si tout à bien marché, félicitations, votre système de paiement est fonctionnel ! Pour une entreprise réelle, l'adresse du **webhook** concret (utilisé avec la clé réelle pour recevoir des paiements et pas celle de test) ne se configure pas via le terminal, mais dans un onglet dédié du dashboard de Stripe. Dans ce cas, l'adresse ciblée doit être publiquement accessible (pas de localhost ou autre). Le **webhook** cible un site déjà hébergé/en production. Comme dans le terminal, il est possible de filtrer les événements qui déclenche l'appel au webhook. Il est donc aussi possible de créer plusieurs webhooks, pour des événements différents.

La signature secrète de requête que vous possédez ne sera pas aussi la même entre le mode test et le mode production. Une fois l'entreprise enregistrée, vous avez accès à toutes les données nécessaires pour réellement recevoir des paiements (clé privée, signature...)

Idéalement, lors du développement d'un système de paiement, on teste d'abord que tout fonctionne en local avec les clés de tests et avec le client Stripe, en réalisant des paiements factices (comme ce qu'on fait ici) puis, quand le site est publié, on utilise un fichier de configuration contenant la clé et la signature "réelle" puis on renseigne la véritable adresse du webhook au niveau du dashboard. 

Bref, tout cela ne vous concerne pas vraiment pour le moment, car nous nous limitons au mode "test" dans le cadre de ce TD, bien entendu.

### Scénarios d'erreur

Nous allons maintenant gérer quelques scénarios d'erreurs, où il faut donc annuler le paiement :

* L'utilisateur ciblé n'existe pas. Par exemple, l'utilisateur a supprimé son compte (une fonctionnalité que nous n'avons pas encore développée, mais qui pourrait être là dans le futur).

* L'utilisateur est déjà membre premium : par exemple, vous avez ouvert deux formulaires de paiement et vous les remplissez à la suite. Il ne faut pas débiter le client deux fois !

* Le paiement n'a pas pu être capturé, pour diverses raisons.

Dans chaque cas, il faut **annuler le PaymentIntent** et lever une Exception (ce qui renverra donc un code 400 à Stripe).

Pour **anuller* un `PaymentIntent`, on peut utiliser la méthode `cancel` :

```php
$stripe->paymentIntents->cancel($paymentIntent);
```

<div class="exercise">

1. Modifiez la méthode `handlePaymentPremium` pour gérer ces trois scénarios d'erreur.

2. Vérifiez les deux premiers scénarios d'erreur (le troisième est plus difficile à simuler) :

    * Pour le premier, créez un compte, connectez-vous, ouvrez le formulaire de paiement, supprimez l'utilisateur dans la base de données, simulez le paiement. Le paiement ne devrait pas avoir lieu.

    * Pour le second, connectez-vous à un compte non-premium, ouvre deux fois le formulaire de paiement et simulez deux paiements. Vérifiez que le deuxième paiement n'aboutit pas (code 400 sur le client Stripe).

</div>

### Confirmation du paiement

Actuellement, quand le paiement est validé, l'utilisateur est redirigé sur la page d'accueil sans aucun message pour l'informer du déroulement de l'opération. C'est un peu rude ! Heureusement, Stripe a prévu un système pour qu'on puisse récupérer les informations sur le déroulement de la transaction (si notre webhook l'a bien validé ou non, par exemple).

L'idée est la suivante :

* Ajouter **l'identifiant de la session Stripe** dans le **query string** quand on génère le lien de paiement.

* Quand l'utilisateur est redirigé, on extrait l'identifiant depuis l'URL, on récupère les données de la session grâce à son identifiant, puis celles du paiement afin de vérifier son état. Si le paiement a bien été confirmé, on affiche un message de confirmation, sinon, on affiche un message d'erreur.

Pour rappel, le **query string** est la partie de l'URL contenant des paramètres supplémentaires : `http://exemple.com/route?param1=exemple&param2=exemple`

Cela diffère de nos routes "paramétrée" où les paramètres font parties de la route en elle-même.

Avec Symfony, il y a deux moyens d'extraire les données contenues dans le "query string".

Soit en utilisant l'objet Request :

```php
use Symfony\Component\HttpFoundation\JsonResponse;

 #[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(Request $request): Response
{
    $param1 = $request->get('param1');
    $param2 = $request->get('param2');
    ...
}
```

Ou bien en utilisation l'attribut `#[MapQueryParameter]`, dans les paramètres de la méthode :

```php
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(#[MapQueryParameter] string $param1, #[MapQueryParameter] string $param2): Response
{
    ...
}
```

Il faut alors bien sûr que le nom des paramètres de la méthode correspondent exactement au nom des paramètres dans le query string.

Du côté de Stripe, il ne nous est pas possible de rentrer nous-même l'identifiant de la session, car il n'est pas connu à ce stade (on est justement en train de créer la session...). Il faut simplement utiliser la chaîne littérale `{CHECKOUT_SESSION_ID}` afin d'indiquer à Stripe qu'il faudra remplacer cette chaîne lors de la redirection de l'utilisateur, après le paiement :

```php
$paymentData = [
    ...
    'success_url' => $this->urlGenerator->generate('maRoute', [], UrlGeneratorInterface::ABSOLUTE_URL).'?sessionId={CHECKOUT_SESSION_ID}',
    ...
];
```

**Note** : Dans l'absolu, on aurait pu utiliser une route paramétrée, mais le service `UrlGeneratorInterface` échappe les caractères spéciaux de l'URL (comme `{` et `}`) ce qui fait que Stripe n'aurait alors pas reconnu `{CHECKOUT_SESSION_ID}` et n'aurait donc pas effectué le remplacement.

Enfin, une fois l'identifiant de session récupéré, on peut vérifier l'état du paiement ainsi :

```php
//On initialise le client Stripe avec notre clé secrète
$stripe = new StripeClient(cleSecreteApi);

//On récupère les données de la session à partir de l'identifiant de la session
$session = $stripe->checkout->sessions->retrieve($sessionId);

//On extraie l'identifiant du paiement depuis la session
$paymentIntentId = $session->payment_intent;

//On récupère les données du paiement 
$paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);

//L'état "succeeded" signifie que le paiement a bien été capturé (le client a été débité)
$status = $paymentIntent->status;
```

Maintenant, à vous de jouer !

<div class="exercise">

1. Dans votre service `PaymentHandler`, ajoutez et complétez la méthode suivante :

    ```php
    //Renvoie true si le paiement lié à la session dont l'identifiant est passé en paramètre a aboutit (a été capturé...) et renvoie false sinon.
    public function checkPaymentStatus($sessionId) : bool {

    }
    ```

    Mettez également à jour **l'interface** de ce service.

2. Dans `PremiumController`, créez une route `premiumCheckoutConfirm` ayant pour chemin `/premium/checkout/confirm` et accessible avec `GET` seulement. Cette route a pour vocation d'être appelée avec un paramètre dans le query string, contenant l'identifiant de la session Stripe lié au paiement que l'utilisateur a effectué. Vous nommerez ce paramètre comme vous voulez (vous devrez utiliser le même nom dans la prochaine étape).

    Le but de la route est de vérifier l'état du paiement puis, si tout s'est bien passé, d'ajouter un message flash de confirmation "Paiement confirmé. Vous êtes maintenant membre premium !" et sinon, un message flash d'erreur "Une erreur est survenue lors du paiement. Veuillez réessayer.". Dans tous les cas, l'utilisateur est ensuite redirigé vers la page principale (route `feed`).

3. Modifiez la méthode `getPremiumCheckoutUrlFor` de votre service `PaymentHandler` afin que le lien de redirection après paiement pointe sur votre nouvelle route. Vous ferez en sorte d'ajouter l'identifiant de la session dans le query string.

4. Pour vérifier que tout fonctionne, exécutez le scénario suivant :

    * Depuis un compte non-premium, ouvrez deux fois le formulaire de paiement.

    * Complétez et validez le premier : lorsque vous êtes redirigé, le message de confirmation devrait être affiché.

    * Complétez le second : cette fois-ci, après redirection, le message d'erreur devrait être affiché (le paiement n'a pas abouti, car vous êtes déjà membre premium !)
</div>

Voilà, notre système de membre premium est complet ! Attention toutefois, dans un contexte réel, il y aurait un autre cas d'erreur à gérer (peu probable, mais qui peut arriver) : comme dans le dernier scénario, l'utilisateur ouvre deux fois le formulaire, mais cette fois, il les valide quasi simultanément. Il est possible que Stripe envoi donc deux requêtes pour déclencher votre **webhook** quasi simultanément. Comme les deux requêtes s'exécutent alors en parallèle, sur la seconde, la vérification que l'utilisateur n'est pas déjà membre premium pourrait passer, car la première requête n'a pas fini de s'exécuter ! Dans ce cas-là, comme expliqué plus tôt, il faut utiliser un système de "verrou" pour bloquer le code de la méthode `handlePaymentPremium`. Diverses librairies vous permettent de faire cela plus ou moins facilement. En tant que développeur, vous devez réfléchir à tous les problèmes qui peuvent découler de ce genre de système !

Stripe propose aussi un système d'identification qui permet de ne pas exécuter deux fois des paiements considérés identiques (même service). Il faut alors fournir un identifiant spécial qui permet à Stripe si deux paiements sont équivalents. On utilise pour cela l'attribut `idempotency_key` lors de la création du paiement, dans un tableau `$options` passé comme second paramètre de la fonction `Session::create`. Dans notre cas, on pourrait générer un identifiant unique qu'on stockerait dans les informations de l'utilisateur dans la base quand l'utilisateur clique sur le bouton de "Acheter" (sur notre site). Il s'agirait de la clé d'idempotence. Si le client re-essaye d'acheter à nouveau (avant d'avoir validé le paiement), la clé n'est pas écrasée. Quand le paiement est terminé (ou mieux, si l'utilisateur annule son mode premium, si on ajoute cette fonctionnalité) cette clé est supprimée de la base. Cela nous éviterait aussi de gérer certains cas d'erreurs que nous avons gérés plus tôt (paiement si l'utilisateur déjà premium, par exemple).

De manière générale, il faudrait plutôt gérer les paiements dans des objets dédiés à part (par exemple, un objet commande, etc...). Il serait créé lors de l'intention de paiement et supprimé une fois le paiement traité. La clé d'idempotence pourrait être l'identifiant (ou une valeur unique) lié à cet objet.

Bref, tout ceci n'était qu'une introduction, et nous n'avons malheureusement pas plus de temps pour aller dans le détail. Cependant, dans la mise en place réelle d'un système de paiement, il faut impérativement prendre en compte et utiliser ces systèmes de sécurisation !

## Bonus

Nous allons maintenant aborder quelques sections bonus, optionnelles. Si le temps le permet, vous pouvez vous y intéresser afin d'approfondir la maîtrise de cet outil.

### Des permissions plus avancées

Actuellement, votre route `deletePublication` doit à peu près ressembler à ça :

```php
#[IsGranted('ROLE_USER')]
#[Route('/feedy/{id}', name: 'deletePublication', options: ["expose" => true], methods: ["DELETE"])]
public function deletePublication(?Publication $publication, EntityManagerInterface $entityManager) : Response {
    if($publication === null) {
        return new JsonResponse(null, 404);
    }
    if($this->getUser() !== $publication->getAuteur()) {
        return new JsonResponse(null, 403);
    }
    $entityManager->remove($publication);
    $entityManager->flush();
    return new JsonResponse(null, 204);
}
```

Ici, nous avons notamment besoin de vérifier que l'utilisateur est bien l'auteur de la publication... Mais saviez-vous que nous pouvons aussi faire tout cela dans l'attribut `IsGranted` ? En effet, nous avons vu précédemment que nous pouvions accéder au paramètre `user` représentant l'utilisateur courant en utilisant un objet `Expression` dans l'attribut `IsGranted`. Il est aussi possible d'accéder à un des paramètres de la méthode et de l'utiliser dans notre condition. Pour cela, on ajoute un second paramètre (le `subject`) à notre attribut `IsGranted` en précisant le nom d'un de nos paramètres.

Par exemple :

```php
#[IsGranted(attribute: new Expression("is_granted('ROLE_USER') and subject.method() == user.method()"), subject: "monObjet")]
#[Route('/exemple/{id}', name: 'route_exemple'], methods: ["POST"])]
public function deletePublication(Exemple $monObjet) : Response {
    ...
}
```

Deux notes importantes :

* Le second paramètre de `IsGranted` est nommé `subject` et fait référence à un des paramètres de la méthode (représentant généralement une entité mappée avec `#[MapEntity]`). Dans notre exemple, il s'agit donc dans `monObjet`. Ensuite, dans l'objet `Expression`, on fait référence à cet objet en utilisant le mot clé `subject`. Ici, `subject` représente donc `monObjet`. Et donc, quand on appelle `subject.method()` dans l'expression, c'est comme si on appelait `monObjet.method()`.

* Il faut enlever le `?` du type de l'objet (`Exemple` et pas `?Exemple`) Pour rappel, `?` autorise une valeur nulle. Ici, le fait de ne pas autoriser cela générera automatiquement une réponse **404** si l'utilisateur essaye d'accéder à un objet qui n'existe pas (identifiant invalide).

Normalement, vous devriez maintenant être en mesure de retravailler la logique de vérification du "propriétaire" d'une publication.

<div class="exercise">

1. Au niveau de la route `deletePublication`, utilisez vos nouvelles connaissances pour déplacer la logique vérifiant que l'utilisateur courant est bien le propriétaire de la publication vers votre attribut `IsGranted`.

2. Vérifiez que tout fonctionne comme attendu (supprimez des publications sur votre compte).

</div>

Tout cela fonctionne bien, mais on reste encore dans des cas assez simples. Mais si la condition grandit (de nouveaux rôles, comme un administrateur, ayant tous les droits...) ou bien que la vérification devient plus compliquée (appel à des services, plusieurs lignes de code...), que doit-on faire ? Tout mettre dans le contrôleur ? Non ! Comme évoqué précédemment, Symfony possède un système avancé pour gérer les permissions : les **voters**.

Un **voter** est une classe listant des **permissions** (généralement liées à une entité, mais pas obligatoirement.). Lorsque le système vérifie une permission avec `isGranted` (avec une fonction ou un attribut), les **voters** sont sollicités au travers de deux méthodes :

* Une méthode qui détermine si la classe du **voter** peut traiter cette vérification (est-ce que c'est une permission qui lui est liée ou non...).

* Une méthode qui effectue la vérification et renvoie `true` ou `false` selon sa décision (accepte / refuse).

Comme plusieurs **voters** peuvent "voter" sur la décision à prendre pour une même permission, on peut configurer une stratégie au niveau de l'application :

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
    $subject correspond au sujet sur lequelle la vérification est effectué (par exemple, une publication, un utilisateur)
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
    */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        ...
        switch ($attribute) {
            case self::EXEMPLE:
                return ...
            ...
        }
        ...
    }
}
```

Prenons l'exemple suivant : une application web permet à ses utilisateurs d'uploader et de partager des vidéos. Les données de la vidéo ne peuvent être modifiées que par l'utilisateur ayant uploadé la vidéo, pareil pour la suppression. Les vidéos peuvent être vues par tous les utilisateurs, sauf si la vidéo est privée. Certaines vidéos peuvent être inadaptées aux mineurs (contenu sensible, langage grossier...). Dans ce cas la vidéo ne peut pas être visionnée par un utilisateur ayant moins de 18 ans. Enfin, la vidéo peut éventuellement ne pas être visionnable dans certains pays.

Pour gérer ces permissions, je vais construire un voter `VoterVideo` qui contiendra deux permissions : `VIDEO_VIEW` (permission pour regarder une vidéo donnée) et une autre `VIDEO_EDIT` (pour avoir le droit d'éditer ou de supprimer une vidéo).

```php
//src/Security/Voter/VideoVoter.php
class VideoVoter extends Voter
{
    public const VIEW = 'VIDEO_VIEW';
    public const EDIT = 'VIDEO_EDIT';

    public function __construct()
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        //Je vote si la permission vérifiée est soit VIDEO_VIEW ou VIDEO_EDIT et que $subject est une instance de la classe Video.
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Video;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        //a ce stade, comme `supports` oblige $subject à être du type Video, je sais que $subject est une vidéo.

        //Je récupère l'utilisateur (null s'il n'est pas connecté)
        $user = $token->getUser();

        switch ($attribute) {
            case self::VIEW:
                if($subject->isPrivate() && ($user == null || $subject->getAuthor() != $user)) {
                    return false;
                }
                else if($subject->isAdultOnly() && ($user == null || $user.getAge() < 18)) {
                    return false;
                }
                else if(!empty($subject->getBannedCountries()) && ($user == null || in_array($user->getCountry(), $subject->getBannedCountries()))) {
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

Enfin, dans mon contrôleur (ou ailleurs) dès que je veux contrôler l'autorisation, par exemple, quand un utilisateur accède à une vidéo, j'utilise la permission `VIDEO_VIEW` :

```php
#[IsGranted(attribute: 'VIDEO_VIEW', subject: 'video')]
#[Route('/watch/{id}', name: 'videoWatch', methods: ["GET"])]
public function watchVideo(#[MapEntity] Video $video): Response
{
    ...
}
```

On voit bien qu'il aurait été difficile de mettre toute la logique de la permission `VIDEO_VIEW` dans l'attribut `IsGranted` ! On peut aussi utiliser, à la place, la méthode `denyAccessUnlessGranted` :

```php
#[Route('/watch/{id}', name: 'videoWatch', methods: ["GET"])]
public function watchVideo($id, VideoRepository $videoRepository): Response
{
    $video = $videoRepository->find($id);
    $this->denyAccessUnlessGranted(`VIDEO_VIEW`, $video);
    ...
}
```

Ou bien :

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

La commande suivante permet de générer une classe `NomEntiteVoter` contenant du code basique pour un **Voter**, lié à l'entité `NomEntite` :

```php 
php bin/console make:voter NomEntiteVoter
``` 
Cependant, encore une fois, il n'est pas obligatoire d'avoir des permissions liées spécifiquement à une entité !

<div class="exercise">

1. Créez un voter `PublicationVoter`, pour les permissions relatives aux objets de type `Publication`. Ce **voter** ne gérera qu'une permission (pour le moment) nommée `PUBLICATION_DELETE` (pour vérifier si l'utilisateur a le droit de supprimer une publication ou non, s'il en est bien l'auteur). Complétez la classe de manière adéquate : l'utilisateur a le droit de supprimer la publication seulement s'il en est l'auteur.

2. Utilisez votre nouvelle permission au niveau de la route `deletePublication`.

3. Modifiez le template `publication.html.twig` pour utiliser `is_granted` pour afficher le bouton de suppression de la publication au lieu du code que vous utilisiez avant.

4. Vérifiez que tout fonctionne toujours.

</div>

### Rôle Admin

Nous allons maintenant créer et utiliser un véritable nouveau **rôle** qui aura plus de permissions. Ce rôle sera un administrateur qui aura tous les droits! Et la mise en place de tout cela va être grandement facilité par le système de voter.

Il n'y a pas vraiment de procédure pour créer un nouveau rôle sur Symfony. En fait, on peut ajouter les rôles que l'on souhaite aux utilisateurs. Cependant, il faut impérativement que le nom du rôle débute par `ROLE_`.

Néanmoins, il faut penser à **hiérarchiser** les rôles. Cela consiste à dire que tel ou tel rôle est une version dérivée d'un rôle existant. Ainsi, un utilisateur possédant un rôle particulier aura ses propres privilèges en plus de ceux de tous les sous-rôles duquel le rôle est dérivé.

Tout cela se configure dans le fichier `config/packages/security.yaml` :

```yaml
#config/packages/security.yaml
security:
    
    ...

    role_hierarchy:
        ROLE_CUSTOM: ROLE_USER
        ROLE_CUSTOM2: ROLE_USER
        ROLE_SUPER_CUSTOM : ROLE_CUSTOM, ROLE_CUSTOM2
```

Dans l'exemple ci-dessus, un utilisateur possédant le rôle `ROLE_CUSTOM` possède automatiquement tous les privilèges de `ROLE_USER` (en plus des siens). Pareil pour `ROLE_CUSTOM2`. Enfin, `ROLE_SUPER_CUSTOM` possède les privilèges de `ROLE_CUSTOM`, `ROLE_CUSTOM2` et aussi `ROLE_USER` (car `ROLE_CUSTOM` et/ou `ROLE_CUSTOM2` dérivent de `ROLE_USER`...).

<div class="exercise">

1. Dans le fichier `security.yaml`, définissez une hiérarchie pour le rôle `ROLE_ADMIN` (nouveau rôle) en faisant en sorte que celui-ci hérite de tous les privilèges du rôle de base : `ROLE_USER`.

2. Modifiez le voter `PublicationVoter` afin de voter favorablement si l'utilisateur possède le privilège `ROLE_ADMIN`. Pour cela, il vous faudra injecter et utiliser le service `Security` qui permet d'utiliser la méthode `isGranted` :

    ```php
    use Symfony\Bundle\SecurityBundle\Security;

    $this->security->isGranted(role)
    ```

3. Dans votre base de données, ajoutez le rôle `ROLE_ADMIN` à un utilisateur : affectez la valeur `["ROLE_ADMIN"]` dans le champ `roles`. Si vous étiez connecté avec ce compte, vous serez déconnecté après le changement de rôle, par mesure de sécurité.

4. Connectez-vous avec le compte admin. Si vous avez bien configuré votre voter, le bouton de suppression devrait alors apparaître sur toutes les publications !

</div>

Comme vous le constatez, les voters sont assez puissant ! L'intérêt est encore assez limité ici, mais nous pourrions rajouter plus de permissions dans la classe publication (edit, delete, etc...). Cette classe permet de centraliser toute la logique de vérification des permissions. Nous n'avons pas eu à répéter le code vérifiant le statut de l'utilisateur (propriétaire ou admin) à la fois dans le contrôleur et à la fois dans le template twig. On utilise simplement notre permission `PUBLICATION_DELETE`.

### Créer ses propres commandes

Pour finir, nous allons voir comment créer nos propres commandes qui seront utilisables comme les autres commandes de Symfony, dans le terminal :

```php
php bin/console macommande ...
```

Il peut être très utile de créer des commandes pour assurer certaines opérations de maintenance ou d'administration du site. On peut aussi relier cela à un système qui exécutera périodiquement des commandes (par exemple, chaque semaine, chaque mois...). L'avantage (par rapport à un script classique) c'est qu'on est déjà dans l'environnement de l'application. On peut donc injecter et utiliser des services, des paramètres, etc...

Pour initialiser la classe d'une commande, on exécute :

```
php bin/console make:command MaCommande
```

Ce qui génère une classe `MaCommande` dans le dossier `src/Command`. Faisons un tour des possibilités proposées par cette classe :

```php
#[AsCommand(
    /* Nom de la commande, tel qu'on l'utilisera lors de l'éxécution de php bin/console ... */
    name: 'nomcommande',
    /* Pour décrire ce que fait la commande, si l'utilisateur utilise l'option --help, par exemple. */
    description: '...',
)]
class MaCommande extends Command
{
    public function __construct(
        /* Injection de dépendances... */
    ) {
        //Il faut quand même appeller le constructeur parent (de la classe Command)
        parent::__construct();
    }

    //On configure les paramètres de la commande ici
    protected function configure(): void
    {
        $this
            //Argument (se place dans l'ordre, quand on écrit la commande)
            //Peut être obligatoire ou optionnel
            ->addArgument('arg1', InputArgument::REQUIRED, "Argument description")
            ->addArgument('arg2', InputArgument::OPTIONAL, "Argument description")

            //On peut configurer des options qui s'utilisent ainsi `--nomOption` dans la commande. Il n'y a pas de valeur associée à l'option, contrairement aux arguments. Il s'agit simplement d'une option qu'on active ou non.
            ->addOption('nomOption', null, InputOption::VALUE_NONE, "Option description")
        ;
    }

    //Méthode déclenchée lors de l'éxécution de la commande.
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Permet de gérer les messages d'entrées/sorties
        $io = new SymfonyStyle($input, $output);

        //On récupère un argument
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            //Permet de vérifier que l'argument est défini ou non (s'il est optionnel)
        }

        //Permet de voir si une option est activée.
        if ($input->getOption('nomOption')) {
            // ...
        }

        //Pour afficher un message normal.
        $io->writeln("message");

        //Pour afficher un message de succès.
        $io->success("message");

        //Pour afficher un message d'erreur.
        $io->error("message");

        //Pour afficher un message d'avertissement.
        $io->warning("message");

        //On peut aussi utiliser $io->ask pour poser une question et récupérer des arguments de manière intéractive... $io contient plein de méthodes utiles!

        /* 
        On retourne une des trois valeurs possibles :
        * Command::SUCCESS : la commande s'st bien éxécutée (de bout en bout)
        * Command::INVALID: il y a un problème par rapport aux arguments passés.
        * Command:FALIURE : il y a eu un problème lors de l'éxécution.
        */
        return Command::SUCCESS;
    }
}
```

Reprenons l'exemple du site de partage de vidéos. Je veux créer une commande qui me permet de supprimer une vidéo dont le code unique est passé en paramètre (pas l'id, mais une chaîne de caractère unique du style `whIu75m`, comme sur YouTube par exemple).

```php
#[AsCommand(
    name: 'delete:video',
    description: 'Delete a video (by using its unique identifier)',
)]
class MaCommande extends Command
{
    public function __construct(
       private VideoRepository $videoRepository,
       private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('videoCode', InputArgument::REQUIRED, "The unique identifier of the video.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $code = $input->getArgument('videoCode');
        $video = $this->videoRepository->findOneBy(["videoCode" => $code]);
        if($video === null) {
            $io->error("Video not found.");
            return Command::FAILURE;
        }
        $this->entityManager->remove($video);
        $this->entityManager->flush();
        $io->success("The video has been deleted !");
        return Command::SUCCESS;
    }
}
```

<div class="exercise">

1. Créez et testez la commande `GivePremiumCommand` nommée `give:premium` qui prend en paramètre le login d'un utilisateur et le rend membre premium.

2. Créez et testez la commande `RevokePremiumCommand` nommée `revoke:premium` qui prend en paramètre le login d'un utilisateur et le lui enlève le statut premium.

3. Créez et testez la commande `PromoteAdminCommand` nommée `promote:admin` qui prend en paramètre le login d'un utilisateur et lui donne le rôle `ROLE_ADMIN`. Vous aurez besoin d'ajouter la méthode suivante (pour ajouter un rôle) à la classe `Utilisateur` :

    ```php
    public function addRole($role) : void {
        if(!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
    }
    ```

4. Créez et testez la commande `RevokeAdminCommand` nommée `revoke:admin` qui prend en paramètre le login d'un utilisateur et lui enlève le rôle `ROLE_ADMIN`. Vous aurez besoin d'ajouter la méthode suivante (pour retirer un rôle) à la classe `Utilisateur` :

    ```php
    public function removeRole($role) : void {
        $index = array_search($role, $this->roles);
        //array_search renvoie soit l'index (la clé) soit false is rien n'est trouver 
        //Préciser le !== false est bien nécessaire, car si le role se trouve à l'index 0, utiliser un simple if($index) ne vérifie pas le type! Et donc, si l'index retournait est 0, la condition ne passerait pas...!
        if ($index !== false) {
            unset($this->roles[$index]);
        }
    }
    ```
</div>

## Conclusion

Avec ce TD, vous avez pu consolider votre maîtrise de Symfony et vous connaissez maintenant une grande partie du framework. À vous de vous lancer dans des projets plus complets afin de développer votre expérience avec cet outil.

Dans le prochain TD (le dernier consacré à Symfony), nous allons voir comment créer une **API REST** pour notre application "The Feed" avec un outil dédié : **API Platform.** Nous allons transposer tout ce que nous avons fait jusqu'ici sous la forme d'une API (donc, sans rendu HTML) qui pourra être utilisée par n'importe quel application : une application mobile, un autre service, ou bien une application **Vue.js**, ce qui sera l'objet des futurs TDs !
