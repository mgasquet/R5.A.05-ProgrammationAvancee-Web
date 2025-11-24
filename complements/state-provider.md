---
title: API Platform - Les State Providers
layout: tutorial
lang: fr
---

## Introduction

Dans le [TD4]() nous vu qu'il existe deux types de classes importantes dans `API Platform` : Les `StateProcessor` et les `StateProvider`.

* Un `StateProvider` est une classe qui permet de récupérer les données de l'entité (dans la base de données), à 
partir des données de la route. Elle est utilisée dans les opérations `GET`, `POST`, `PUT`, `PATCH` et `DELETE`.
Par défaut, **API Platform** utilise un **provider** par défaut, mais s'il y a besoin d'effectuer un traitement 
particulier, on peut coder et fournir son propre **StateProvider**. Dans le cas d'une opération `POST`, le provider
par défaut instancie juste la nouvelle entité.

* Un `StateProcessor` est une classe qui permet de traiter et modifier un objet lors d'opérations qui vont changer son état. 
Elle est donc utilisée dans le cadre des opérations `POST`, `PUT` et `PATCH` et `DELETE`. Dans le [TD4]({{site.baseurl}}/tutorials/tutorial4), 
nous en avions utilisé deux pour affecter automatiquement l'auteur d'un message, mais aussi pour hacher le mot de passe de l'utilisateur.

En résumé, un `StateProvider` permet de récupérer et traiter l'objet avant traitement, et 
un `StateProcessor` de traiter l'objet après l'envoi des données par client et avant sa sauvegarde.

Durant le TD, nous avons utilisé les **states processors** mais pas de **state providers**. Ces services peuvent être utiles dans divers cas :

* Données à traiter/transformer avant qu'elles ne soient renvoyées au client ou traitées par une opération d'écriture.
* Données à aller chercher sur une autre source de données que la base de données.
* Données à aller chercher en effectuant une requête spéciale, par exemple avec du [DQL]({{site.baseurl}}/complements/dql).

Bref, cet outil est aussi utile que les **state processors**.

## Manipuler les states providers

**API Platform** fournit une commande pour créer la base d'un **StateProvider** :

```bash
php bin/console make:state-provider MonProvider
```

Ce qui créé la classe `MonProvider` dans le dossier `src/State`.

Il ne reste alors plus qu'à la compléter :

```php
namespace App\State;

class MonProvider implements ProviderInterface
{
    //Injections de dépendances possibles...
    public function __construct()
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //Traitement...
        return $objet;
    }
}
```

Comme pour les **state processors** il est possible de **décorer** un **state provider** pour utiliser le traitement par défaut puis rajouter sa logique, si besoin :


```php
namespace App\State;

class MonProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $provider,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $objet = $this->provider->provide($operation, $uriVariables, $context);
        //Traitement...
        return $objet;
    }
}
```

## Exemple

Prenons l'exemple suivant :

```php
namespace App\Entity;

class Film {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column]
    private ?int $anneeSortie = null;
}
```

```php
namespace App\Entity;

class Ville {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $codePostal = null;

    /**
     * @var Collection<int, Film>
     */
    #[ORM\ManyToMany(targetEntity: Cinema::class, inversedBy: 'filmsDiffuses')]
    private Collection $cinemasDiffusion;
}
```

```php
namespace App\Entity;

class Cinema {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\ManyToOne]
    private ?Ville $ville = null;

    /**
     * @var Collection<int, Film>
     */
    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'cinemasDiffusion')]
    private Collection $filmsDiffuses;
}
```

On souhaite disposer d'une route : `/api/villes/{idVille}/cinemas/films` qui permet de retrouver tous les films qui sont diffusés dans les cinémas d'une ville, triées du plus récent au plus ancien. De base, **API Platform** ne peut pas traiter cette requête, même avec une sous-ressource. Nous allons donc devoir traiter cela avec un **state provider** customisé, qui effectue une requête [DQL]({{site.baseurl}}/complements/dql) :

```php
namespace App\Repository;

use App\Entity\Film;
use App\Entity\Ville;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
* @extends ServiceEntityRepository<Film>
*/
class FilmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Film::class);
    }

    /**
    * @return Film[]
    */
    public function findFilmsQuiPassentDansVille(Ville $ville) : array {
        $queryBuilder = $this->createQueryBuilder('f')
            ->join('f.cinemasDiffusion', 'c')
            ->join('c.ville', 'v')
            ->where('v.id = :idVille')
            ->orderBy('f.anneeSortie', 'DESC')
            ->setParameter('idVille', $ville->getId());
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }
}
```

```php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\FilmRepository;
use App\Repository\VilleRepository;

class FilmVilleProvider implements ProviderInterface
{
    public function __construct(
        private VilleRepository $villeRepository,
        private FilmRepository $filmRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $ville = $this->villeRepository->findOneBy(['id' => $uriVariables['idVille']]);
        if($ville != null) {
            return $this->filmRepository->findFilmsQuiPassentDansVille($ville);
        }
        //Provoque une erreur 404 si retourne null dans un GetCollection
        return null;
    }
}
```

```php
namespace App\Entity;

use App\State\FilmVilleProvider;

#[ApiResource(
    uriTemplate: '/villes/{idVille}/cinemas/films',
    operations: [
        new GetCollection()
    ],
    uriVariables: [
        "idVille" => new Link(fromClass: Ville::class)
    ],
    provider: FilmVilleProvider::class
)]
class Film
{
    ...
}
```

## Conclusion

Les **state providers** sont donc très pratiques afin de proposer des routes particulières où les mécanismes de base d'**API Platform** ne suffisent pas à réaliser le traitement désiré.

Vous pouvez trouver plus d'information sur la [documentation officielle](https://api-platform.com/docs/core/state-providers/).