---
title: API PLatform - Gestion des relations entre les entités (un-plusieurs, un-un, plusieurs-plusieurs)
subtitle:
layout: tutorial
lang: fr
---

## Introduction

Dans le [TD4](), nous avons utilisé **API Platform** afin de modéliser l'API de **The Feed**.

Cependant, comme le modèle de données associé à ce réseau social n'est pas très riche, il n'y a pas beaucoup 
de relations entre les entités.

La seule relation que vous avez dû gérer se trouve au niveau d'une publication, pour affecter son auteur 
(de plus, nous avons fait en sorte de ne même pas avoir à le préciser dans le **payload** d'une `Publication` 
en utilisant l'identifiant de l'utilisateur connecté).

Si les relations de type **un-plusieurs** (`OneToMany` et `ManyToOne`) sont assez triviales à gérer, cela n'est pas 
forcément le cas pour :

* Les relations **plusieurs-plusieurs** (`ManyToMany`) qui traduisent une association binaire simple.
* Les relations qui traduisent une association **n-aire** au-delà de deux entités (ternaire, etc).
* Les relations qui traduisent une association **n-aire porteuse** (qui possède des données en dehors des 
clés étrangères qui composent l'association).

Dans ce complément de TD, nous allons explorer différents cas de figures à partir d'un exemple simple afin d'étudier 
les différentes solutions à notre disposition pour gérer ces différents cas.

## Les relations un-plusieurs (OneToMany et ManyToOne)

Une relation **un-plusieurs** entre une entité `A` et une entité `B` signifie que `A` peut être lié à plusieurs instances 
de `B` alors que `B` n'est lié (au maximum) qu'à une seule instance de `A`.

Concrètement, dans la base de données cela se traduit par une clé étrangère (vers la clé primaire de `A`) au niveau 
de l'entité `B` (qui peut être nulle ou non).

Au niveau du **code**, on pourra avoir un attribut de type `A` dans `B` et éventuellement une collection d'entités `B` dans `A`.

Pour commencer, nous allons définir un modèle E/A de base (phase d'analayse) et l'enrichir au fur et à mesure des exemples :

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
    "@id": "/demo_complement/public/api/villes/2/joueurs",
    "@type": "Collection",
    "totalItems": 3,
    "member": [
        {
            "@id": "/demo_complement/public/api/joueurs/1",
            "@type": "Joueur",
            "id": 1,
            "nom": "Smith",
            "prenom": "John",
            "ville": "/demo_complement/public/api/villes/2",
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

Jusqu'ici, c'est plus ou moins ce que vous aviez déjà vu dans le [TD4]().

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

API Platform nous permet de mettre en place ce système en remplaçant l'attribut `[ApiPLatform]` par défaut dans l'entité `Resultat` :

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

Une autre fonctionnalité intéressante à rajouter (dans ce contexte) est le fait de pouvoir directement créer des résultats lorsqu'ont créé un joueur :

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
    normalizationContext: ["groups" => ['joueur:read']],
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
    denormalizationContext: ["groups" => ['resultat:write']]
)]
#[ApiResource(
    uriTemplate: '/joueurs/{idJoueur}/resultats/{idResultat}',
    ...
    denormalizationContext: ["groups" => ['resultat:write']]
)]
class Resultat
{
    ...

    #[ORM\Column]
    #[Groups(['resultat:write'])]
    private ?int $nombrePoints = null;

    ...
}
```

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

<div">
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
#[ApiResource(
    normalizationContext: ["groups" => ['casier:read']],
)]
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
    normalizationContext: ["groups" => ['joueur:read']],
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
    #[Groups(['joueur:read', 'joueur:write'])]
    private ?Casier $casier = null;
}
```

Bref, en conclusion, l'utilisation des relations **un-un** reste assez rare et nous utiliserons beaucoup plus les 
relations **un-plusieurs** et **plusieurs-plusieurs**.

## Les relations plusieurs-plusieurs (ManyToMany), associations porteuses et n-aire

La gestion des relations types **plusieurs-plusieurs** (**binaires**), ou qui impliquent plus de deux entités
(**ternaires**, etc), incluant ou non des données (**association porteuse**) peut s'avérer compliquée de bien des manières !

Pour illustrer cela, nous allons faire évoluter notre modélisation en incluant des **clubs** auxquels peut s'inscrire un joueur.
Un joueur peut s'inscrire à différents **clubs** et un **club** peut possèder plusieurs **membres**.

On va modéliser cela avec une association binaire **plusieurs-plusieurs** (`ManyToMany`) simple :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/ModeleEA3.PNG)
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
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/DiagrammeClasses3.PNG)
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
    normalizationContext: ["groups" => ['joueur:read', 'ville:read']],
)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Joueur>
     */
    #[ORM\ManyToMany(targetEntity: Joueur::class, inversedBy: 'clubs')]
    private Collection $membres;

    //Methodes...
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read']],
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
    normalizationContext: ["groups" => ['joueur:read', 'ville:read']],
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

<!--

* Pour les relations **binaires plusieurs-plusieurs** simples (non porteuses), on peut conserver l'utilisation de `ManyToMany` et utiliser une "ressource virtuelle" (non stockée). Nous en reparlerons.

* Sinon, pour les relations **binaires** et autres cas (n-aire plus complexe, porteuse...) on peut ne pas utiliser `ManyToMany` :

    * Dans notre exemple, créer une entité **Inscription** dont avec une [clé composite](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/tutorials/composite-primary-keys.html) liée à la fois à un **joueur** et un **club** :

        * En base de données, cela génèrera une table **Inscription**(<u>#idJoueur</u>, <u>#idClub</u>) comme celle présentée dans le schéma relationnel.

        * Notre entité `Inscription` à deux relations `ManyToOne` : une vers le `Joueur` et l'autre vers `Club`. 
        
        * Du côté de `Joueur` et `Club`, on a alors des **collections** d'entités `Inscription` (on supprime les relations `ManyToOne` entre **Club** et **Joueur**).

        * On peut créer, supprimer ou mettre à jour des entités **Inscription** en utilisant les identifiants du joueur et du club concernés.

        * Cette méthode respecte la modélisation initiale du modèle E/A.

        * La contrainte d'unicité est naturellement gérée au travers de la clé (pas deux fois la même inscription d'un joueur à un club).

        * Dans le pratique, même si Doctrine gère les **clés composites**, [il est déconseillé de les utiliser](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/best-practices.html#avoid-composite-keys) par soucis de performance.

        * La mise en place d'une telle méthode est difficile au travers d'API Platform (beaucoup de code à écrire).

    * Ou alors, toujours dans notre exemple, créer une entité **Inscription** avec un **identifiant** (clé) numérique **simple** et deux attributs **joueur** et **club** (clés étrangères, mais ne font pas partie de la clé primaire).

        * En base de données, cela génèrera une table **Inscription**(<u>idInscription</u>, #idJoueur, #idClub) correspondant à une **entité coordinatrice**.

        * Notre entité `Inscription` à deux relations `ManyToOne` : une vers le `Joueur` et l'autre vers `Club`.

        * Du côté de `Joueur` et `Club`, on a alors des **collections** d'entités `Inscription` (on supprime les relations `ManyToOne` entre **Club** et **Joueur**).

        * `Inscription` est une ressource à part entière et possède **son propre identifiant**.

        * On peut créer, supprimer ou mettre à jour des entités **Inscription** en utilisant leurs identifiants propres.

        * Cette méthode ne respecte pas vraiment la modélisation initiale du modèle E/A, car elle utilise une **entité coordinatrice** au lieu d'une **association binaire**.

        * Il faut explicitement gérer la **contrainte d'unicité** (et **NOT NULL**) sur le couple **(#idJoueur, #idClub)** (très facile avec **Symfony** et **Doctrine**).

        * Solution la plus flexible en pratique, et facilement prise en charge par **Api Platform**, sans causer de soucis de performances avec **Doctrine**.

        * De manière générale, l'utilisation de clés primaires simples (et donc de classes coordinatrices où la contrainte d'unicité est explicitement gérée) à la place d'associations "plusieurs-plusieurs" est recommandée par une partie des développeurs.

En bref, même si la solution de la **clé composite** semble en adéquation avec le modèle E/A, il est assez déconseillé de choisir cette voie.

Pour la suite, nous allons donc favoriser l'utilisation de la dernière option : création d'une entité et d'une ressource `Inscription` 
dédiée qui possède ses propres identifiants. C'est la solution la plus flexible et adaptable dans toutes les situations.

Nous verrons aussi l'utilisation d'une "ressource virtuelle" non stockée (seulement pour gérer les cas de `ManyToMany` simples).
-->

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
des opérations `POST`, `PUT` et `PATCH`. Dans le [TD4](), nous en avions utilisé deux pour affecter automatiquement 
l'auteur d'un message, mais aussi pour hacher le mot de passe de l'utilisateur.

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

    * On sauvegarde la mise à jour de l'utilisateur en utilisant la méthode `flush` du service `EntityManager`.

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

Dans la logique d'utiliser une **entité coordinatrice dédiée**, nous allons adapter notre **modèle E/A** en conséquence :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/ModeleEA4.PNG)
</div>

Ce qui donnerait alors le schéma relationnel suivant :

* **Joueur**(<u>id</u>, nom, prenom, #idVille)
* **Club**(<u>id</u>, nom)
* **Inscription**(<u>id</u>, #idJoueur, #idClub)

On constate bien que la contrainte d'unicité sur le couple de clés étrangères n'est donc plus gérée naturellement (car plus dans la clé primaire), il faudra la préciser nous-même.
Il faudra aussi spécifier que `#idJoueur` et `#idClub` ne peuvent pas être **nuls** (tout cela peut se faire à travers **Symfony** et **Doctrine**).

En termes de **conception**, on adapte aussi le **diagramme de classes de conception** en conséquence :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/DiagrammeClasses4.PNG)
</div>

À noter que la bidirectionnelle est facultative. 
On pourrait éventuellement se passer des collections **inscirptions** d'un côté ou même des deux (on pourra toujours créer des routes dédiées pour trouver l'information).

La mise en place de cette solution se fait en plusieurs étapes :

1. On n'associe pas `Joueur` et `Club` avec une `ManyToMany`.

2. À la place, on crée une nouvelle entité `Inscription` composée :

    * D'un identifiant (généré automatiquement quand on crée l'entité avec la commande `make:entity`).

    * D'une relation `ManyToOne` avec `Joueur` qui **ne doit pas pouvoir être nulle** qui peut être éventuellement bidirectionnelle (collection d'inscriptions dans `Joueur`).

    * D'une relation `ManyToOne` avec `Club` qui **ne doit pas pouvoir être nulle** qui peut être éventuellement bidirectionnelle (collection d'inscriptions dans `Club`).

3. On ajoute une **contrainte d'unicité pour la base de données** sur le couple de clés étrangères référençant `Joueur` et `Club` grâce à l'attribut `#[ORM\UniqueConstraint]`.

4. On ajoute une **contrainte d'unicité pour l'application** sur le couple d'attributs `joueur` et `club` grâce à l'attribut `#[ORM\UniqueEntity]`.

5. On met à jour la structure de la base de données avec doctrine.

Si l'entité évolue (porteuse de données, ternaire, etc...) il suffira de rajouter de nouveaux attributs et/ou d'adapter les contraintes d'unicité.

Tout cela donnera, du côté de l'application :

```php
#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[UniqueEntity(fields: ['joueur', 'club'], message: "Un joueur ne peut pas être inscrit plus d'une fois au même club.")]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_JOUEUR_CLUB', fields: ['joueur', 'club'])]
#[ApiResource]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['club:read'])]
    private ?Joueur $joueur = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['joueur:read'])]
    private ?Club $club = null;
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read']],
    denormalizationContext: ["groups" => ['joueur:write', 'resultat:write']]
)]
class Joueur
{
#[ORM\Id]

    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['joueur:read', 'club:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['joueur:read', 'joueur:write', 'club:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['joueur:read', 'joueur:write', 'club:read'])]
    private ?string $prenom = null;

    ...

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'joueur', orphanRemoval: true)]
    #[Groups(['joueur:read'])]
    private Collection $inscriptions;
}

#[ORM\Entity(repositoryClass: ClubRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['club:read']],
    denormalizationContext: ["groups" => ['club:write']]
)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['club:read', 'joueur:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['club:read', 'club:write', 'joueur:read'])]
    private ?string $nom = null;

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'club', orphanRemoval: true)]
    #[Groups(['club:read'])]
    private Collection $inscriptions;
}
```

Attention, comme montré dans cet exemple, si on souhaite afficher des données sur les clubs/joueurs (autre que l'IRI) 
quand on lit un club ou un joueur, il faut bien gérer les **groupes de sérialisation** (pour la lecture/normalisation). 

Il faut faire attention de ne pas boucler (joueur affiche club qui affiche ses membres, qui affiche les clubs des 
membres, qui affichent leurs membres...). Ici, quand on récupère un **joueur**, les données des **clubs** où il est inscrit 
seront lues, mais pas la liste de ses membres de ses clubs. Et quand on récupère un **club**, la liste des joueurs **membres** 
inscrits est lue, mais pas leurs **clubs**.

### Choix de la route

#### Routes par défaut

#### Routes avec sous-ressource

#### Routes composée avec les identifiants

### Évolution de la ressource

## Conclusion
