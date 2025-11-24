---
title: API PLatform - Sécurité des sous-ressources
layout: tutorial
lang: fr
---

## Introduction

Pour rappel, dans le [TD4]({{site.baseurl}}/tutorials/tutorial4), nous avions vu qu'il "était possible d'utiliser le paramètre `security` au niveau d'une opération afin de contrôler les droits d'accès/du'itlisation de la route, notamment vis-à-vis **des données de l'objet ciblé** par la route.

Par exemple :

```php
#[ApiResource(
    operations: [
        new Get()
        new GetCollection()
        new Post(security: "is_granted('ROLE_JOURNALISTE')"),
        new Patch(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getOwner() == user)")
    ]
)]
class Article {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[ApiProperty(security: "is_granted('ROLE_USER')")]
    private ?string $contenu = null;

    #[ORM\Column]
    #[ApiProperty(securityPostDenormalize: "is_granted('ROLE_ADMIN')")]
    private ?bool $desactive = false;
}
```

Dans cet exemple :

* Tout le monde peut récupérer la liste des publications ou les détails d'un article, mais seuls les utilisateurs connectés pourront en voir le contenu.
* Seul un journaliste peut publier un article, mais seul un administrateur peut écrire la propriété "desactive".
* Seul un administrateur ou le propriétaire d'un article peut modifier l'article, mais seul un administrateur peut modifier la propriété "desactive".

Nous avions aussi vu qu'il est possible d'utiliser des **voters** de cettte manière :

```php
#[ApiResource(
    operations: [
        new Post(security: "is_granted('PERMISSION', object)"),
    ]
)]
```

Tout cela fonctionne bien sur des opérations portant sur des ressources/routes simples. Cependant, quand on commence à aborder des **sous-ressources** avec des routes plus complexes, la gestion de la sécurité est un peu plus complexe.

## Sécurité des sous-ressources

Une **sous-ressource** est une **ressource** accessible au travers d'une autre ressource via une route plus complexe.

Par exemple :
* Les publications d'un utilisateur (comme nous le faisons dans le [TD4]({{site.baseurl}}/tutorials/tutorial4)) : `/utilisateurs/{idUtilisateur}/publications` (One-To-Many).
* La ville dans laquelle est située une entreprise : `/entreprises/{idEntreprise}/ville` (Many-To-One).
* Les compétences d'une personne : `/personnes/{idPersonne}/competences` (Many-To-Many).
* Le casier d'un employé ou inversement : `/employes/{idEmploye}/casier` ou `/casier/{idCasier}/employe` (One-To-One)

Selon le contexte, il est souhaitable de réaliser certaines opérations de lecture/écriture directement au niveau de ces **sous-ressources** et il faut donc pouvoir les sécuriser. Le problème est que la route implique deux types d'entités différentes (celle "contenant" et celle "contenue" à laquelle on accède) et le paramètre `security` appliqué à une opération ne porte que sur **la sous-ressource** à laquelle on accède (donc, celle "contenue").

Pour que la vérification porte sur la ressource "parente", il ne faut alors pas placer le paramètre `security` au niveau de l'opération, mais au niveau de l'objet `Link` utilisé pour faire le lien entre la ressource parente et la sous-ressource.

Par exemple, imaginons la situation suivante :
* Un **article** est écrit par un **utilisateur**.
* Un **article** possède des **annotations** (notes privées postées par l'auteur).
* Un **article** possède des **tags** (privés).

```php
class Article
{

    // ...

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Utilisateur $auteur = null;

    /**
     * @var Collection<int, Annotation>
     */
    #[ORM\OneToMany(targetEntity: Annotation::class, mappedBy: 'article', cascade: ['persist'], orphanRemoval: true)]
    private Collection $annotations;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    public function getAuteur() : ?Utilisateur {
        return $this->auteur;
    }

}
```
On pourrait donc définir les routes suivantes afin de manipuler différentes sous-ressources :

* `/articles/{idArticle}/annotations` : pour lire les annotations privées de l'article.
* `/articles/{idArticle}/tags` : pour lire les tags de l'article.

Cependant, on voudrait sécuriser les routes manipulant les sous-ressources d'un article en s'assurant que l'utilisateur qui accède à la sous-ressource est bien le propriétaire de la ressource parente (dans notre cas, l'article).

Pour cela, il faut commencer par activer le paramètre : `enable_link_security` dans la configuration d'API Platform :

```yaml
#src/config/packages/api_platform.yaml
api_platform:
    ...
    enable_link_security: true
```

Ensuite, il faut procéder ainsi :

```php
#[ApiResource(
    uriTemplate: '/articles/{idArticle}/annotations',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'idArticle' => new Link(
            toProperty: 'article',
            fromClass: Article::class,
            //Ou bien, avec un voter : "is_granted('ARTICLE_VIEW', article)"
            security : "is_granted('ROLE_USER') and article.getAuteur() == user"
        )
    ]
)]
class Annotation
{
    //...
    #[ORM\ManyToOne(inversedBy: 'annotations')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Article $article = null;
}
```

Cependant, dans certains cas, il se peut que la sous-ressource ne contienne pas de référence vers la ressource parente (c'est souvent le cas dans certaines relations Many-To-Many). Il faut alors utiliser le paramètre `fromProperty` (au lieu de `toProperty`) pour indiquer la propriété à mapper dans la ressource parente. Mais dans ce cas, l'objet utilisé dans le paramètre `security` semble curieuse.

Par exemple, imagions que `Annotation` ne possède pas de référence vers `Article` :

```php
#[ApiResource(
    uriTemplate: '/articles/{idArticle}/annotations',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'idArticle' => new Link(
            fromProperty: 'annotations',
            fromClass: Article::class,
            security : "is_granted('ROLE_USER') and annotations.getAuteur() == user"
        )
    ]
)]
class Annotation
{
    //...
}
```

Il n'y a pas d'erreur de syntaxe au niveau de `annotations.getAuteur() == user` : le `annotations` représente en fait l'article ! Comme cela peut être très confusant, un autre paramètre `securityObjectName` permet de renommer ce paramètre pour l'utiliser dans `security` :

```php
#[ApiResource(
    uriTemplate: '/articles/{idArticle}/annotations',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'idArticle' => new Link(
            fromProperty: 'annotations',
            fromClass: Article::class,
            //On renomme 'annotations" en 'article' pour l'utiliser dans security
            securityObjectName: 'article'
            security : "is_granted('ROLE_USER') and article.getAuteur() == user"
        )
    ]
)]
class Annotation
{
    //...
}
```

Voyons un dernier exemple avec les **tags** où les tags sont communs à plusieurs articles et n'ont pas de références vers les articles qui contiennent les tags. Les tags sont "masqués" pour les utilisateurs normaux et seul l'auteur de l'article peut y accéder :

```php
#[ApiResource(
    uriTemplate: '/articles/{idArticle}/tags',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'idArticle' => new Link(
            fromProperty: 'tags',
            fromClass: Article::class,
            securityObjectName: 'article'
            security : "is_granted('ARTICLE_VIEW', article)"
        )
    ]
)]
class Tag
{
    //...
}
```