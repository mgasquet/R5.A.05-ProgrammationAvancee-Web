---
title: Le Doctrine Query Language (DQL)
layout: tutorial
lang: fr
---

## Introduction

Jusqu'ici, afin d'interroger la base de données, nous avons utilisé les diverses fonctionnalités offertes par les **repository** de nos entités : `find`, `findAll`, `findBy`, `findOneBy`.

Même si ces fonctions sont très utiles et couvrent beaucoup de cas, il se peut que dans certains cas, nous ayons besoin d'effectuer une **requête** plus précise (ou plus performante). Pour cela, on peut alors utiliser le **Doctrine Query Language** (ou `DQL`).

## Le Doctrine Query Language (DQL)

Le `DQL` est un langage qui *ressemble* au `SQL` et permet d'interroger la base de données (ou bien de faire des mises à jour/suppression de données). Doctrine va ensuite traduire cette requête en une requête compatible avec le `SGBD` utilisé (toutes les requêtes écrites en `DQL` sont donc portables, peu importe la base de données utilisée).

Les différences majeures entre le `SQL` et le `DQL` sont qu'on ne fait pas de requêtes sur des tables, mais sur les **entités** de l'application et qu'on utilise les **propriétés** de ces dernières dans les clauses `SELECT`, `WHERE`, `JOIN`, etc, au lieu de noms de colonnes.

Les jointures se font donc **entre les entités** : il faut donc qu'il existe une propriété liant les deux entités dans le type d'entité sélectionnée dans le `FROM` pour pouvoir faire une jointure.

Dans le cadre de requêtes de **sélection**, Doctrine convertira automatiquement le résultat de la requête en instances d'entités de l'application.

### Comment effectuer une requête

Imaginons l'ensemble d'entités suivant :

```php
namespace App\Entity;

class Film {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\ManyToOne]
    private ?Genre $genre = null;

    #[ORM\Column]
    private ?int $anneeSortie = null;

    /**
     * @var Collection<int, Acteur>
     */
    #[ORM\ManyToMany(targetEntity: Acteur::class)]
    private Collection $acteurs;

    /**
     * @var Collection<int, Film>
     */
    #[ORM\ManyToMany(targetEntity: Cinema::class, inversedBy: 'filmsDiffuses')]
    private Collection $cinemasDiffusion;
}
```

```php
namespace App\Entity;

class Genre {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;
}
```

```php
namespace App\Entity;

class Acteur {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $nbOscars = null;
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

Pour créer et exécuter une requête `DQL`, on utilise le service `EntityManagerInterface` (que nous utilisons déjà pour insérer, mettre à jour et supprimer une entité). La méthode la plus basique pour effectuer une requête est d'utiliser la fonction `createQuery` :

```php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class MonService {

    public function __construct(private EntityManagerInterface $entityManager) {}

    public function exemple() {
        //On veut récupérer tous les films sortis entre 2000 et 2025 (inclus).
        //On désigne l'entité par son nom complet (namespace + nom) : App\Entity\Film
        //anneeSortie est le nom d'un attribut de la classe Film
        $requete = $entityManager->createQuery('SELECT f FROM App\Entity\Film f WHERE f.anneeSortie >= 2000 AND f.anneeSortie <= 2025');
        //On récupère un tableau d'entités films
        $films = $requete->getResult();
    }
}
```

Même s'il est possible d'injecter `EntityManager` dans n'importe quel service, on préférera plutôt utiliser le `DQL` dans les **repositories** des entités en questions (où il est déjà intégré) :

```php
namespace App\Repository;

use App\Entity\Film;
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
    public function findFilmsSortisEntre(int $debut, int $fin) : array {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT f
            FROM App\Entity\Film f
            WHERE f.anneeSortie >= :debut AND f.anneeSortie <= :fin'
        );
        $query->setParameter('debut', $debut);
        $query->setParameter('fin', $fin);
        return $query->getResult();
    }
}
```

Dans le dernier exemple, on remarque qu'il est possible de **préparer** des requêtes et d'y injecter des paramètres (obligatoire pour éviter les **injections SQL**!).

### Query Builder

Une alternative à la méthode `createyQuery` de `EntityManager` est la méthode `createQueryBuilder` qui permet de créer une requête pas à pas via le design pattern créateur `Builder`. Cette méthode est très utile, notamment quand certaines parties de la requête sont dépendantes de certaines conditions. De plus, si on l'utilise dans un repository, on peut utiliser la méthode `$this->createQueryBuilder()` qui permettra de sélectionner directement à partir de l'entité liée au repository (donc, pour éviter de préciser la clause `FROM`...).

```php
namespace App\Repository;

use App\Entity\Film;
use App\Entity\Cinema;
use App\Entity\Ville;
use App\Entity\Genre;
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
    public function findFilmsSortisEntre(int $debut, int $fin) : array {
        // 'f' représente l'alias lié à l'entité Film
        $queryBuilder = $this->createQueryBuilder('f')
                             ->where('f.anneeSortie >= :debut')
                             ->andWhere('f.anneeSortie <= :fin')
                             ->setParameter('debut', $debut)
                             ->setParameter('fin', $fin);
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }

    /**
    * @return Film[]
    */
    public function findFilmsGenreAvecDesActeursOscarises(Genre $genre, int $nbOscarsMinimum, bool $trierParAnneeSortie) : array {
        $queryBuilder = $this->createQueryBuilder('f')
                             ->join('f.genre', 'g')
                             ->join('f.acteurs', 'a')
                             ->where('g.id >= :genre')
                             ->andWhere('a.nbOscars >= :nbOscars')
                             ->setParameter('genre', $genre->getId())
                             ->setParameter('nbOscars', $nbOscarsMinimum);
        if($trierParAnneeSortie) {
            $queryBuilder->orderBy('f.anneeSortie', 'DESC');
        }
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }

    /**
    * @return Film[]
    */
    public function findFilmsQuiPassentDansVille(Ville $ville) : array {
        $queryBuilder = $this->createQueryBuilder('f')
                        ->join('f.cinemasDiffusion', 'c')
                        ->join('c.ville', 'v')
                        ->where('v.id = :idVille')
                        ->setParameter('idVille', $ville->getId());
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }

    /**
    * @return Film[]
    */
    public function findFilmsDiffusesDansCinemas($nbCinemas) : array {
        $queryBuilder = $this->createQueryBuilder('f')
                        ->leftJoin('f.cinemasDiffusion', 'c')
                        ->groupBy('f.id')
                        ->having("COUNT(c.id) >= :nbCinemas")
                        ->setParameter('nbCinemas', $nbCinemas);
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }

    public function deleteVieuxFilms(int $annee) : void {
        $queryBuilder = $this->createQueryBuilder('f')
                        ->delete()
                        ->where("f.anneeSortie <= :annee")
                        ->setParameter('annee', $annee);
        $query = $queryBuilder->getQuery();
        //On utilise execute pour les suppressions/mises à jour
        $query->execute();
    }
}
```

### Utiliser du SQL natif

Il est aussi tout à fait possible d'utiliser du SQL natif pour réaliser les requêtes, mais :
* Elles ne seront pas nécessairement portables d'un SGBD à l'autre.
* Il faut expliquer à Doctrine comment **mapper** le résultat de la requête en objet.

```php
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
    public function findFilmsSortisEntre(int $debut, int $fin) : array {
        $rsm = new ResultSetMapping();

        //Données du film
        $rsm->addEntityResult(Film::class, 'f');
        $rsm->addFieldResult('f', 'id', 'id');
        $rsm->addFieldResult('f', 'titre', 'titre');
        $rsm->addFieldResult('f', 'annee_sortie', 'anneeSortie');

        //Genre du film (optionnel, seulement si on veut les données du genre)
        $rsm->addJoinedEntityResult(Genre::class, 'g', 'f', 'genre'); 
        $rsm->addFieldResult('g', 'genre_id', 'id');
        $rsm->addFieldResult('g', 'genre_nom', 'nom');

        //Acteurs du film (optionnel, seulement si on veut les acteurs du film)
        $rsm->addJoinedEntityResult(Acteur::class, 'a', 'f', 'acteurs');
        $rsm->addFieldResult('a', 'acteur_id', 'id');
        $rsm->addFieldResult('a', 'acteur_nom', 'nom');
        $rsm->addFieldResult('a', 'acteur_prenom', 'prenom');

        $sql =
        "SELECT f.id, f.titre, f.annee_sortie, g.id AS genre_id, g.nom AS genre_nom, a.id AS acteur_id, a.nom AS acteur_nom, a.prenom AS acteur_prenom
        FROM film f
        LEFT JOIN genre g ON f.genre_id = g.id
        LEFT JOIN film_acteur fa ON f.id = fa.film_id
        LEFT JOIN acteur a ON fa.acteur_id = a.id
        WHERE f.annee_sortie >= :debut AND f.annee_sortie <= :fin";

        
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('debut', $debut);
        $query->setParameter('fin', $fin);
        return $query->getResult();
    }
}
```

## Conclusion

Le `DQL` est donc un outil puissant qui permet d'exécuter des requêtes plus précises et plus complexes. Son utilisation peut permettre d'améliorer les performances de certains traitements tout en garantissant la portabilité de l'application.

Pour plus de détails, vous pouvez consulter la [documentation officielle](https://www.doctrine-project.org/projects/doctrine-orm/en/3.5/reference/dql-doctrine-query-language.html#doctrine-query-language) de Doctrine et notamment la section concernant le [QueryBuilder](https://www.doctrine-project.org/projects/doctrine-orm/en/3.5/reference/query-builder.html#the-querybuilder).

