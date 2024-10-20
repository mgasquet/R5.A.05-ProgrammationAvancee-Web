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

<div style="margin:auto;width:75%">
 ![modele e/a 1]({{site.baseurl}}/assets/complement2/ModeleEA1.PNG)
</div>

Ce qui donnerait le schéma relationnel suivant :

* **Joueur**(<u>id</u>, nom, prenom, #idVille)
* **Ville**(<u>id</u>, nom, codePostal)
* **Resultat**(<u>id</u>, nombrePoints, #idJoueur)

Enfin, en conception, après certains choix, on obtiendrait alors le diagramme de classes suivant :

<div style="margin:auto;width:75%">
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

### Ressource à part entière

La première relation à laquelle nous allons nous intéresser est `Joueur-Ville`.

Cette relation est très facile à maintenir : on précise l'**IRI** de la ville en question dans le **payload** du Joueur :

```
POST https://localhost/api/villes
{
    "nom": "Nîmes",
    "codePostal": 30000
}

Renvoi :

{
    "@id": "/api/villes/1"
    "id": 1,
    "nom": "Nîmes",
    "codePostal": 30000
}

POST https://localhost/api/villes
{
    "nom": "Montpellier",
    "codePostal": 34000
}

Renvoi :

{
    "@id": "/api/villes/2"
    "id": 2,
    "nom": "Montpellier",
    "codePostal": 34000
}

Création d'un joueur qui habite à Nîmes :

POST https://localhost/api/joueurs
{
    "nom": "Smith",
    "prenom": "John",
    "ville": "/api/villes/1"
}

Renvoi :

{
    "@id": "/api/joueurs/1"
    "id": 1,
    "nom": "Smith",
    "prenom": "John",
    "ville": "/api/villes/1",
    "resultats" : []
}

On ne veut plus connaître la ville de résidence de ce joueur :

PATCH https://localhost/api/joueurs/1
{
    "ville": null
}

Changement de ville : 

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

Renvoi :

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

Renvoi :

{
    "@id": "/api/joueurs/1/ville",
    "@type": "Ville",
    "id": 2,
    "nom": "Montpellier",
    "codePostal": 34000
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

```
Ajout d'un résultat au joueur 1 :

POST https://localhost/api/joueurs/1/resultats
{
    "nombrePoints": 15
}

Récupération de tous les résultats du joueur 1 :

GET https://localhost/api/joueurs/1/resultats

Récupération du résultat 1, lié au joueur 1 :

GET https://localhost/api/joueurs/1/resultats/1

Mise à jour du résultat 1, lié au joueur 1 :

PATCH https://localhost/api/joueurs/1/resultats/1
{
    "nombrePoints": 23
}

Supression du résultat 1, lié au joueur 1 :

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



## Les relations plusieurs-plusieurs (ManyToMany), associations porteuses et n-aire

### Utilisation d'une ressource dédiée

### Choix de la route

#### Route par défaut

#### Route avec sous-ressource

#### Route composée avec les identifiants

### Utilisation d'une ressource virtuelle

## Conclusion
