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

Pour créer et exécuter une requête `DQL`, on utilise le service `EntityManagerInterface` (que nous utilisons déjà pour insérer, mettre à jour et supprimer une entité). La méthode la plus basique pour effectuer une requête est d'utiliser la fonction `createQuery` :

```php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class MonService {

    public function __construct(private EntityManagerInterface $entityManager) {}

    public function exemple() {
        //On veut récupérer tous les films sortis entre 2000 et 2025 (inclus).
        //On désigne l'entité par son nom complet App\Entity\Film
        //anneePublication est le nom d'un attribut de la classe Film
        $requete = $entityManager->createQuery('SELECT f FROM App\Entity\Film f WHERE f.anneePublication >= 2000 AND f.anneePublication <= 2025');
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
            WHERE f.anneePublication >= :debut AND f.anneePublication <= :fin'
        )
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
                             ->andWhere('f.anneeSortie <= :fin');
                             ->setParameter('debut', $debut);
                             ->setParameter('fin', $fin);
        $query = $queryBuilder->getQuery();
        return $query->execute();
    }

    public function findFilmsGenreAvecDesActeursOscarises(string $genre, int $nbOscars, bool $trierParAnneeSortie) {
        $queryBuilder = $this->createQueryBuilder('f')
                             ->where('f.genre >= :genre')
                             ->join('f.acteurs', 'a');
                             ->where('a.nbOscars >= :nbOscars')
                             ->setParameter('genre', $genre);
                             ->setParameter('genre', $nbOscars);
        if($trierParDateSortie) {
            $queryBuilder->orderBy('f.anneeSortie', 'DESC');
        }
        $query = $queryBuilder->getQuery();
        return $query->execute();
    }

    public function findFilmsQuiPassentDansVille(Ville $ville) {
        //Film ne possède pas d'attributs "cinemasDiffusion", on doit donc à partir de "Cinema"
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                        ->select('f')
                        ->from(Cinema::class, 'c')
                        ->join('c.ville', 'v');
                        ->join('c.filmsDiffuses', 'f')
                        ->where('v.idVille = :idVille')
                        ->setParameter('idVille', $ville->getId());
        $query = $queryBuilder->getQuery();
        return $query->execute();
    }
}
```

### Utiliser du SQL pur

## Conclusion

