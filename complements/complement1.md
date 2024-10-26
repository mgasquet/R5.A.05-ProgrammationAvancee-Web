---
title: API PLatform - Utilisation d'identifiants simples à la place des IRIs
subtitle:
layout: tutorial
lang: fr
---

## Introduction

Dans le [TD4]({{site.baseurl}}/tutorials/tutorial4), nous avons utilisé **API Platform** afin de modéliser l'API de **The Feed**.

Pendant ce TD, vous avez découvert le concept d'**IRI** qui permet d'identifier une ressource par son **chemin interne** dans l'API.

Prenons l'exemple suivant :

* Une ville est modélisée par identifiant, d'un nom et d'un code postal.
* Un utilisateur est modélisé par un identifiant, un nom, un prénom et (éventuellement) une ville.

On obtient donc le **modèle E/A** suivant :

<div>
 ![modele e/a 1]({{site.baseurl}}/assets/complement1/ModeleEA1.PNG)
</div>

Ce qui donne ce **schéma relationnel** :

* **Utilisateur**(<u>id</u>, nom, prenom, #idVille)
* **Ville**(<u>id</u>, nom, codePostal)

Enfin, en conception, après certains choix, on obtiendrait alors le diagramme de classes suivant :

<div>
 ![diagramme de classes 1]({{site.baseurl}}/assets/complement1/DiagrammeClasses1.PNG)
</div>

Ce qui donnera les classes suivantes du côté de l'application :

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

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ApiResource]
class Utilisateur
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

    //Methodes...
}
```

On peut maintenant ajouter des **villes** puis définir une **ville** pour un utilisateur.

Par exemple :

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

Création d'un utilisateur qui habite à Nîmes :
```
POST https://localhost/api/utilisateurs
{
    "nom": "Smith",
    "prenom": "John",
    "ville": "/api/villes/1"
}
```

Renvoi :
```
{
    "@id": "/api/utilisateurs/1"
    "id": 1,
    "nom": "Smith",
    "prenom": "John",
    "ville": "/api/villes/1"
}
```

## Denormalizer

Pour pouvoir indiquer que l'utilisateur habite à Nîmes, il faut connaître l'**IRI** de la ville (ici `/api/villes/1`).

Si la notion d'**IRI** est très utile lors de la lecture de données (nous donne l'adresse exacte d'une ressource), il pourrait être souhaitable, dans certains cas, d'utiliser les identifiants simples (**plain identifiers**) à la place des **IRI** lors de l'utilisation des opérations `POST`, `PUT` et `PATCH`. Cela nous évite de devoir connaître exactement l'adresse interne de l'API lors des requêtes d'écritures.

Globalement, on aimerait pouvoir écrire cette requête :

```
POST https://localhost/api/utilisateurs
{
    "nom": "Smith",
    "prenom": "John",
    "ville": "1"
}
```

Pour pouvoir gérer cela, il faut écrire un `Denormalizer` qui va permettre de convertir (en interne) l'identifiant passé dans le **payload** en **IRI**.

Ces classes de **dénormalisation** sont placées dans le dossier `src/Serializer`. Il faut en coder une pour chaque entité où l'on souhaite utiliser ce système.

Dans notre cas, nous allons donc en coder une pour notre entité `Utilisateur` :

```php
namespace App\Serializer;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

class UtilisateurDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    //On injecte le service qui permet de convertir un id en IRI.
    public function __construct(private IriConverterInterface $iriConverter)
    {}

    //On indique dans cette méthode quel sont les objets gérés par notre denormalizer
    public function getSupportedTypes(?string $format): array
    {
        return [
            //Paramètres obligatoires
            'object' => null,
            '*' => false,
            //On indique que cette classe permet seulement de gérer la dénormalisation d'un utilisateur
            Utilisateur::class => true
        ];
    }

    /* 
    Cette méthode sert à déterminer si notre dénormaliseur doit être appliqué ou non :
    
    - Il faut que le format de la requête soit json ou jsonld (on peut éventuellement ne pas préciser cette condition)
    - Il faut que le type de l'entité soit Utilisateur
    - Il faut que la ville soit présente dans le payload
    */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return \in_array($format, ['json', 'jsonld'], true) && 
                is_a($type, Utilisateur::class, true) && 
                !empty($data['ville']) && 
                !isset($context[__CLASS__]);
    }

    //Cette méthode convertit l'identifiant simple en IRI puis reprend le processus normal de dénormalisation
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        //Convertir le champ $data["ville"] contenant un id simple en IRI
        //On indique bien quelle est l'entité visée (ici, Ville)
        $data['ville'] = $this->iriConverter->getIriFromResource(resource: Ville::class, context: ['uri_variables' => ['id' => $data['ville']]]);
        
        //On reprend le processus de dénormalisation
        return $this->denormalizer->denormalize($data, $class, $format, $context + [__CLASS__ => true]);
    }
}
```

Une fois cette classe codée, il n'y a rien d'autre à faire. Symfony prend en charge l'**autowiring** de ce service.

Si la classe est correctement codée, il devient alors possible d'exécuter la requête suivante sans problème :

```
POST https://localhost/api/utilisateurs
{
    "nom": "Smith",
    "prenom": "John",
    "ville": "1"
}
```

En interne, le `1` contenu dans le **payload** sera converti en `/api/villes/1`.

## Conclusion

Si vous souhaitez utiliser des identifiants simples lors des requêtes d'écriture d'une ressource, cela est donc tout à fait possible. Il suffit d'écrire le **dernomalizer** correspondant. Si l'entité contient plusieurs références vers d'autres entités (et que vous souhaitez aussi utiliser des identifiants simples pour référencer ces entités), il faudra gérer cela dans le même **denormalizer**.
