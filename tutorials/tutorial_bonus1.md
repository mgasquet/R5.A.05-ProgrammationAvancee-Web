---
title: TP Bonus &ndash; Mise en place d'un système de paiement (Stripe)
subtitle: Stripe, Webhook
layout: tutorial
lang: fr
---

## Introduction

Ce TP est un TP bonus qui consiste en la mise en place d'un système de paiement (en mode "test" mais qui pourra réellement fonctionner juste en changeant une clé API). Pour cela, nous allons utiliser la plateforme de paiement `Stripe`.

Si vous souhaitez faire ce TP, il faut impérativement avoir au moins terminé la section **The Feed Premium** du [TD3]({{site.baseurl}}/tutorials/tutorial3).

Au même titre que PayPal, Stripe est un outil permettant aux entreprises de recevoir de l'argent en reliant leur compte à divers systèmes de paiement, par exemple, sur un site web. Ce genre d'outil dispose généralement d'une `API` qui permet son intégration à de nombreux systèmes de manière assez flexible. Et bien sûr, avant de recevoir de "vrai" paiement, ces plateformes mettent à disposition des développeurs des outils de tests pour simuler des paiements et vérifier que tout fonctionne.

Au niveau de PayPal, cet outil se nomme "PayPal Sandbox" et représente une copie complète de l'API et de l'environnement de PayPal, mais avec des comptes dédiés aux tests.

Quant à Stripe qui fonctionne avec un système de clés (publique/privées), il est simplement possible de passer en mode "test" et d'obtenir des clés dédiées aux tests pour simuler des paiements. Il est également possible de créer des faux clients, dans divers pays et la plateforme nous met à disposition des cartes bancaires factices qui fonctionnent uniquement en mode test.

Bien sûr, il existe bien d'autres plateformes, mais celles-ci sont les plus populaires. Hors des outils et des possibilités proposées par chaque plateforme, la principale différence va se situer au niveau du `fee` (frais de transaction) prélevé sur chaque transaction réelle (c'est ainsi que la plateforme fait du profit). On retrouve généralement un système de frais fixe (par exemple, 15 centimes) + un pourcentage sur le paiement reçu (par exemple, 3% du paiement reçu). Une entreprise doit donc prendre en compte le fait que si elle fait payer un produit ou un service, par exemple 15 euros, elle ne recevra pas ces 15 euros en totalité, car la plateforme prendra sa part.

Aujourd'hui, nous allons uniquement utiliser Stripe en mode "test", donc aucune transaction réelle sera réalisée (inutile de vous munir de votre CB :D). Si dans le futur vous avez pour projet de monter un business réel, gardez en tête que vous devez avoir une entreprise (microentreprise, association ou autre) déclarée. Toute plateforme similaire vous demandera des informations et des documents légaux avant que vous puissiez transférer l'argent vers votre compte bancaire (le compte bancaire de l'entreprise). Cependant, ce que vous allez apprendre aujourd'hui sera facilement reproductible dans un cas concret. Il suffira de changer vos clés API de test par des clés réelles.

Dans le cadre de notre application, nous allons travailler avec un système de **webhook** qui suit la logique suivante :

* L'utilisateur souhaite acheter un produit / service sur notre site.

* L'application (côté back-end) génère un tableau contenant toutes les informations de la commande (noms des produits, quantité, prix...) et utilise la librairie de Stripe en donnant ces informations afin de générer un lien (vers Stripe) qui permettra de finaliser le paiement.

* L'utilisateur est redirigé vers le lien généré à l'étape précédente qui le mène hors du site (sur Stripe) pour finaliser le paiement.

* Lorsque le formulaire de paiement est complété et validé, Stripe utilise un **webhook**. Cela signifie que Stripe envoie une requête à notre site web (pas depuis votre navigateur, mais depuis les serveurs de Stripe) informant que le paiement est valide (ou non, d'ailleurs).

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

    * Soit utiliser [cette clé de test](https://gitlabinfo.iutmontp.univ-montp2.fr/progweb-but3/documents-utiles/-/blob/main/Cl%C3%A9_API_secr%C3%A8te__test__partag%C3%A9e_-_Stripe.txt) que nous avons créé pour vous, notamment si vous ne souhaitez pas créer de compte et communiquer vos informations personnelles. Cette clé sera partagée par tous vos collègues, mais ce n'est pas trop contraignant. Néanmoins, vous n'aurez pas accès au dashboard de Stripe pour visualiser les transactions, mais rien de gênant, nous pourrons suivre cela sur un terminal, à la place.

2. **Si vous avez décidé de créer un compte Stripe**, rendez-vous dans **Développeurs** (en haut à droite). Ensuite, sur la nouvelle page, accédez à l'onglet **Clés API** et cliquez sur "Révéler la clé secrète". Cette clé débute par `sk_test_`. Notez-la quelque part.

3. Stripe met à disposition des librairies dans de nombreux langages afin d'utiliser plus facilement son API. Installez celle destinée à PHP avec composer :

    ```bash
    composer require stripe/stripe-php 
    ```
    Répondez oui (`y`) quand on vous pose la question quant à l'exécution de la recipe liée à **Stripe**. Cela va notamment ajouter un paramètre pour la clé secrète dans le fichier `.env`, un fichier de configuration, puis définir un service permettant d'accéder à un objet `StripeClient` que nous allons utiliser plus tard (notamment pour récupérer cette clé).

4. Affectez la clé secrète au niveau du paramètre `STRIPE_SECRET_KEY` dans `.env`.

</div>

Concernant notre clé secrète, il s'agit d'une donnée sensible qui se trouve dans notre fichier `.env` ou `.env.local` (si on ne veut pas que la clé soit prise en compte par git). Dans un contexte réel, on pourrait aussi avoir un fichier `.env.dev` et placer la clé de test là-dedans et notre clé réelle dans `.env`. Faites **très attention** à ce que vous envoyez sur Github/Gitlab si votre repository est public. Pour rappel `.env` est versionné, mais pas `.env.local`. Dans le cas où vous manipuliez une véritable clé d'API, si elle se trouve dans `.env` et que votre repository est public, toutes les personnes ayant accès au repository pourront la récupérer (comme vous identifiants BDD, etc...).

Comme nous l'avions fait pour le service `UtilisateurManager`, le paramètre peut facilement être injecté via le constructeur d'un service avec l'attribut `#[Autowire]` en précisant toutefois le paramètre `env` : `#[Autowire(env : '...')]`.

Par exemple :

```php
class MonService {

    public __construct(#[Autowire(env: 'MA_VAR')] private $maVar) {}

}
```

Cependant, dans notre cas, nous nous servirons d'un service prédéfini qui fait déjà ce travail et initialise le client Stripe avec la clé stockée dans `.env`.

## Création d'un paiement

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
$stripeSession = $this->stripeClient->checkout->sessions->create($paymentData);
$url = $stripeSession->url;
```

La partie `paymentData` est un tableau contenant toutes les informations sur la transaction que nous souhaitons réaliser :

* `mode` : définit le type de transaction : un paiement simple (`payment`), ou bien par exemple, la souscription à un abonnement. Dans notre cas, nous allons donc choisir l'option `payment` (sur notre site, on achète le statut premium une seule fois, et on le reste à vie).

* `payment_intent_data` : on indique qu'on veut capturer le paiement manuellement (c'est-à-dire avec un webhook, dans notre application) il faut aussi indiquer où sera envoyé le ticket de réception de la transaction (à quelle adresse email). En mode test, aucun n'email ne sera envoyé par Stripe.

* `customer_email` : Stripe a besoin de connaître l'adresse email de l'utilisateur (pour être affichée au vendeur, dans l'historique Stripe). Elle peut être différente de l'adresse où sera envoyé le ticket de caisse (mais c'est généralement la même). Nous ne sommes pas obligés de la remplir ici. Cela permet simplement de préremplir le champ correspondant sur le formulaire de Stripe.

* `success_url` : L'URL vers laquelle est redirigé l'utilisateur après que le paiement a été traité (par Stripe et notre application).

* `cancel_url` : L'URL vers laquelle est redirigé l'utilisateur s'il décide d'annuler la transaction (via un bouton).

* `metadata` : un tableau contenant des données supplémentaires sur la transaction, qui pourront notamment être récupérées par notre back-end lors du déclenchement du **webhook**. Par exemple, on peut placer ici l'identifiant de l'utilisateur réalisant la transaction, pour le récupérer ensuite (c'est même quasiment obligatoire, car on rappelle que c'est Stripe qui utilise notre **webhook**, et pas l'utilisateur ; Il faut donc un moyen d'identifier qui a payé).

* `line_items` : un tableau contenant plusieurs tableaux décrivant les produits de la transaction. Pour chaque produit, on remplit donc un tableau avec les informations suivantes :

    * `price_data` : Un tableau précisant la devise (dans notre cas, `eur` pour "euros"), des données supplémentaires, comme le nom du produit (dans un sous-tableau) et enfin, le prix unitaire (donc le prix d'un produit). Attention, le prix s'exprime en centimes. Donc, si je veux vendre un produit 25 € je mets 2500.

    * `quantity` : La quantité vendue pour ce produit.

On initialise ensuite une session (grâce au service `StripeClient`, qui sera injecté dans notre futur service) puis on génère l'URL vers laquelle rediriger l'utilisateur.

Nous allons construire un service dédié à la gestion des paiements. On aura une première méthode permettant de générer un lien de paiement pour un utilisateur donné. En plus des classes de Stripe (que vous devez simplement importer), vous aurez besoin du service `UrlGeneratorInterface` permettant de générer des URLs (relatives ou absolues) à partir d'un nom de route. Son fonctionnement est similaire à la fonction `path` que nous utilisons dans nos templates `Twig` :

```php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

//Pour générer une URL absolue
$url = $generator->generate(nomRoute, ["param" => ..., ], UrlGeneratorInterface::ABSOLUTE_URL);
```

Bien sûr, si la route n'est pas paramétrable, il suffit de préciser un tableau vide comme second argument. Le dernier paramètre `UrlGeneratorInterface::ABSOLUTE_URL` permet de générer une URL absolue. Vous en aurez besoin pour générer les liens pour les paramètres `success_url` et `cancel_url`.

Il vous faudra aussi injecter le service `StripeClient` qui permet de créer une session et de générer l'URL de paiement.

Il faudra bien penser à inclure son import : 

```php
use Stripe\StripeClient;
```

<div class="exercise">

1. Créez un service `PaymentHandler` contenant la méthode suivante :

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

2. Définissez une interface `PaymentHandlerInterface` pour votre service.

3. Dans `PremiumController`, ajoutez une route `premiumCheckout` ayant pour chemin `/premium/checkout` et accessible en `GET` qui devra simplement **générer le lien de paiement** en utilisant notre nouveau service et rediriger l'utilisateur vers ce lien. La route ne devra être accessible qu'aux membres connectés qui ne sont pas encore membres premium (comme pour la route `premiumInfos`).


    Pour rappel, dans un contrôleur, on peut directement accéder à l'utilisateur (`$this->getUser()`).

    Pour la redirection, il faudra utiliser la méthode `redirect` (du contrôleur) et non pas `redirectToRoute`. La méthode `redirect` permet de rediriger vers une URL (absolue) :

    ```php
     #[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
    public function methodeExemple(): Response
    {
        return $this->redirect("https://coucou.com");
    }
    ```

4. Modifiez le template `premium-infos.html.twig` en modifiant l'élément `<a></a>` attaché au bouton afin de placer un lien vers notre nouvelle route.

5. Rendez-vous sur la page d'informations du statut premium et cliquez sur le bouton. Vous devriez alors être redirigé sur Stripe. Vérifiez que le récapitulatif présenté est correct (email, prix, quantité, nom...). Vérifiez également que le bouton d'annulation vous renvoie bien sur la bonne page (informations du premium).

</div>

## Webhook

Il ne nous reste plus qu'à finaliser le paiement et la demande en créant un `webhook` sur lequel Stripe pourra envoyer des informations. Pour cela, il va falloir donner à Stripe une `route` vers laquelle envoyer la requête de confirmation quand la plateforme reçoit un paiement. Aussi, nous allons devoir sécuriser cette route afin que seul Stripe puisse y envoyer des requêtes !

Mais, dans le cas où vous développez votre application sur un serveur local (localhost), comment Stripe peut-il envoyer des requêtes vers votre site ? Pour régler ce problème, Stripe met à disposition un outil très complet à utiliser sur votre machine (en ligne de commande) pour simuler différents événements et aussi spécifier vers quelle adresse envoyer les requêtes post-paiement.

Cet outil est connecté à votre compte Stripe à distance et, dès qu'un paiement est reçu, l'événement est transmis à votre machine, au niveau de l'outil qui s'occupera alors de transmettre la requête vers l'adresse précisée. Comme votre outil tourne en local, sur votre machine, il est donc possible d'accéder à des adresses locales, comme `localhost` !

Dans un premier temps, nous allons voir comment installer et configurer cet outil. Nous n'allons pas l'installer dans notre conteneur, mais plutôt directement sur votre machine. Exceptionnellement, les commandes liées à `stripe` devront donc être exécutées dans un terminal sur votre machine, et pas dans le conteneur.

<div class="exercise">

1. Dans votre conteneur docker, exécutez cette commande n'importe où pour installer le client Stripe :

    ```bash
    npm install --global @stripe/cli
    ```

2. Ensuite, exécutez la commande suivante :

    ```bash
    stripe login --interactive
    ```

3. À l'étape d'après, collez votre clé secrète de test (clic-droit pour coller dans le terminal). Par sécurité, la clé n'est pas affichée (comme quand vous tapez un mot de passe dans un terminal, sous Linux), validez simplement après avoir collé la clé. On vous demande ensuite un nom pour votre machine. Un nom est proposé par défaut, vous pouvez valider ou le changer.

4. Exécutez maintenant la commande suivante :

    ```bash
    stripe listen
    ```

    Une **signature** secrète de webhook vous est donné. Conservez-la quelque-part.

5. Pour vérifier que tout est bien connecté, allez sur votre site puis accédez à la page du paiement sur Stripe. Validez le paiement en entrant les informations [d'une des cartes bancaires de test](https://stripe.com/docs/testing?locale=fr-FR#cards) mises à disposition par Stripe. Une fois validé, regardez votre terminal, vous devriez capter l'événement ! (si vous utilisez le compte "commun", vous capterez aussi les paiements des autres étudiants, mais ça ne sera pas trop gênant pour la suite.)

6. Quittez le programme (pour le moment).

</div>

Le client est prêt à être utilisé et est bien relié à votre compte Stripe (ou au compte commun si vous n'avez pas créé de compte). La **signature** secrète qui vous a été donnée est très importante. Elle permettra de vérifier dans le code de votre route (le webhook) que la requête a bien été émise par Stripe.

Nous allons diviser la gestion de la requête en trois parties :

* Vérification de l'identité de la requête (avec la signature) et extraction des données (dans une classe dédiée).

* Vérification du type d'événement récupéré, puis lancement du traitement (aussi dans une classe dédiée).

* Traitement de la demande, vérification d'éventuels problèmes, capture du paiement et changement de rôle de l'utilisateur (dans un service).

Pour les deux premières classes, Symfony permet de les initialiser très simplement avec une commande et de configurer automatiquement la route en relation avec notre `webhook` :

```bash
php bin/console make:webhook
```

On nous demande alors plusieurs informations :
* Le nom du webhook (qui permettra de définir le chemin de la route). Par exemple, si j'appelle mon webhook "exemple", la route associée sera `/webhook/exemple`.
* Les **matchers** que l'on souhaite utiliser. Cela permet d'informer Symfony de vérifier qu'une requête émise par un service sur ce webhook peut être traitée. On va par exemple regarder la forme de la requête (par exemple format JSON, formulaire), sa méthode, etc. On peut n'en spécifier aucun, un, ou plusieurs. Cela dépend de la documentation du service qui utilisera notre webhook.

Une fois que la commande est exécutée, deux fichiers sont créés :

```php
//src/WebHook/ExempleRequestParser.php
namespace App\Webhook;

final class ExempleRequestParser extends AbstractRequestParser
{
    //Permet à Symfony d'identifier si la requête reçue peut être traitée par ce parser.
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        //Les matchers
        return new ChainRequestMatcher([
            ...
        ]);
    }

    // Permet d'extraire les données de la requête et d'émettre un événement contenant les données intéressantes (nom de l'événement, identifiant, contenu de la requête...). Cet événement sera traité par un "consumer" (autre classe).
    //L'objet $request nous permet d'extraire les données.
    //L'objet $secret est un paramètre qui permet de vérifier l'identité de la requête et de la rejeter si elle est émise par une source non autorisée. L'attribut #[\SensitiveParameter] permet d'indiquer la nature critique de ce paramètre qui ne doit jamais être dévoilé/affiché, etc. Ce paramètre sera injecté automatiquement.
    // On peut lever une exception RejectWebhookException s'il y a un problème (par exemple, si le webhook est émis par un service/utilisateur non autorisé)
    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        $payload = ...
        ...
        return new RemoteEvent($nom, $id, $payload);
    }
}
```

```php
//src/RemoteEvent/ExempleWebhookConsumer.php
namespace App\RemoteEvent;

//On indique le nom du webhook pour lequel cette classe traite les événements émis.
#[AsRemoteEventConsumer('exemple')]
final class ExempleWebhookConsumer implements ConsumerInterface
{
    public function __construct(/* Injection de dépendances... */)
    {}

    public function consume(RemoteEvent $event): void
    {
        //Traitement du webhook...
        //$event contient les données de l'événement émis par la classe précédente (ExempleRequestParser). On peut se servir du payload qui contient les données émises par le service qui a fait appel au webhook.
        //Ici, on peut notamment faire appel à des services injectés.
    }
}
```

Enfin, le fichier `src/config/packages/webhook.yaml` est automatiquement modifié pour enregistrer notre webhook :

```yaml
#src/config/packages/webhook.yaml
framework:
  webhook:
    routing:
      exemple:
        service: App\Webhook\ExempleRequestParser
        #On précise à quoi correspond le secret utilisé dans le parser pour vérifier l'identité du service à l'origine de la requête.
        secret: '%env(SECRET_EXEMPLE)%'
```

Ce qui configure la route `/webhook/exemple` en interne.

Nous allons donc mettre en place un webhook pour traiter les notifications envoyées par Stripe après un paiement, avec la structure de code suivante :

```php
namespace App\Webhook;

use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class StripeRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new IsJsonRequestMatcher(),
            new MethodRequestMatcher('POST'),
        ]);
    }

    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        //On récupère le contenu de la requête (payload JSON)
        $content = $request->getContent();

        //On extrait la signature de la requête, qui permet d'identifier la source à l'origine de la requête.
        $sig_header = $request->server->get('HTTP_STRIPE_SIGNATURE');
        try {
            /*
            On construit l'événement.
            On utilise $secret qui contient la signature secrète récupérée plus tôt (dans le terminal)
            Si la signature n'est pas bonne (vérifié avec la signature de la requête et celle secrète), une exception est déclenchée.
            */
            $event = Webhook::constructEvent($content, $sig_header, $secret);

            //On retourne un événement avec différentes informations utiles, notamment le type d'événement (dans notre cas, nous ne traiterons qu'un seul événement, lorsque le paiement est complété, mais Stripe peut en envoyer d'autres).
            //Les données de la requête (le JSON) sont converties en tableau associatif.
            return new RemoteEvent(
                $event->type,
                $event->id,
                $event->toArray()
            );
        } catch (SignatureVerificationException $e) {
            //Si la signature est invalide, c'est que la requête est émise par une source inconnue ou non autorisée, on rejette donc le webhook.
            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Invalid signature.');
        }
    }
}
```

```php
namespace App\RemoteEvent;

use App\Service\PaymentHandlerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('stripe')]
final class StripeWebhookConsumer implements ConsumerInterface
{
    public function __construct(/* Injection d'un service...*/)
    {}

    //On récupère l'objet émis à l'étape précédente...
    public function consume(RemoteEvent $event): void
    {
        /*
        On vérifie le type d'événement.
        Pour l'instant, nous ne traitons que l'événement checkout.session.completed qui est déclenché quand l'utilisateur valide le formulaire et le paiement est prêt à être capturé
        Si l'application vient à évoluer, on pourrait traiter d'autres événements
        */
        if($event->getName() == 'checkout.session.completed') {
            /*
            $session est un tableau qui contient les données du paiement.
            On pourra notamment accéder aux meta-données que nous avions initialement placées lors de la création du paiement.
            */
            $session = $event->getPayload()["data"]["object"];
            //On imagine que $service est un service contenant une méthode permettant de traiter la suite de la requête.
            $service->traitement($session);
        }
    }
}
```

Afin d'injecter la signature secrète dans `$secret`, on la stocke dans une variable dans `.env` (par exemple, `STRIPE_SECRET_SIGNATURE`) et on l'injecte ainsi :

```yaml
#src/config/packages/webhook.yaml
framework:
  webhook:
    routing:
      stripe:
        service: App\Webhook\StripeRequestParser
        secret: '%env(STRIPE_SECRET_SIGNATURE)%'

```

Pour le reste du traitement (qui sera délégué à un service), plusieurs objets et méthodes nous intéressent :

```php
//La variable $this->stripeClient correspond au service StripeClient que nous avons déjà utilisé
//La variable $session est le tableau contenant les données de la session.

//L'objet "paymentIntent" permet de capturer (confirmer) ou d'annuler le paiement.
$paymentIntent = $session["payment_intent"];

//On peut récupérer les meta-données depuis les données de la session transmises par Stripe.
// Dans notre cas, cela permettra de récupérer l'utilisateur visé par la requête (grâce à son identifiant que nous avons placé dans les métadonnées).
$metadata = $session["metadata"];

//Avant d'extraire une donnée, on peut bien sûr vérifier sa présence...
if(!isset($metadata["dataExemple"])) {
    return;
}

//On récupère les données ainsi
$dataExemple = $metadata["dataExemple"];

//Pour "capturer" et valider le paiement : l'argent arrive définitivement sur notre compte Stripe
$paymentCapture = $this->stripeClient->paymentIntents->capture($paymentIntent, []);
//On peut ensuite vérifier si le paiement a bien été capturé (si oui, on dispose de l'argent sur le compte Stripe, à ce stade).
if($paymentCapture == null || $paymentCapture["status"] != "succeeded") {
    return;
}

//Après avoir capturé le paiement avec succès, on peut réaliser nos actions complémentaires (dans notre cas, mettre l'attribut "premium" de l'utilisateur cible à true, puis sauvegarder).

```

Pour récupérer l'utilisateur ciblé à partir de son identifiant, vous pourrez bien sûr injecter et utiliser le **repository** de l'entité.

Pour mettre à jour les données de l'utilisateur en base de données, il faudra utiliser le service `EntityManagerInterface`, comme quand vous créez une entité. Après avoir modifié les données de l'utilisateur, il suffit d'appeler `flush`.

Vous aurez sans doute remarqué que quand il y a une erreur, nous ne levons pas d'exceptions et nous effectuons un simple `return` pour arrêter la méthode. C'est voulu, car, si on accepte de traiter la requête de webhook envoyée par Stripe (donc, après avoir passé le **parser**), nous devons renvoyer un code de succès (2xx) pour informer que nous avons bien reçu et identifié le webhook (peu importe qu'on arrive ou non au bout du traitement ensuite, dans le consumer). La levée d'une exception provoquerait une réponse d'erreur (typiquement 500) et Stripe pourrait penser que nous n'avons pas bien reçu sa requête à cause d'un problème technique, et ainsi la renvoyer plus tard. Bref, le seul endroit où l'on peut refuser la requête (si elle n'est pas bien formée, ou s'il n'y a pas les autorisations, etc). Cependant, s'il y a une erreur rencontrée lors du traitement, nous aurons bien sûr un moyen d'annuler le paiement.

 <!-- Il n'y a pas (encore) de méthode dans notre entité `Utilisateur` permettant d'ajouter un rôle. Nous l'ajouterons dans le prochain exercice. -->

<div class="exercise">

1. Dans votre service `PaymentHandler`, ajoutez et complétez la méthode suivante :

    ```php
    use Exception;

    /**
    * @throws Exception
    */
    public function handlePaymentPremium(array $session) : void {
        
    }
    ```

    Pour l'instant, cette méthode doit simplement : 
    
    * Récupérer l'identifiant de l'utilisateur dans les métadonnées de l'objet `$session` (le nom correspond à celui que vous aviez donné dans `getPremiumCheckoutUrlFor`). S'il n'existe pas dans les métadonnées, on arrêtera la fonction avec un `return`. 
    
    * Récupérer l'objet utilisateur lié à cet id.

    * Capturer le paiement (et vérifier qu'il l'est bien validé) puis changer la propriété "premium" de l'utilisateur (en la passant à `true`). Si le paiement n'est pas validé, on arrête la fonction avec un `return`.
    
    * Sauvegarder ces modifications en base de données.

    N'oubliez pas d'ajouter les injections de dépendances nécessaires dans votre service. Vous aurez notamment besoin de quoi aller récupérer des utilisateurs dans la base de données et de quoi les sauvegarder... Et pensez également à mettre à jour son **interface** avec la signature de la méthode `handlePaymentPremium`.

2. **Si vous utilisez le compte commun** et pas votre propre compte Stripe, nous avons un "léger" soucis à régler. En effet, comme expliqué plus tôt, vous allez recevoir les événements déclenchés par tous les autres étudiants utilisant le compte commun. Il faut donc trouver un moyen d'identifier vos requêtes de manière unique et d'ignorer celles des autres. Nous vous proposons donc les ajouts suivants :

    * Dans la méthode `getPremiumCheckoutUrlFor`, ajoutez dans le tableau `metadata` un attribut `studentToken` avec un pseudonyme ou un code que vous choisissez (assurez-vous de ne pas avoir le même qu'un autre étudiant...)

    * Dans la méthode `handlePaymentPremium`, vérifiez que cet attribut est bien là et possède la valeur que vous aviez configurée. Sinon, on lève une exception :

    ```php
    $metadata = $session["metadata"];
    if(!isset($metadata["studentToken"]) || $metadata["studentToken"] !== "votre_code_perso") {
        return;
    }
    ```

    Ce bricolage permet de rejeter les requêtes qui ne vous sont pas destinées. Bien sûr, dans un cas réel, il n'y a pas de tel système à mettre en place, vous aurez votre propre compte et ne capterez pas d'événement indésirable. Il s'agit juste d'une astuce pour que le TD se passe bien si vous utilisez le compte partagé !

3. Enregistrez la signature secrète que vous aviez récupérée dans le terminal (lors de l'exécution du client Stripe) comme une **variable** de l'application définie dans `.env` ou `.env.local`.

4. Créez un nouveau webhook grâce à la commande `make:webhook` :

    * Son nom est `stripe`.
    * Choisissez les request matchers suivants : `IsJsonRequestMatcher` et `MethodRequestMatcher`.

5. Modifiez le fichier `src/config/packages/webhook.yaml` pour injecter la signature secrète que vous avez ajoutée dans `.env` en tant que `$secret` du service `StripeRequestParser`.

6. Modifiez le code des classes `StripeRequestParser` et `StripeWebhookConsumer` afin de traiter les requêtes envoyées par Stripe, comme montré un peu plus tôt. Concernant `StripeWebhookConsumer`, vous injecterez le service `PaymentHandlerInterface` afin de vous servir la méthode `handlePaymentPremium` sur le tableau contenant les données de session.

</div>

Tout est prêt pour finaliser notre système de paiement ! Avant de traiter les cas d'erreurs, nous allons vérifier que dans un scénario normal, le système fonctionne comme attendu.

Pour que Stripe utilise notre **webhook**, nous devons lui indiquer l'URL à laquelle il se trouve. Pour le mode "test", cela se fait donc avec le client que vous avez utilisé plus tôt, en ligne de commandes. Il suffit d'exécuter la commande suivante :

```bash
stripe listen --skip-verify --forward-to https://exemple.com
```

Par défaut, nous écoutons **tous les événements** en lien avec notre compte Stripe. Pour filtrer et n'utiliser que celui qui nous intéresse pour ce webhook, on utilise l'option `--events` :

```bash
stripe listen --skip-verify --events=checkout.session.completed --forward-to https://exemple.com
```

Ici, dès que l'événement correspondant à la validation du formulaire de paiement est émis, Stripe enverra une requête à l'adresse précisée (à travers notre machine, car le client est connecté à notre compte).

Ainsi, il est possible d'avoir plusieurs **webhooks** différents, pour plusieurs événements.

<div class="exercise">

1. Utilisez la commande `listen` du client Stripe en précisant l'URL pointant vers la route de votre site correspondant au **webhook** créé lors de l'exercice précédent (normalement [https://localhost/the_feed/public/webhook/stripe](https://localhost/the_feed/public/webhook/stripe) si vous avez bien nommé votre webhook **stripe**).

2. Testez d'acheter du mode premium (comme tout à l'heure, en utilisant une [carte bancaire de test](https://stripe.com/docs/testing?locale=fr-FR#cards)).

3. Jetez un coup d'œil au terminal, si le code `200` apparaît quelque part, cela doit être bon. Attention toutefois, si vous utilisez le compte partagé, peut-être que vous verrez le résultat de la requête d'un autre étudiant qui a effectué un paiement en même temps que vous.

   **Aide pour déboguer :** Si le code `500` (ou autre) apparaît et que vous n'arrivez pas à savoir pourquoi, ouvrez le fichier `var/log/dev.log` et cherchez le détail des exceptions levées par le programme, en fin de fichier.

4. Vérifiez sur le site que l'utilisateur est bien devenu membre premium.

</div>

Si tout a bien marché, félicitations, votre système de paiement est fonctionnel ! Pour une entreprise réelle, l'adresse du **webhook** concret (utilisé avec la clé réelle pour recevoir des paiements et pas celle de test) ne se configure pas via le terminal, mais dans un onglet dédié du dashboard de Stripe. Dans ce cas, l'adresse ciblée doit être publiquement accessible (pas de localhost ou autre). Le **webhook** cible un site déjà hébergé/en production. Comme dans le terminal, il est possible de filtrer les événements qui déclenchent l'appel au webhook. Il est donc aussi possible de créer plusieurs webhooks, pour des événements différents.

La signature secrète de requête que vous possédez ne sera pas aussi la même entre le mode test et le mode production. Une fois l'entreprise enregistrée, vous avez accès à toutes les données nécessaires pour réellement recevoir des paiements (clé privée, signature...)

Idéalement, lors du développement d'un système de paiement, on teste d'abord que tout fonctionne en local avec les clés de tests et avec le client Stripe, en réalisant des paiements factices (comme ce qu'on fait ici) puis, quand le site est publié, on utilise un fichier de configuration contenant la clé et la signature "réelle" puis on renseigne la véritable adresse du webhook au niveau du dashboard. 

Bref, tout cela ne vous concerne pas vraiment pour le moment, car nous nous limitons au mode "test" dans le cadre de ce TD, bien entendu.

## Scénarios d'erreur

Nous allons maintenant gérer quelques scénarios d'erreurs, où il faut donc annuler le paiement :

* Les méta-données ne contiennent pas l'identifiant de l'utilisateur.

* L'utilisateur ciblé n'existe pas. Par exemple, l'utilisateur a supprimé son compte (une fonctionnalité que nous n'avons pas encore développée, mais qui pourrait être là dans le futur).

* L'utilisateur est déjà membre premium : par exemple, vous avez ouvert deux formulaires de paiement et vous les remplissez à la suite. Il ne faut pas débiter le client deux fois !

* Le paiement n'a pas pu être capturé, pour diverses raisons.

Dans chaque cas, il faut **annuler le PaymentIntent** et expliquer pourquoi on l'annule.

Pour **annuler* un `PaymentIntent`, on peut utiliser la méthode `cancel` (suivi d'un `return` pour arrêter le traitement de la méthode) :

```php
$this->stripeClient->paymentIntents->cancel($paymentIntent);
return;
```

<div class="exercise">

1. Modifiez la méthode `handlePaymentPremium` pour gérer ces quatre scénarios d'erreur et annuler le paiement.

2. Vérifiez les trois premiers scénarios d'erreur (le quatrième est plus difficile à simuler) :

    * Pour le premier, retirez temporairement `idUtilisateur` des méta-données lors de la création du lien de paiement (dans `getPremiumCheckoutUrlFor`).

    * Pour le second, créez un compte, connectez-vous, ouvrez le formulaire de paiement, supprimez l'utilisateur dans la base de données, simulez le paiement. Le paiement ne devrait pas avoir lieu.

    * Pour le troisième, connectez-vous à un compte non-premium, ouvrez deux fois le formulaire de paiement et simulez deux paiements. Vérifiez que le deuxième paiement n'aboutit pas (code 400 sur le client Stripe).

</div>

Idéalement, il faudrait entourer l'appel à `cancel` d'un bloc `try/catch`, car une erreur pourrait éventuellement survenir si on tente d'annuler un paiement déjà annulé (en cas de doublons, par exemple).

## Confirmation du paiement

Actuellement, quand le paiement est validé, l'utilisateur est redirigé sur la page d'accueil sans aucun message pour l'informer du déroulement de l'opération. C'est un peu rude ! Heureusement, Stripe a prévu un système pour qu'on puisse récupérer les informations sur le déroulement de la transaction (si notre webhook l'a bien validé ou non, par exemple).

L'idée est la suivante :

* Ajouter **l'identifiant de la session Stripe** dans le **query string** quand on génère le lien de paiement.

* Quand l'utilisateur est redirigé, on extrait l'identifiant depuis l'URL, on récupère les données de la session grâce à son identifiant, puis celles du paiement afin de vérifier son état. Si le paiement a bien été confirmé, on affiche un message de confirmation, sinon, on affiche un message d'erreur.

Pour rappel, le **query string** est la partie de l'URL contenant des paramètres supplémentaires : `https://exemple.com/route?param1=exemple&param2=exemple`

Cela diffère de nos routes « paramétrées », où les paramètres font partie de la route elle-même.

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
//On récupère les données de la session à partir de l'identifiant de la session
$session = $this->stripeClient->checkout->sessions->retrieve($sessionId);

//On extraie l'identifiant du paiement depuis la session
$paymentIntentId = $session->payment_intent;

//On récupère les données du paiement 
$paymentIntent = $this->stripeClient->paymentIntents->retrieve($paymentIntentId);

//L'état "succeeded" signifie que le paiement a bien été capturé (le client a été débité)
$status = $paymentIntent->status;
```

Maintenant, à vous de jouer !

<div class="exercise">

1. Dans votre service `PaymentHandler`, ajoutez et complétez la méthode suivante :

    ```php
    //Renvoie true si le paiement lié à la session dont l'identifiant est passé en paramètre a abouti (a été capturé...) et renvoie false sinon.
    public function checkPaymentStatus(string $sessionId) : bool {

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

Voilà, notre système de membre premium est complet ! Attention toutefois, dans un contexte réel, il y aurait un autre cas d'erreur à gérer (peu probable, mais qui peut arriver) : comme dans le dernier scénario, l'utilisateur ouvre deux fois le formulaire, mais cette fois, il les valide quasi simultanément. Il est possible que Stripe envoie donc deux requêtes pour déclencher votre **webhook** quasi simultanément. Comme les deux requêtes s'exécutent alors en parallèle, sur la seconde, la vérification que l'utilisateur n'est pas déjà membre premium pourrait passer, car la première requête n'a pas fini de s'exécuter ! Dans ce cas-là, comme expliqué plus tôt, il faut utiliser un système de "verrou" pour bloquer le code de la méthode `handlePaymentPremium`. Diverses bibliothèques vous permettent de faire cela plus ou moins facilement. En tant que développeur, vous devez réfléchir à tous les problèmes qui peuvent découler de ce genre de système !

Stripe propose aussi un système d'identification qui permet de ne pas exécuter deux fois des paiements considérés identiques (même service). Il faut alors fournir un identifiant spécial qui permet à Stripe si deux paiements sont équivalents. On utilise pour cela l'attribut `idempotency_key` lors de la création du paiement, dans un tableau `$options` passé comme second paramètre de la fonction `Session::create`. Dans notre cas, on pourrait générer un identifiant unique qu'on stockerait dans les informations de l'utilisateur dans la base quand l'utilisateur clique sur le bouton de "Acheter" (sur notre site). Il s'agirait de la clé d'idempotence. Si le client re-essaye d'acheter à nouveau (avant d'avoir validé le paiement), la clé n'est pas écrasée. Quand le paiement est terminé (ou mieux, si l'utilisateur annule son mode premium, si on ajoute cette fonctionnalité) cette clé est supprimée de la base. Cela nous éviterait aussi de gérer certains cas d'erreurs que nous avons gérés plus tôt (paiement si l'utilisateur déjà premium, par exemple).

De manière générale, il faudrait plutôt gérer les paiements dans des objets dédiés à part (par exemple, un objet commande, etc...). Il serait créé lors de l'intention de paiement et supprimé une fois le paiement traité. La clé d'idempotence pourrait être l'identifiant (ou une valeur unique) lié à cet objet.

Bref, tout ceci n'était qu'une introduction, et nous n'avons malheureusement pas plus de temps pour aller dans le détail. Cependant, dans la mise en place réelle d'un système de paiement, il faut impérativement prendre en compte et utiliser ces systèmes de sécurisation !