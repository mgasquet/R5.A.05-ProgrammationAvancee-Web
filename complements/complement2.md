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

En conception, on évite généralement d'utiliser des **bi-directionnelles** quand cela est possible, car elles peuvent être compliquées
à maintenir. Cependant, **Doctrine** gère très bien cela de façon automatique sans intervention du développeur. On peut donc les utiliser
sans trop de problèmes. Nous n'allons pas le faire ici : un joueur connaît sa ville, mais la ville ne connaît pas (directement) la liste de ses
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
    normalizationContext: ["groups" => ['joueur:read']]
)]
class Joueur
{
    ...

    #[ORM\ManyToOne]
    #[Groups(['joueur:read'])]
    private ?Ville $ville = null;

    ...
}

#[ApiResource]
class Ville
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['joueur:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['joueur:read'])]
    private ?string $nom = null;

    #[ORM\Column]
    #[Groups(['joueur:read'])]
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

Je peux ensuite créer une route spéciale pour directement obtenir les détails de la ville d'un joueur :

```php
#[ApiResource]
#[ApiResource(
    uriTemplate: '/joueurs/{id}/ville',
    operations: [new Get()],
    uriVariables: [
        'id' => new Link(
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
    #[Groups(['joueur:read', 'ville:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['joueur:read', 'ville:read'])]
    private ?string $nom = null;

    #[ORM\Column]
    #[Groups(['joueur:read', 'ville:read'])]
    private ?int $codePostal = null;
}
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
    uriTemplate: '/villes/{id}/joueurs',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'ville',
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
    operations: [
        ...
        new Patch(
            denormalizationContext: ["groups" => ['resultat:write']]
        )
    ],
    ...
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
une relation **bi-directionnelle** et dans le cas de relations `OneToOne`, doctrine conseille généralement d'éviter cela quand c'est possible.

Dans notre cas, nous allons décider de tout de même vouloir connaître le propriétaire d'un casier 
(pour savoir s'il est affecté ou non) ainsi que le casier d'un joueur.

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/DiagrammeClasses2.PNG)
</div>

Ce qui donnera, du côté de l'application :

```php
#[ORM\Entity(repositoryClass: CasierRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['casier:read']],
)]
class Casier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['casier:read', 'joueur:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['casier:read', 'joueur:read'])]
    private ?int $taille = null;

    #[ORM\Column(length: 255)]
    #[Groups(['casier:read', 'joueur:read'])]
    private ?string $couleur = null;

    #[ORM\OneToOne(inversedBy: 'casier', cascade: ['persist', 'remove'])]
    #[Groups(['casier:read'])]
    private ?Joueur $proprietaire = null;

    //Methodes...
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read']],
    denormalizationContext: ["groups" => ['joueur:write', 'resultat:write']]
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
(liste de **clubs** dans **Joueur** ou bien liste de **joueurs** dans **Club**) ou bien des deux côtés (bi-directionnelle).

Comme **Doctrine** gère correctement les propriétés **bi-directionnelles**, nous allons décider d'avoir à la fois avoir 
une collection listant les clubs d'un joueur (dans `Joueur`) et une autre collection listant les membres d'un club (dans `Club`).

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/DiagrammeClasses3.PNG)
</div>

Ce qui donnera, du côté de l'application :

```php
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
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['club:read', 'joueur:read', 'club:write'])]
    private ?string $nom = null;

    /**
     * @var Collection<int, Joueur>
     */
    #[ORM\ManyToMany(targetEntity: Joueur::class, inversedBy: 'clubs')]
    #[Groups(['club:read', 'joueur:read', 'club:write'])]
    private Collection $membres;

    //Methodes...
}

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ApiResource(
    normalizationContext: ["groups" => ['joueur:read']],
    denormalizationContext: ["groups" => ['joueur:write', 'resultat:write']]
)]
class Joueur
{
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
     * @var Collection<int, Club>
     */
    #[ORM\ManyToMany(targetEntity: Club::class, mappedBy: 'membres')]
    #[Groups(['joueur:read', 'joueur:write'])]
    private Collection $clubs;

    ...
}
```

Et on peut bien sûr créer une route spéciale pour lister les clubs d'un joueur et 
inversement (`/joueurs/{id}/clubs` et/ou `/clubs/{id}/membres`).

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
effectuer un `PATCH` du joueur ou du club. C'est la problématique que nous allons essayer de résoudre par la suite.

Il va d'abord falloir faire des choix quant à la **méthode** utilisée afin d'implémenter cette fonctionnalité :

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

### Utilisation d'une entité et ressource dédiées

Dans la logique d'utiliser une **entité dédiée**, nous allons adapter notre **modèle E/A** en conséquence :

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

### Utilisation d'une ressource virtuelle pour les Many-To-Many simples

### Évolution de la ressource

## Conclusion
