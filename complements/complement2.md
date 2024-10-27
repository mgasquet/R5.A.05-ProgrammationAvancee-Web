---
title: API PLatform - Gestion des relations entre les entités 1/2
subtitle: Relations un-plusieurs et un-un
layout: tutorial
lang: fr
---

## Introduction

Dans le [TD4]({{site.baseurl}}/tutorials/tutorial4), nous avons utilisé **API Platform** afin de modéliser l'API de **The Feed**.

Cependant, comme le modèle de données associé à ce réseau social n'est pas très riche, il n'y a pas beaucoup 
de relations entre les entités.

La seule relation que vous avez du gérer se trouve au niveau d'une publication, pour affecter son auteur 
(de plus, nous avons fait en sorte de ne même pas avoir à le préciser dans le **payload** d'une `Publication` 
en utilisant l'identifiant de l'utilisateur connecté).

Si les relations de type **un-plusieurs** (`OneToMany` et `ManyToOne`) et **un-un** (`OneToOne`) sont assez triviales à gérer, 
cela n'est pas forcément le cas pour :

* Les relations **plusieurs-plusieurs** (`ManyToMany`) qui traduisent une association binaire simple.
* Les relations qui traduisent une association **n-aire** au-delà de deux entités (ternaire, etc).
* Les relations qui traduisent une association **n-aire porteuse** (qui possède des données en dehors des 
clés étrangères qui composent l'association).

Dans ce complément de TD, nous allons explorer différents cas de figures à partir d'un exemple simple afin d'étudier 
les différentes solutions à notre disposition pour gérer ces différents cas.

La première partie de ce complément de TD sera consacrée aux relations **un-plusieurs** et **un-un**.

## Les relations un-plusieurs (OneToMany et ManyToOne)

Une relation **un-plusieurs** entre une entité `A` et une entité `B` signifie que `A` peut être lié à plusieurs instances 
de `B` alors que `B` n'est lié (au maximum) qu'à une seule instance de `A`.

Concrètement, dans la base de données cela se traduit par une clé étrangère (vers la clé primaire de `A`) au niveau 
de l'entité `B` (qui peut être nulle ou non).

Au niveau du **code**, on pourra avoir un attribut de type `A` dans `B` et éventuellement une collection d'entités `B` dans `A`.

Pour commencer, nous allons définir un modèle E/A de base (phase d'analyse) et l'enrichir au fur et à mesure des exemples :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/ModeleEA1.PNG)
</div>

Ce qui donnerait le schéma relationnel suivant :

* **Joueur**(<u>id</u>, nom, prenom, #idVille)
* **Ville**(<u>id</u>, nom, codePostal)
* **Resultat**(<u>id</u>, nombrePoints, #idJoueur)

Enfin, en conception, après certains choix, on obtiendrait alors le diagramme de classes suivant :

<div>
 ![diagramme de classes 1]({{site.baseurl}}/assets/complement2/DiagrammeClasses1.PNG)
</div>

On a donc deux relations **un-plusieurs** :

* Un joueur possède une référence vers la ville dans laquelle il habite (et on peut éventuellement ne pas la connaître).
* Un résultat possède une référence vers le joueur ayant réalisé ce résultat.
* Un joueur possède une collection des résultats qu'il a réalisés.

Avec la commande `make:entity` de Symfony, tout cela ne prend que quelques petites minutes à configurer : les classes du programme 
ainsi que la base de données correspondante sont rapidement prêts à l'emploi.

Pour les références, on pourra utiliser `ManyToOne` dans la classe `Resultat` et `Joueur`. Et on autorisa `Joueur` à avoir 
une collection d'objets `Resultat`.

À l'issue, on obtient alors les classes suivantes :

```php
#[ORM\Entity(repositoryClass: VilleRepository::class)]
#[ApiResource]
class Ville
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $codePostal = null;

    //Methodes...
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource]
class Joueur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\ManyToOne]
    private ?Ville $ville = null;

    /**
     * @var Collection<int, Resultat>
     */
    #[ORM\OneToMany(targetEntity: Resultat::class, mappedBy: 'joueur', orphanRemoval: true)]
    private Collection $resultats;

    //Methodes...
}

#[ORM\Entity(repositoryClass: ResultatRepository::class)]
#[ApiResource]
class Resultat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $nombrePoints = null;

    #[ORM\ManyToOne(inversedBy: 'resultats')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Joueur $joueur = null;

    //Methodes...
}
```

En conception, on évite généralement d'utiliser des **bidirectionnelles** quand cela est possible, car elles peuvent être compliquées
à maintenir. Cependant, **Doctrine** gère bien cela de façon automatique sans intervention du développeur. On peut donc les utiliser
sans trop de problèmes, même s'il est plus judicieux de l'éviter quand cela est possible. Nous n'allons pas le faire ici : un joueur connaît sa ville, mais la ville ne connaît pas (directement) la liste de ses
habitants. On pourra toutefois rajouter une route afin d'obtenir cette information, plus tard.

### Ressource à part entière

La première relation à laquelle nous allons nous intéresser est `Joueur-Ville`.

Cette relation est très facile à maintenir : on précise l'**IRI** de la ville en question dans le **payload** du Joueur :

```
POST https://localhost/api/villes
{
    "nom": "Nîmes",
    "codePostal": 30000
}
```

Renvoi :
```
{
    "@id": "/api/villes/1"
    "id": 1,
    "nom": "Nîmes",
    "codePostal": 30000
}
```

```
POST https://localhost/api/villes
{
    "nom": "Montpellier",
    "codePostal": 34000
}
```

Renvoi :
```
{
    "@id": "/api/villes/2"
    "id": 2,
    "nom": "Montpellier",
    "codePostal": 34000
}
```

Création d'un joueur qui habite à Nîmes :
```
POST https://localhost/api/joueurs
{
    "nom": "Smith",
    "prenom": "John",
    "ville": "/api/villes/1"
}
```

Renvoi :
```
{
    "@id": "/api/joueurs/1"
    "id": 1,
    "nom": "Smith",
    "prenom": "John",
    "ville": "/api/villes/1",
    "resultats" : []
}
```

On ne veut plus connaître la ville de résidence de ce joueur :
```
PATCH https://localhost/api/joueurs/1
{
    "ville": null
}
```

Changement de ville :
```
PATCH https://localhost/api/joueurs/1
{
    "ville": "/api/villes/2"
}
```

On peut ensuite utiliser la **normalisation** afin de montrer les détails de la ville lorsqu'on affiche un joueur :

```php
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read', 'ville:read']]
)]
class Joueur
{
    ...

    #[ORM\ManyToOne]
    #[Groups(['joueur:read'])]
    private ?Ville $ville = null;

    ...
}

#[ApiResource(
    normalizationContext: ["groups" => ['ville:read']]
)]
class Ville
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ville:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ville:read'])]
    private ?string $nom = null;

    #[ORM\Column]
    #[Groups(['ville:read'])]
    private ?int $codePostal = null;

    //Méthodes
}
```

```
GET https://localhost/api/joueurs/1

{
    "@id": "/api/joueurs/1"
    "id": 1,
    "nom": "Smith",
    "prenom": "John",
    "ville": {
        "@id": "/api/villes/2"
        "id": 2,
        "nom": "Montpellier",
        "codePostal": 34000
    },
    "resultats" : []
}
```

On peut aussi créer une route spéciale pour directement obtenir les détails de la ville d'un joueur (à la place ou en complément) :

```php
#[ApiResource]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/ville',
    operations: [new Get()],
    uriVariables: [
        'idJoueur' => new Link(
            fromProperty: 'ville',
            fromClass: Joueur::class
        )
    ],
    normalizationContext: ["groups" => ["ville:read"]]
)]
class Ville
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ville:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ville:read'])]
    private ?string $nom = null;

    #[ORM\Column]
    #[Groups(['ville:read'])]
    private ?int $codePostal = null;
}
```

Notez qu'ici, nous avons utilisé le paramètre `fromProperty` et pas `toProperty` dans `uriVariables` : nous souhaitons récupérer 
les détails d'une **ville** qui se trouve dans la classe `Joueur`.

Si nous avions eu la liste des joueurs dans `Ville` (avec une propriété `habitants`) on aurait pu écrire :

```php
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/ville',
    operations: [new Get()],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'habitants',
            fromClass: Ville::class
        )
    ],
    normalizationContext: ["groups" => ["ville:read"]]
)]
```

```
GET https://localhost/api/joueurs/1/ville

{
    "@id": "/api/joueurs/1/ville",
    "@type": "Ville",
    "id": 2,
    "nom": "Montpellier",
    "codePostal": 34000
}
```

Ou bien la liste des habitants d'une ville :

```php
#[ApiResource(
    uriTemplate: '/villes/{idVille}/habitants',
    operations: [new GetCollection()],
    uriVariables: [
        'idVille' => new Link(
            toProperty: 'ville',
            fromClass: Ville::class
        )
    ],
    normalizationContext: ["groups" => ['joueur:read']]
)]
class Joueur
{
    ...
}
```

```
GET https://localhost/api/villes/2/joueurs

{
    "@id": "/api/villes/2/joueurs",
    "@type": "Collection",
    "totalItems": 3,
    "member": [
        {
            "@id": "/api/joueurs/1",
            "@type": "Joueur",
            "id": 1,
            "nom": "Smith",
            "prenom": "John",
            "ville": "/api/villes/2",
        },
        {
            ...
        },
        {
            ...
        },
    ]
}
```

Jusqu'ici, c'est plus ou moins ce que vous aviez déjà vu dans le [TD4]({{site.baseurl}}/tutorials/tutorial4).

### Sous-ressource

La relation `Resultat-Joueur` est légèrement différente de la relation `Joueur-Ville`.

Un résultat est **fortement couplé** au joueur, de telle manière qu'un **résultat** ne peut pas exister sans
le joueur propriétaire (si on supprime le joueur, les objets résultats liés au joueur doivent aussi être supprimés).

On pourrait donc parler de **composition forte** dans ce cas (ou l'agrégation noire, en UML).

Actuellement, comme pour les joueurs, il est possible de simplement créer l'entité et de la lier au joueur, avec une route classique :

```
POST https://localhost/api/resultats
{
    "nombrePoints": 15,
    "joueur": "/api/joueurs/1"
}
```

Mais cela ne fait pas bien sens, car on pourrait avoir l'impression qu'un résultat est une ressource indépendante, alors qu'elle est fortement dépendante de `Joueur`. Aussi, avec un `PATCH`, on pourrait changer le propriétaire du résultat, ce qui ne semble pas logique !

Pour améliorer l'architecture de l'API, on peut alors remplacer la route précédente `/api/resultats` par une route désignant les `resultats` comme une **sous-ressource** de `Joueur`.

Cette route aura alors la forme suivante : 

 * `/api/joueurs/{idJoueur}/resultats` : pour créer un nouveau résultat (`POST`) et pour récupérer tous les `resultats` du joueur (`GetCollection`).
 * `/api/joueurs/{idJoueur}/resultats/{idResultat}` : pour lire (`GET`), modifier (`PATCH` et/ou `PUT`) et supprimer (`DELETE`) un résultat d'un joueur donné.

Avec cette nouvelle route, **on ne précisera alors plus explicitement l'identifiant du joueur**. Il sera affecté automatiquement (grâce à l'identifiant passé dans la route). On bloque aussi l'écriture du champ `joueur` afin d'empêcher le changement de propriétaire d'un résultat avec un `PATCH`.

API Platform nous permet de mettre en place ce système en définissant des **routes** customisées en configurant correctement plusieurs  
attributs `[ApiResource]` (et en supprimant celui par défaut) dans l'entité `Resultat` :

```php
#[ORM\Entity(repositoryClass: ResultatRepository::class)]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/resultats',
    operations: [
        new Post(provider: CreateProvider::class),
        new GetCollection(),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'joueur',
            fromClass: Joueur::class,
        )
    ]
)]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/resultats/{idResultat}',
    operations: [
        new Get(),
        new Delete(),
        new Patch()
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'joueur',
            fromClass: Joueur::class,
        ),
        'idResultat' => new Link(
            fromClass: Resultat::class,
        ),
    ]
)]
class Resultat
{
    ...

    #[ApiProperty(writable: false)]
    #[ORM\ManyToOne(inversedBy: 'resultats')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Joueur $joueur = null;
}
```


Ajout d'un résultat au joueur 1 :
```
POST https://localhost/api/joueurs/1/resultats
{
    "nombrePoints": 15
}
```

Récupération de tous les résultats du joueur 1 :
```
GET https://localhost/api/joueurs/1/resultats
```

Récupération du résultat 1, lié au joueur 1 :
```
GET https://localhost/api/joueurs/1/resultats/1
```

Mise à jour du résultat 1, lié au joueur 1 :
```
PATCH https://localhost/api/joueurs/1/resultats/1
{
    "nombrePoints": 23
}
```

Suppression du résultat 1, lié au joueur 1 :
```
DELETE https://localhost/api/joueurs/1/resultats/1
```

Là aussi, lors de la lecture des données les `IRI` vont s'afficher pour les entités en relation. 

Comme d'habitude, on peut utiliser la **normalisation** pour afficher les données désirées.

Une autre fonctionnalité intéressante à rajouter (dans ce contexte) est le fait de pouvoir directement créer des résultats lorsqu'on créé un joueur :

```
POST https://localhost/api/joueurs
{
    "nom": "Kaecoute",
    "prenom": "Xavier",
    "ville": "/api/villes/1",
    "resultats": [
        {"nombrePoints": 30},
        {"nombrePoints": 15},
        {"nombrePoints": 42}
    ]
}
```

Pour cela, on doit explicitement autoriser l'écriture de la **Collection** `$resultats` de `Joueur` avec des groupes de **dénormalisation** :

```php
#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read', 'ville:read', 'resultat:read']],
    denormalizationContext: ["groups" => ['joueur:write', 'resultat:write']]
)]
class Joueur
{
    ...

    /**
     * @var Collection<int, Resultat>
     */
    #[ORM\OneToMany(targetEntity: Resultat::class, mappedBy: 'joueur', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['joueur:read', 'joueur:write'])]
    private Collection $resultats;

    ...
}

#[ORM\Entity(repositoryClass: ResultatRepository::class)]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/resultats',
    ...
    normalizationContext: ["groups" => ['resultat:read']],
    denormalizationContext: ["groups" => ['resultat:write']]
)]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/resultats/{idResultat}',
    ...
    normalizationContext: ["groups" => ['resultat:read']],
    denormalizationContext: ["groups" => ['resultat:write']]
)]
class Resultat
{
    ...

    #[ORM\Column]
    #[Groups(['resultat:read', 'resultat:write'])]
    private ?int $nombrePoints = null;

    ...
}
```

Attention, toutefois, cette implémentation est assez **dangereuse** !
en effet, maintenant, on pourrait simplement écrire :

```
PATCH https://localhost/api/joueurs/3
{
    "resultats": [
        "/api/joueurs/1/resultats/2"
    ]
}
```

Ce qui permettrait de "voler" un résultat d'un autre joueur, dans notre contexte !
Bref, ajouter cette fonctionnalité est intéressante, mais elle est risquée, car il faut en assurer la sécurité.
On peut par exemple créer une [assertion dédidée](https://stackoverflow.com/questions/69257642/how-not-to-allow-an-iri-when-denormalizing-embedded-relations)
afin d'empêcher de poster des données avec des IRIs pointant sur des ressources existantes.

Sinon, on peut simplement ne pas inclure cette fonctionnalité (ne pas autoriser l'écriture de la collection **résultats**) et s'en tenir à nos routes :

* `/api/joueurs/{idJoueur}/resultats` : pour ajouter ou consulter les résultats d'un joueur.
* `/api/joueurs/{idJoueur}/resultats/{idResultat}` : pour accéder, modifier ou supprimer le résultat d'un joueur (en interdisant l'écriture de la propriété **joueur**).

## Les relations un-un (OneToOne)

Dans la pratique, les relations un-un (**OneToOne**) sont assez peu utilisées, car les 
informations correspondantes peuvent simplement être mises à disposition dans une seule des deux entités.

Cependant, elle peut éventuellement être utilisée dans le cas d'informations qui sont renseignées peu 
fréquemment, afin éviter d'avoir un ensemble d'attributs **null** dans l'entité propriétaire. Ou bien s'il n'y a
pas de relations fortes (composition forte) entre les deux entités.

Ces relations se gèrent globalement comme les relations **un-plusieurs** que nous avons abordé lors du point précédent. La seule
différence est qu'il n'y a pas de collections à gérer. On aura une clé étrangère dans une des deux entités.

Cependant, le choix des **cardinalités** minimum va potentiellement influer sur certains choix.

Prenons l'exemple suivant : Un **joueur** peut posséder un **casier** qui a un identifiant, une taille et une couleur :

* Si le joueur possède entre 0 et 1 casier et qu'un casier possède entre 0 et 1 propriétaire : 

    * Il existe des joueurs sans casier et des casiers sans propriétaire. Dans ce cas, la ressource **casier** peut exister indépendamment et posséder ses propres routes.

    * On a des routes `/casiers`, `/casier/{idCasier}`, etc. 
    
    * La suppression d'un joueur n'entraîne pas la suppression de son casier (s'il en a un), mais on désaffecte simplement le propriétaire.

* Si le joueur possède obligatoirement un casier, mais qu'un casier possède entre 0 et 1 propriétaire :

    * Le casier reste indépendant, mais on doit obligatoirement affecter un casier lors de la création d'un joueur (il faut donc qu'il en existe un avant, sans proprietaire).

    * La suppression du joueur n'entraîne toujours pas la suppression du casier.

* Si le joueur possède optionnellement un casier, mais qu'un casier possède obligatoirement un propriétaire :

    * Le casier n'est plus vraiment indépendant (on doit toujours avoir un propriétaire), et va plutôt être lié fortement au joueur (composition forte).

    * Le **casier** va probablement plutôt être une **sous-ressource** de **joueur** (comme pour les **résultat**).

    * On aura des routes `/joueurs/{idJoueur}/casiers` et `/joueurs/{idJoueur}/casiers/{idCasier}`.

Dans notre modélisation, nous allons faire le choix qu'un joueur possède optionnellement un casier et qu'un casier possède optionnellement un joueur.

Le casier est donc indépendant : cela se traduit par le fait que la clé étrangère peut être **nulle**.

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/ModeleEA2.PNG)
</div>

Ce qui donnerait le schéma relationnel suivant :

* **Joueur**(<u>id</u>, nom, prenom, #idVille)
* **Casier**(<u>id</u>, taille, couleur, #idJoueurProprietaire)

Ou bien :

* **Joueur**(<u>id</u>, nom, prenom, #idVille, #idCasier)
* **Casier**(<u>id</u>, taille, couleur)

Si on souhaite accéder à la fois au propriétaire d'un casier et au casier d'un joueur, en conception, on a affaire à 
une relation **bidirectionnelle** et dans le cas de relations `OneToOne`, doctrine conseille généralement d'éviter cela quand c'est possible.

Dans notre cas, nous allons décider de tout de même vouloir connaître le propriétaire d'un casier 
(pour savoir s'il est affecté ou non) ainsi que le casier d'un joueur.

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/DiagrammeClasses2.PNG)
</div>

On peut aussi créer des **routes** pour connaître le casier d'un joueur et/ou le propriétaire d'un casier.

Bref, tout cela donnera, du côté de l'application :

```php
#[ORM\Entity(repositoryClass: CasierRepository::class)]
#[ApiResource]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/casier',
    operations: [
        new Get(),
    ],
    uriVariables: [
        'idJoueur' => new Link(
            toProperty: 'proprietaire',
            fromClass: Joueur::class,
        )
    ]
)]
class Casier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $taille = null;

    #[ORM\Column(length: 255)]
    private ?string $couleur = null;

    #[ORM\OneToOne(inversedBy: 'casier', cascade: ['persist', 'remove'])]
    private ?Joueur $proprietaire = null;

    //Methodes...
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read', 'ville:read', 'resultat:read']],
    denormalizationContext: ["groups" => ['joueur:write', 'resultat:write']]
)]
...
#[ApiResource(
    uriTemplate: '/casiers/{idCasier}/joueur',
    operations: [
        new Get(),
    ],
    uriVariables: [
        'idCasier' => new Link(
            toProperty: 'casier',
            fromClass: Casier::class,
        )
    ],
    normalizationContext: ["groups" => ['joueur:read', 'ville:read']],
)]
class Joueur
{
    ...

    #[ORM\OneToOne(mappedBy: 'proprietaire', cascade: ['persist', 'remove'])]
    #[ApiProperty(writable: false)]
    #[Groups(['joueur:read'])]
    private ?Casier $casier = null;
}
```

Là aussi, il faudrait faire attention à la sécurité et ne pas autoriser un joueur à "voler" le casier d'un autre joueur 
(en gérant les groupes de dénormalisation, avec des voters, etc).

Bref, l'utilisation des relations **un-un** reste assez rare et nous utiliserons beaucoup plus les relations **un-plusieurs** et **plusieurs-plusieurs**.

## Conclusion

Dans cette première partie, vous avez appris à plus amplement gérer les relations **un-plusieurs** et **un-un**, notamment avec l'utilisation
de sous-ressources.

Dans la [seconde partie]({{site.baseurl}}/complements/complement3), nous allons nous attaquer à un sujet plus complexe : la gestion des relations **plusieurs-plusieurs** (puis, plus globalement, las associations **porteuses** et **n-aire**) en étudiant différentes solutions.
