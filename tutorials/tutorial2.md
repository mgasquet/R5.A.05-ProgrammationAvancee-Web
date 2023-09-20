---
title: TD2 &ndash; Découverte du framework Symfony 2/2
subtitle: Gestion des utilisateurs et finitions
layout: tutorial
lang: fr
---

## Introduction

La seconde partie de la découverte de ce framework va se concentrer sur la gestion de nos utilisateurs. Comme c'est un aspect assez courant sur les applications web, Symfony possède bien sûr divers outils et processus permettant de mettre en place rapidement et facilement un système de gestion pour nos utilisateurs.

Enfin, dans une seconde partie, nous effectuerons quelques finitions sur le site comme ajouter des auteurs pour les publications et des pages personnelles pour les utilisateurs. Nous verrons aussi comment **inclure** d'autres templates dans un template Twig et enfin, nous personnaliserons nos pages d'erreur.

## Barre de débogage

Vous avez sans doute remarqué une barre d'outil s'affichant sur chaque page de votre application (si elle ne s'affiche pas, il faut cliquer sur le logo Symfony, en bas à droite). Cette barre s'affiche car nous sommes dans un environnement de développement (nous en reparlerons plus tard). Cette barre est très utile car elle nous fournit beaucoup d'informations :

* Temps d'exécution de la requête.
* Trace : on peut mesurer le temps passé dans chaque fichier, dans chaque fonction, dans la base de données.
* Requêtes SQL exécutées : code, temps d'exécution, nombre de requêtes
* Les données de l'utilisateur connecté.
* Les erreurs, les warnings...
* Code de réponse HTTP.

Bref, cet outil nous permet de connaître en détail l'état de notre application. Prenez le temps de l'explorer !

## Les utilisateurs

Il est temps d'ajouter des utilisateurs à notre site. Avec un framework, cette étape est généralement assez simplifiée et partiellement automatisée.

### Création de l'entité

Dans Symfony, il existe une commande interactive pour initier un système de gestion d'utilisateurs qui va générer la base des classes dont nous aurons besoin et va également configurer différentes choses, comme l'algorithme de hachage des mots de passes.

Cette commande se nomme `make:user` :

```bash
php bin/console make:user
```

Une fois exécutée, elle va vous demander :

* Le nom de l'entité qui jouera le rôle d'utilisateur.

* Si les informations de l'utilisateur doivent être stockées dans la base de données (login, mot de passe haché, etc...).

* Le nom de la propriété (unique) identifiant de l'utilisateur, c'est-à-dire, avec laquelle il se connectera (quelque chose supposé unique par utilisateur, par exemple, son login ou bien son adresse email...)

* Si les mots de passes doivent être hachés et vérifiés (a priori, on répond oui sauf si on a un autre système que Symfony qui gère cela).

Une fois complétée, la commande va générer une classe pour l'entité et une autre pour son repository puis va mettre à jour le fichier `config/packages/security.yaml`.

L'entité générée est vraiment basique et contient le strict nécessaire (id, propriété unique pour la connexion, mot de passe **haché** et rôles) mais il est tout à fait possible de la compléter en utilisant la commande `make:entity`

<div class="exercise">

1. À l'aide de la commande `make:user`, initiez le système de gestion d'utilisateurs, avec les contraintes suivantes :

    * Nom de l'entité : `Utilisateur`.
    * Stockage des informations dans la base.
    * Nom de la propriété unique : `login`.
    * Les mots de passes sont hachés et vérifiés.

2. Utilisez la commande `make:entity` pour mettre à jour et compléter la classe `Utilisateur` avec les champs suivants :

    * `adresseEmail` : string, 255 caractères maximum, non null.
    * `nomPhotoProfil` : text, null autorisé.

3. Afin de rendre l'adresse email **unique**, ajoutez le paramètre `unique: true` au niveau de l'annotation sur la propriété `adresseEmail` dans la classe `Utilisateur`.

4. Prenez le temps d'observer le code des classes générées ainsi que le fichier `security.yaml`.

5. Mettez à jour la structure de la base de données avec les commandes `make:migration` puis `doctrine:migrations:migrate`. Allez observer votre base de données pour constater la présence de la nouvelle table.

</div>

À ce stade, tout est prêt pour gérer nos utilisateurs. Il n'y a plus qu'à gérer la phase d'inscription et de connexion. Mais revenons d'abord sur certains éléments importants qui ont été générés.

Au niveau de la classe `Utilisateur` :

* La propriété `password` représente le mot de passe **haché** (on ne stocke jamais le mot de passe en clair ici)

* La propriété `roles` représente une liste de rôles de l'utilisateur. Les rôles sont un système permettant d'accorder des privilèges à certains utilisateurs. On peut limiter l'accès à des routes à certains rôles, ou bien vérifier le rôle d'un utilisateur dans un template Twig. Si vous jetez un œil à la méthode `getRoles`, vous remarquerez que par défaut, un utilisateur a le rôle `ROLE_USER`. C'est le rôle basique d'un utilisateur connecté. Dans la base de données, cette valeur est stockée comme un string et décodée puis transformée en tableau par Symfony.

* La méthode `eraseCredentials` permet d'effacer de la mémoire de Symfony des données sensibles, éventuellement stockées dans la classe de l'utilisateur, temporairement (comme le mot de passe en clair, pour la connexion). Dans notre cas, nous ne nous en servirons pas.

* La classe implémente `UserInterface` qui est demandée comme paramètre de nombreux services (comme pour le chiffrement du mot de passe). Notre classe `Utilisateur` sera donc compatible.

Au niveau du fichier `security.yaml` :

* La zone `app_user_provider` permet d'informer Symfony quelle est l'entité qui représente nos utilisateurs ainsi que la propriété utilisée comme identifiant de connexion.

* Un peu plus bas, `password_hashers` permet de sélectionner l'algorithme de chiffrement des mots de passes. Depuis les dernières versions de Symfony, on peut utiliser la valeur `auto` (comme c'est le cas ici) qui permet de sélectionner **le meilleur algorithme de chiffrement disponible**. Cela permet aux mots de passes d'être le plus sécurisé possible. De plus, si cet algorithme vient à changer (par exemple, un meilleur algorithme est publié dans le futur), Symfony procède à la **migration** des mots de passes. La prochaine fois qu'ils se connecteront, les utilisateurs dont le mot de passe utilise encore l'ancien algorithme de chiffrement déclencheront automatiquement la migration de leur mot de passe qui sera re-chiffré avec le nouvel algorithme puis stocké, et tout cela de manière invisible. Ainsi, avec ce paramètre, le développeur n'a pas (trop) à se soucier d'être à jour niveau sécurité des mots de passes. Les algorithmes de chiffrement contiennent un système de `salt`, comme vous l'avez vu l'année dernière.
 
### Formulaire d'inscription

#### Création du formulaire

Nous allons maintenant mettre en place un formulaire d'inscription pour nos utilisateurs !

À la différence du formulaire que nous avons créé pour les publications, celui-ci contiendra deux champs qui ne sont pas lié directement à la classe Utilisateur :

* `plainPassword`: il s'agit du mot de passe **en clair** transmis via le formulaire, qui diffère de l'attribut `password` qui lui représente le mot de passe chiffré et ne doit justement pas faire partie du formulaire ! Cela signifie que pour les **assertions** concernant `plainPassword`, il faudra le faire au niveau de la classe du formulaire, et non pas au niveau de l'entité `Utilisateur`.

* `fichierPhotoProfil` : il s'agit du **fichier** contenant la photo de profil de l'utilisateur. Cela est différent de `nomPhotoProfil` qui ne doit pas faire partie du formulaire et qui stocke seulement le nom de la photo de profil (pour l'afficher plus tard).

Vous aurez aussi besoin de nouvelles **assertions** :

* `#[Assert\Email()]` : vérifie que la chaîne de caractères est une adresse email valide (bien formée).

* `#[Assert\File(maxSize : ..., extensions : [...])]` : vérifie que le fichier envoyé ne dépasse pas une certaine taille et possède une des extensions autorisées (par exemple, "pdf"). Pour exprimer une taille en mégaoctets, on utilise `M`.

  Exemple d'une assertion `File` qui n'accepte que les fichiers de type mp3, wav ou ogg de 2Mo maximum :
  ```php
  #[Assert\File(maxSize : '2M', extensions : ['mp3', 'wav', 'ogg'])]
  ```

* `#[Assert\Regex(pattern: ...)]` : que nous avions brièvement présenté plus tôt. Le paramètre `pattern` défini l'expression régulière que la chaîne de caractères doit respecter.


Pour rappel, pour ajouter un champ qui ne fait par partie de l'entité (et lui ajouter des assertions) on le configure ainsi dans la classe du formulaire :

```php
    $builder
        //Champ qui n'est pas lié à l'entité : on rajoute l'option "mapped => false"
        ->add('monChamp', TextType::class, [
            "mapped" => false,
            //Les assertions
            "constraints" => [
                new NotBlank(),
                new NotNull(),
                new Length(...)
            ]
        ])
    ;
```

L'exemple d'assertion `File` donné plus tôt se transformerait ainsi dans le tableau du champ `constraints` :
```php
new File(maxSize : '2M', extensions : ['mp3', 'wav', 'ogg'])
```

Vous l'aurez remarqué, nous utilisons la syntaxe des [arguments nommés](https://www.php.net/manual/fr/functions.arguments.php#functions.named-arguments) que nous avions évoqués lors du premier TD lors de l'introduction des attributs.

Ensuite, au niveau de la classe `Utilisateur`, nous pouvons utiliser un attribut 
```php
#[UniqueEntity(propriete)]
``` 
Cet attribut se place juste au-dessus du nom de la classe et permet de signifier à l'application qu'une valeur d'une propriété de la classe est **unique** (pas de doublons entre les utilisateurs pour cet attribut, comme le "unique" en base de données). Encore une fois, cela peut paraître redondant avec la base de données, mais cela permet de détecter cette erreur plus tôt, au niveau de l'application, et ainsi de la gérer par nous-même plutôt qu'obtenir une page d'erreur que l'utilisateur n'est pas censé voir.

Par exemple :

```php
#[UniqueEntity('champ1', message : "Cette valeur est déjà prise!")]
#[UniqueEntity('champ3')]
class Exemple {

    #[ORM\Column(unique:true)]
    private ?string $champ1 = null;

    #[ORM\Column]
    private ?string $champ2 = null;

    #[ORM\Column(unique:true)]
    private ?string $champ3 = null;

}
```

Pour bien que vous compreniez la différence, le paramètre `unique` de l'attribut `#[ORM\Column(..., unique : true)]` va créer une contrainte **UNIQUE INDEX** au niveau de la base de données tandis que `#[UniqueEntity]` est similaire aux autres **assertions** et est vérifié lors de l'appel à la méthode `isValid` du formulaire. Avec `#[UniqueEntity]`, la vérification est faite avant tout enregistrement et on est ainsi sûr de ne pas exécuter une requête d'insertion qui produira une erreur (pour cause de doublons).

Concernant notre formulaire, contrairement à celui de la page principale,
celui-ci va contenir des balises `<label>`. Pour rappel, une balise `<label>`
est censée afficher le "nom" d'un champ. Quand on clique sur le label, le
"focus" (curseur) est déplacé dans l'input visé à condition d'avoir spécifié
l'id de l'input dans l'attribut `for`.

Avec Symfony, on peut générer le `<label>` lié à un champ avec {% raw %}`{{ form_label(form.champ, 'Mon label') }}`{% endraw %}. Cela est pratique dans le cas où nous n'avons pas besoin d'attribuer un `id` spécifique pour notre champ généré avec `form_widget`. Symfony le génèrera automatiquement et le label pointera automatiquement vers le bon `id` du champ visé.

<div class="exercise">

1. À l'aide de la commande `make:form` créez une classe de formulaire `UtilisateurType` pour l'entité `Utilisateur`. Dans cette nouvelle classe, supprimez les champs `password` et `nomPhotoProfil` et `roles` (qui ne sont pas envoyées et gérés par l'utilisateur) puis ajoutez deux nouveaux champs : `plainPassword` et `fichierPhotoProfil`.

2. Configurez le type des champs ainsi :

    * `login` : TextType
    * `adresseEmail` : EmailType
    * `plainPassword` : PasswordType
    * `fichierPhotoProfil` : FileType
    * `inscription` : SubmitType (bouton d'envoi)

    Quelques imports utiles à faire dans `UtilisateurType` :

    ```php
    use Symfony\Component\Form\Extension\Core\Type\FileType;
    use Symfony\Component\Form\Extension\Core\Type\PasswordType;
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    ```

3. Ajoutez différentes assertions (et attributs) pour implémenter les contraintes suivantes :

    1. Au niveau de la classe `Utilisateur` :

        * `login` : non blanc, non null, entre 4 et 20 caractères. Configurez aussi des messages d'erreurs (si login trop court ou trop long)

        * `adresseEmail` : non blanc, non null, adresse email valide. Configurez un message d'erreur en cas d'adresse non valide (paramètre `message`).

        * `login` et `adresseEmail` doivent être des propriétés à valeurs uniques. Spécifiez un message d'erreur si ces contraintes ne sont pas respectées.

        Classes à importer :

        ```php
        use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
        use Symfony\Component\Validator\Constraints as Assert;
        ```

    2. Au niveau de `UtilisateurType` :

        * `plainPassword` : non blanc, non null, entre 8 et 30 caractères, et doit respecter l'expression régulière (**regex**) suivante : `#^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,30}$#` (au moins une minuscule, une majuscule et un chiffre). Configurez des messages d'erreurs pour la taille du mot de passe et aussi si l'expression régulière n'est pas validée (juste `message`). Il faut aussi configurer l'option `mapped` pour préciser que ce champ ne fait pas partie de la classe `Utilisateur`.

        * `fichierPhotoProfil` : taille maximum **10 mégaoctets**, formats autorisés : **jpg**, et **png**. Configurez des messages d'erreurs dans le cas où la taille n'est pas respectée (`maxSizeMessage`) ou que le format n'est pas respecté (`extensionsMessage`). Ici aussi, il faut configurer l'option `mapped`.

        Classes à importer :

        ```php
        use Symfony\Component\Validator\Constraints\File;
        use Symfony\Component\Validator\Constraints\Length;
        use Symfony\Component\Validator\Constraints\NotBlank;
        use Symfony\Component\Validator\Constraints\NotNull;
        use Symfony\Component\Validator\Constraints\Regex;
        ```

4. Dans le dossier `templates`, créez un dossier `utilisateur` puis, à l'intérieur de ce nouveau répertoire, un template nommé `inscription.html.twig` :

    * Comme toutes nos futures pages, ce template doit étendre le template `base.html.twig`.

    * Le titre de la page doit être `Inscription`.

5. À l'aide de la commande `make:controller`, créez un nouveau contrôleur `UtilisateurController`, effacez le code par défaut et le template `index.html.twig` généré dans le dossier `utilisateur`.

6. Créez une route nommée `inscription`, ayant pour chemin `/inscription` et accessible avec les méthodes `GET` et `POST`. Dans le code de cette route, initialisez un formulaire avec `UtilisateurType`. Le formulaire utilisera la méthode `POST` et son action pointe vers la route `inscription`. Renvoyez une réponse générant une page avec le template créé à l'étape 4, en passant le formulaire en paramètre (n'hésitez pas à vous inspirer du code de la route `feed`). Comme pour les publications, nous utiliserons la même route pour afficher (GET) et traiter (POST) le formulaire.

7. Dans votre template, redéfinissez le bloc de contenu en incluant et en complétant le squelette suivant afin d'afficher le formulaire :

    ```twig
    {% raw %}
    <main>
        {{ form_start(..., {'attr': {'class': 'center basic-form'}}) }}
            <fieldset>
                <legend>Inscription</legend>
                <div class="access-container">
                    <!-- Affichage du label "Login" -->
                    {{ form_label(...) }}
                    <p class="help-input-form">Entre 4 et 20 caractères</p>
                    <!-- Affichage de l'input du login -->
                    {{ form_widget(...) }}
                </div>
                <div class="access-container">
                    <!-- Affichage du label "Mot de passe" -->
                    {{ form_label(...) }}
                <p class="help-input-form">Entre 8 et 30 caractères, au moins une minuscule, une majuscule et un nombre</p>
                    <!-- Affichage de l'input du mot de passe -->
                    {{ form_widget(...) }}
                </div>
                <div class="access-container">
                    <!-- Affichage du label "Adresse mail" -->
                    {{ form_label(...) }}
                    <!-- Affichage de l'input de l'adresse email -->
                    {{ form_widget(...) }}
                </div>
                <div class="access-container">
                    <!-- Affichage du label "Photo de profil" -->
                    {{ form_label(...) }}
                    <!-- Affichage de l'input de la photo de profil (required : '', car non obligatoire...) -->
                {{ form_widget(..., {'required' : '', 'attr' : {'accept' : 'image/png, image/jpeg'}}) }}
                </div>
                <!-- Affichage du bouton d'envoi, contenant le texte (label) "S'inscrire" -->
                {{ form_widget(..., {..., 'attr': {'class': 'basic-form-submit'}}) }}
            </fieldset>
            {{ form_rest(...) }}
        {{ form_end(...) }}
    </main>
    {% endraw %}
    ```

8. Accédez à votre nouvelle page et vérifiez que le formulaire s'affiche correctement.

9. Modifiez `base.html.twig` afin d'inclure un lien (généré) vers votre nouvelle page d'inscription dans le menu de navigation.
</div>

#### Traitement du formulaire d'inscription

Maintenant que nous pouvons afficher notre formulaire d'inscription, il faut pouvoir le traiter ! Mais ce n'est pas aussi simple que pour les publications, car :

* Il faut chiffrer/hacher le mot de passe.

* Il faut sauvegarder la photo de profil (s'il y en a une) et enregistrer le nom de la photo dans les données de l'utilisateur. Il faut faire en sorte que le nom de cette image soit unique.

Pour gérer ces deux aspects, nous nous proposons de créer un service, pour ne pas surcharger le contrôleur (et de toute façon, ce n'est pas vraiment son rôle de faire ces étapes, normalement...)

Dans notre nouveau service, nous allons utiliser certaines fonctions de l'objet de type `File` pour nous permettre de facilement déplacer le fichier uploadé par l'utilisateur dans notre système :

```php
// $fichier est le fichier uploadé
// $destination est le dossier vers lequel le fichier sera déplacé
$fileName = uniqid() . '.' . $fichier->guessExtension();
$fichier->move($destination, $fileName);
```

L'utilisation de `uniqueid` permet de générer une chaîne aléatoire (presque garantie unique). Ainsi, on devrait obtenir un nom de fichier unique, pour l'image de profil de l'utilisateur. La méthode `guessExtension` permet d'obtenir l'extension du fichier (png, jpg...). Enfin, `move` déplace le fichier vers un dossier de destination.

Ensuite, le service `UserPasswordHasherInterface` permet de **hacher/chiffrer** un mot de passe, en utilisant l'algorithme configuré dans `security.yaml` (dans notre cas `auto`, donc, le meilleur algorithme de chiffrement disponible).

```php
// $this->passwordHasher est de type UserPasswordHasherInterface
// $utilisateur est de type UserInterface (classe implémentée par notre entité Utilisateur)
$hashed = $this->passwordHasher->hashPassword($utilisateur, $plainPassword);
$utilisateur->setPassword($hashed);
```

Concernant le **dossier de destination** de l'image, c'est aussi un paramètre que nous allons définir hors de notre classe et qui pourra être injecté automatiquement. La syntaxe diffère un peu dans ce cas.

Tout d'abord, il faut se rendre dans `config/services.yaml` et définir le paramètre souhaité dans la section `parameters` :

```yaml
parameters:
    exemple_param: 'coucou!'
```

Puis, quand on souhaite l'injecter dans notre service, comme pour les autres injections, cela se passe dans le constructeur, mais cette-fois, en utilisant l'attribut `#[Autowire('%nom_param%')]`. Par exemple :

```php
class ExempleService {
    // Les `%` autour du nom du paramètre sont importants!
    public function __construct(
        #[Autowire('%exemple_param%')] private string $exempleParam,
        private AutreServiceInterface $autreService
    ) {}
}
```

Enfin, dans le contrôleur, vous aurez besoin d'aller chercher les champs `plainPassword` et `fichierPhotoProfil` dans l'objet du formulaire, pour les transmettre à votre service. Pour cela, vous pouvez utiliser la méthode `getData` comme dans l'exemple qui suit :

```php
$valeurChamp = $form["monChamp"]->getData();
```

<div class="exercise">

1. Dans le fichier `config/services.yaml`, ajoutez un paramètre `dossier_photo_profil` ayant pour valeur : `'%kernel.project_dir%/public/img/utilisateurs/uploads'`. La partie `%kernel.project_dir%` désigne la racine du projet. C'est un paramètre défini par Symfony (notez qu'en utilisant `%` on peut utiliser la valeur d'autres paramètres pour construire un autre paramètre, comme c'est le cas ici.).

2. Dans le dossier `src/Service`, créez et complétez la classe suivante :

    ```php
    namespace App\Service;

    use App\Entity\Utilisateur;
    use Symfony\Component\DependencyInjection\Attribute\Autowire;
    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    class UtilisateurManager {

        public function __construct(
            //Injection du paramètre dossier_photo_profil
            //Injection du service UserPasswordHasherInterface
        ){}

        /**
         * Chiffre le mot de passe puis l'affecte au champ correspondant dans la classe de l'utilisateur
         */
        private function chiffrerMotDePasse(Utilisateur $utilisateur, ?string $plainPassword) : void {
            //On chiffre le mot de passe en clair
            //On met à jour l'attribut "password" de l'utilisateur
        }

        /**
         * Sauvegarde l'image de profil dans le dossier de destination puis affecte son nom au champ correspondant dans la classe de l'utilisateur
         */
        private function sauvegarderPhotoProfil(Utilisateur $utilisateur, ?UploadedFile $fichierPhotoProfil) : void {
            if($fichierPhotoProfil != null) {
                //On configure le nom de l'image à sauvegarder
                //On la déplace vers son dossier de destination
                //On met à jour l'attribut "nomPhotoProfil" de l'utilisateur
            }
        }

        /**
         * Réalise toutes les opérations nécessaires avant l'enregistrement en base d'un nouvel utilisateur, après soumissions du formulaire (hachage du mot de passe, sauvegarde de la photo de profil...)
         */
        public function processNewUtilisateur(Utilisateur $utilisateur, ?string $plainPassword, ?UploadedFile $fichierPhotoProfil) : void {
            //On chiffre le mot de passe
            //On sauvegarde (et on déplace) l'image de profil
        }

    }
    ```
3. Comme nous l'avions fait pour `FlashMessageHelper`, définissez une interface `UtilisateurManagerInterface` contenant la signature de `processNewUtilisateur` (on rappelle qu'il est très facile d'extraire une interface depuis une classe concrète avec **PHPStorm** !). Ensuite, faites le nécessaire pour que `UtilisateurManager` implémente cette interface puis mettez à jour le fichier `services.yaml` pour qu'on puisse injecter et utiliser le service `UtilisateurManager` directement avec `UtilisateurManagerInterface`.

4. Dans votre route `inscription`, faites en sorte de gérer la soumission du formulaire et de sauvegarder l'utilisateur construit à partir du formulaire dans la base de données. Cependant, **avant de sauvegarder l'utilisateur**, il faudra extraire `plainPassword` puis `fichierPhotoProfil` et enfin utiliser votre nouveau service avec sa méthode `processNewUtilisateur`.

    N'oubliez pas aussi de prendre en charge les erreurs du formulaire, à sauvegarder comme messages flash ! 
    
    Enfin, lorsque l'utilisateur est enregistré, il faut ajouter un message flash (type : `success`) "Inscription réussie" (on rappelle qu'il est possible d'utiliser `addFlash` dans un contrôleur) puis le rediriger vers la route `feed` (méthode `redirectToRoute` qui retourne un objet `Response`). 
    
    Encore une fois, vous pouvez vous inspirer (en partie) du code de votre route `feed`, pour la création d'une publication.

5. Testez d'inscrire un utilisateur en respectant les différentes contraintes et en précisant une image de profil. Vérifiez que :

    * Vous êtes bien redirigé vers la page principale et le message flash "Inscription réussie" apparaît.

    * L'utilisateur est enregistré dans la base de données et aucun champ n'est null.

    * L'image de profil a bien été uploadée dans le dossier `public/img/utilisateurs/uploads`.

6. Tentez d'inscrire un nouvel utilisateur, sans image de profil (cela doit fonctionner).

7. Testez les différents cas d'erreurs possibles en ne respectant pas certaines contraintes (sur le login, le mot de passe, le format de l'image de profil...) et vérifiez que les messages flash d'erreur s'affichent bien.

8. En modifiant `UtilisateurType`, ajoutez des contraintes "client" qui permettront de générer des attributs HTML sur les balises du formulaire pour que le navigateur vérifie certaines contraintes côté client :

    * `minlength` et `maxlength` sur le login et le mot de passe.

    * `pattern` sur le mot de passe avec pour valeur : `^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)[a-zA-Z\\d]{8,30}$` (on utilise `\\d` au lieu de `\d`, car autrement, `\` est interprété comme un caractère spécial)

    * Vous pouvez également déplacer les contraintes déjà présentes dans le code HTML correspondant au champ `fichierPhotoProfil` dans le champ dédié dans classe `UtilisateurType`. Attention, pour `required`, il se place en dehors de `attr` :

    ```php
    $builder
        ->add('champ1', MonType::class, [
            //Champ non requis, génère un required='' dans le champ
            'required' => false,
            'attr' => [
                ...
            ]
        ])
    ;
    ```
</div>

### Connexion et déconnexion

#### Formulaire de connexion

Concernant la **connexion**, nous n'aurons pas à créer de classe pour le formulaire. En fait, il y a juste besoin de créer un formulaire HTML classique en précisant des valeurs précises pour les champs correspondants au login et au mot de passe. Aussi, il n'y aura pas besoin de gérer explicitement le traitement du formulaire, Symfony se charge de vérifier le mot de passe et de récupérer les informations sur l'utilisateur.

Tout d'abord, on commence par créer une route, accès en `GET` et en `POST` :

```php
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/exempleConnexion', name: 'exempleConnexion', methods: ['GET', 'POST'])]
public function connexion(AuthenticationUtils $authenticationUtils) : Response {
    $lastUsername = $authenticationUtils->getLastUsername();
    return $this->render('monFormulaireDeConnexion.html.twig');
}
```

* La variable `$lastUsername` permet de conserver l'affichage du login dans le cas où le formulaire est réaffiché si les identifiants sont invalides.

Ensuite, on crée le template `Twig` correspondant, contenant un formulaire de connexion :

```twig
{% raw %}
<form action="{{ path('exempleConnexion') }}" method="post">
    <input type="text" name="_username" required/>
    <input type="password" name="_password" required/>
    <button type="submit">Connexion</button>
    <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
</form>
{% endraw %}
```

* En cas d'échec de connexion, on réaffiche le nom de l'utilisateur avec {% raw %}`{{ last_username }}`{% endraw %} (passé en argument par notre contrôleur).

* La valeur de l'attribut `name` du champ de login doit être `_username`.

* La valeur de l'attribut `name` du champ de mot de passe doit être `_password`.

* Ce formulaire doit aussi contenir un **jeton CSRF** (protection contre le cross-site request forgery), dans un input de type `hidden` (donc caché), nommé `_csrf_token` dont la valeur est générée avec {% raw %}`{{ csrf_token('authenticate') }}`{% endraw %}.

Enfin, il ne reste plus qu'à éditer le fichier `config/packages/security.yaml` :

```yaml
security:
    ...
    firewalls:
       ...
        main:
            ...

            form_login:
                login_path: exempleConnexion
                check_path: exempleConnexion
                default_target_path: maRoute
                always_use_default_target_path: true
                enable_csrf: true
```

* Le système de sécurité de Symfony redirige les visiteurs non authentifiés vers la route indiquée dans `login_path` lorsqu'ils tentent d'accéder à un page sécurisé sans être connecté.
  
* Le paramètre `check_path` doit correspondre à la route vers laquelle renvoie le formulaire de connexion. Symfony va intercepter les requêtes `POST` à `check_path` pour traiter les identifiants de connexion. En cas d'échec de connexion, Symfony redirige l'utilisateur sur `login_path`, ce qui a pour effet de réafficher le formulaire de connexion.

* Les paramètres `default_target_path` et `always_use_default_target_path` permettent de rediriger l'utilisateur vers une route précise après une connexion réussie.

<div class="exercise">

1. Dans `UtilisateurController`, créez une route `connexion` avec pour chemin `/connexion`, similaire à l'exemple donné plus tôt. Le template à préciser est `utilisateur/connexion.html.twig`. Nous allons le créer à la prochaine étape.

2. Créez le template `connexion.html.twig` dans le dossier `templates/utilisateur`. Comme toutes nos pages, ce template étend `base.html.twig` et récrit certains blocks. La page doit avoir pour titre "Connexion".

3. Concernant le **contenu de la page**, importez et complétez le code du formulaire suivant : 

    ```html
    <main>
        <form action="...A compléter..." method="post" class="basic-form center">
            <fieldset>
                <legend>Connexion</legend>
                <div class="access-container">
                    <label for="login">Login</label>
                    <input id="login" type="text" name="...A compléter..." required/>
                </div>
                <div class="access-container">
                    <label for="password">Mot de passe</label>
                    <input id="password" type="password" name="...A compléter..." required/>
                </div>
                <button type="submit" class="basic-form-submit">Se connecter</button>
            </fieldset>
            <!-- A compléter : champ caché contenant le jeton CSRF -->
        </form>
    </main>
    ```
4. Mettez à jour le fichier `config/packages/security.yaml` de manière adéquate. L'utilisateur doit être redirigé vers la route `feed` après s'être connecté.

5. Mettez à jour le template `base.html.twig` afin d'inclure un lien vers votre page de connexion dans le menu de navigation.

6. Accédez à votre page de connexion et tentez de vous connecter avec un compte existant, mais avec un mauvais mot de passe. Normalement, vous devriez rester sur le formulaire. Essayez ensuite avec un bon mot de passe, vous devriez alors être redirigé vers la page principale (et votre pseudonyme devrait apparaître dans la barre de débbogage). Nous allons gérer les différents messages informatifs plus tard.

7. Actuellement, si l'utilisateur se trompe dans son mot de passe, quand le formulaire est rechargé, le champ du login n'est pas pré-rempli. Il est possible d'améliorer cet aspect en récupérant le **dernier login avec lequel l'utilisateur a tenté de se connecter**. Pour cela, on utilise le service `AuthenticationUtils` :

    ```php
    use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

    $lastUsername = $authenticationUtils->getLastUsername();
    ```

    Il est alors possible de simplement passer cette donnée au template et de l'utiliser pour préciser l'attribut `value` du champ correspondant au login. Ce champ sera donc tout le temps pré-remplit, ce qui est pratique en cas d'erreur de mot de passe, mais aussi si l'utilisateur se déconnecte puis se reconnecte plus tard. Cette donnée est mémorisée dans un **cookie**.

    Effectuez les modifications nécessaires dans votre route `connexion` et dans le template `connexion.html.twig` pour que le champ du login soit automatiquement pré-remplit avec le dernier login avec lequel l'utilisateur a essayé de se connecter.

</div>

#### Déconnexion

Maintenant, nous devons gérer la **déconnexion**. Cela est encore plus simple, car il n'y a même pas de méthode de route ou de formulaire à créer.

Tout d'abord, on déclare une route **vide** dans notre contrôleur :

```php
#[Route('/maRouteDeconnexion', name: 'routeDeconnexion', methods: ['POST'])]
public function routeDeconnexion(): never
{
    //Ne sera jamais appelée
    throw new \Exception("Cette route n'est pas censée être appelée. Vérifiez security.yaml");
}
```

Cette route ne sera jamais appelée (type de retour spécial dans ce cas : `never`) car elle sera interceptée et traitée par Symfony. Par contre, les `attributs` eux sont bien utiles et nous servent à définir la route.

Ensuite, il ne reste plus qu'à éditer le fichier `config/packages/security.yaml` et préciser à Symfony quelle est notre route de déconnexion avec une section nommée `logout`, un peu comme nous l'avons fait pour la connexion :

```yaml
security:
    ...
    firewalls:
       ...
        main:
            ...
            logout:
                path: routeDeconnexion
                target: routeRetour
```

Dans `path`, on précise le **nom** de la route (et pas son chemin) et dans `target` la page vers laquelle est redirigé l'utilisateur après s'être déconnecté.

<div class="exercise">

1. Dans le contrôleur `UtilisateurController`, ajoutez une nouvelle route `deconnexion`, ayant pour chemin `/deconnexion` accessible avec `POST`. Le corps de la fonction traitant cette route est vide et ne sera jamais appelée.

2. Précisez la route de déconnexion dans le fichier `config/packages/security.yaml`. Après s'être déconnecté, l'utilisateur doit être redirigé sur la route `feed`.

3. Dans votre template `base.html.twig`, ajoutez le formulaire suivant dans le menu de navigation en complétant `action` de manière adéquate pour pointer sur votre route `deconnexion` :

    ```html
    <form method="post" action="A compléter">
        <button id="btn-deconnexion">Déconnexion</button>
    </form>
    ```

4. Tentez de vous connecter/déconnecter. Vous pouvez vérifier votre état dans la barre de débogage de Symfony : si votre pseudonyme apparait, vous êtes connecté. Si à la place vous avez `n/a`, vous êtes déconnecté.

</div>

#### Sécurisation d'action

Bon, pour le moment, vous ne voyez aucune différence qui indique que vous êtes bien connecté ! Nous allons effectuer quelques modifications pour changer certains éléments affichés selon l'état de l'utilisateur (connecté ou non).

Dans vos templates `Twig`, vous pouvez utiliser la fonction `is_granted(role)` pour vérifier le rôle d'un utilisateur (par exemple, dans une structure conditionnelle) et ainsi afficher ou non certaines sections.

Dans notre cas, nous pouvons vérifier si l'utilisateur a le rôle `ROLE_USER` qui est le rôle de base que tous les utilisateurs connectés possèdent :

```twig
{% raw %}
{% if is_granted('ROLE_USER') %}
<!-- Utilisateur connecté -->
{% else %}
<!-- Utilisateur non connecté -->
{% endif %}
{% endraw %}
```

On peut bien sûr utiliser `not is_granted('...')` pour vérifier qu'un utilisateur ne possède **pas** un rôle.

Du côté des contrôleurs, il est aussi possible de limiter l'accès à des routes à certains rôles en utilisant l'attribut `#[IsGranted(role)]` au-dessus d'une route :

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(): Response
{
    //Seuls les utilisateurs possédant le rôle `ROLE_ADMIN` ont accès à cette route.
}
```

Si jamais il y a plusieurs méthodes autorisées pour une route (par exemple, `GET` et `POST`) et que l'on souhaite seulement interdire l'accès à cette route pour une méthode donnée, on peut vérifier quelle est la méthode utilisée avec la méthode `isMethod(method)` de l'objet `Request` et refuser l'accès à la route en utilisant `denyAccessUnlessGranted(role)` si l'utilisateur ne possède pas le rôle spécifier :

```php
#[Route('/exemple', name: 'route_exemple', methods: ["GET", "POST"])]
public function methodeExemple(Request $request): Response
{
    if($request->isMethod('POST')) {
        $this->denyAccessUnlessGranted('ROLE_USER');
        //Si l'utilisateur n'a pas le rôle 'ROLE_USER' l'éxécution s'arrête et une page d'erreur est affichée.
    }

    //Tous les utilisateurs (connectés ou non) peuvent accèder à cette route avec la méthode 'GET' mais seuls les utilisateurs qui ont le rôle 'ROLE_USER' (donc, tous les utilisateurs connectés) peuvent déclencher cette route avec la méthode 'POST'.
}
```

Notez qu'il est aussi possible de simplement faire de routes séparées avec le même chemin, une en `GET` et une en `POST` et utiliser l'attribut `IsGranted` :

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/exemple', name: 'route_exemple_get', methods: ["GET"])]
public function methodeExempleGet(): Response
{
    //Tout le monde a accès à cette route.
}

#[IsGranted('ROLE_USER')]
#[Route('/exemple', name: 'route_exemple_post', methods: ["POST"])]
public function methodeExemplePost(): Response
{
    //Seuls les utilisateurs connectés ont accès à cette route.
}
```

Cependant, comme nous l'avons vu, dans le cadre d'un formulaire, nous pouvons regrouper GET et POST dans la même méthode. Dans le cas particulier où l'on souhaite afficher une page formulaire, mais réserver son traitement à un rôle particulier, on utilisera donc plutôt la méthode utilisant `denyAccessUnlessGranted`. Néanmoins, ce cas de figure ne se produit pas souvent, mais c'est le cas pour notre route `feed`, par exemple : on souhaite pouvoir afficher la page à tout le monde, mais réserver la création d'une publication aux utilisateurs connectés.

<div class="exercise">

1. Adaptez le template `base.html.twig` afin que les liens menant vers la page `Inscription` et `Connexion` ne soient affichés que si l'utilisateur n'est **pas** connecté. À l'inverse, faites en sorte que le formulaire de **déconnexion** ne soit visible que par les utilisateurs connectés.

2. Adaptez votre route `feed` pour autoriser la création d'une publication seulement aux utilisateurs connectés. Attention cependant, tout le monde doit pouvoir voir la liste des publications.

3. Enfin, modifiez votre template `feed.html.twig` pour afficher le formulaire de création d'une publication seulement aux utilisateurs connectés.

4. Vérifiez que tout s'affiche comme attendu selon votre état (connecté/déconnecté).

</div>

#### Notification utilisateur de connexion

Comme notre système de connexion/déconnexion est géré par Symfony, nous ne pouvons pas ajouter de messages flash comme pour une route normale. Mais heureusement, pour cela, il y a les **événements** ! Durant le cycle de vie de l'application, certains **événements** comme la connexion ou la déconnexion de l'utilisateur peuvent être captés par le développeur afin de réaliser des actions complémentaires. Les classes qui traitent ces événements sont appelées `EventSubscriber`.

Ces classes sont regroupées dans le dossier `src/EventSubscriber` de l'application et se présentent ainsi : 

```php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class MonEventSubscriber {

    public function __construct(/* Injection de dépendance possible ici*/){}

    #[AsEventListener]
    public function onMonEvent(MonEvent $event) {
        //Méthode déclenchée quand un evenement de type `MonEvent` est déclenché
    }

}
```

* Chaque méthode de la classe peut avoir le nom qu'elle veut, mais il faut qu'elle possède l'attribut `#[AsEventListener]` et prenne en paramètre l'événement qu'elle souhaite traiter.

* Comme pour les services, il est possible de faire de l'injection de dépendances (de services ou d'autres paramètres) via le constructeur.

Dans notre cas, trois événements vont nous intéresser :

* `LoginSuccessEvent` : déclenché quand l'utilisateur s'est authentifié avec succès.

* `LoginFailureEvent` : déclenché quand l'utilisateur n'a pas réussi à s'identifier (mauvais login/mot de passe).

* `LogoutEvent` : déclenché quand l'utilisateur s'est déconnecté.

À l'aide de ces événements, nous allons pouvoir ajouter des messages flash pour améliorer l'ergonomie de notre site après l'exécution de ces actions. Pour cela, nous pouvons utiliser le service `RequestStack` que nous avions déjà utilisé pour `FlashMessageHelper`. Pour rappel, ce service nous permet (entre autres) d'ajouter des messages flash :

```php
$flashBag = $this->requestStack->getSession()->getFlashBag();
$flashBag->add(categorie, message);
```

<div class="exercise">

1. Créez un dossier `EventSubscriber` dans `src`.

2. Dans votre nouveau dossier, créez la classe `AuthenticationSubscriber` qui devra posséder trois méthodes :

    * Une méthode permettant de gérer l'événement `LoginSuccessEvent` et qui ajoute le message flash de type `success` : "Connexion réussie !".

    * Une méthode permettant de gérer l'événement `LoginFailureEvent` et qui ajoute le message flash de type `error` : "Login et/ou mot de passe incorrect !".

    * Une méthode permettant de gérer l'événement `LogoutEvent` et qui ajoute le message flash de type `success` : "Déconnexion réussie !".

    Les imports à faire dans votre classe :

    ```php
    use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
    use Symfony\Component\HttpFoundation\RequestStack;
    use Symfony\Component\Security\Http\Event\LoginFailureEvent;
    use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
    use Symfony\Component\Security\Http\Event\LogoutEvent;
    ```
3. Vérifiez que vos messages s'affichent bien dans les trois situations.
</div>

#### Sécurisation (suite)

Enfin, il reste un dernier problème à régler : malgré le fait que l'accès à la page "inscription" et la page "connexion" soit masqué sur notre page quand un utilisateur est connecté il peut toujours y accéder en tapant l'URL de la route (vous pouvez essayer), ce qui n'est pas normal.

Il est possible d'utiliser l'attribut `#[IsGranted]` pour utiliser une condition plus complexe et vérifier, par exemple, qu'un utilisateur donné n'a pas un rôle spécifique :

```php
use Symfony\Component\ExpressionLanguage\Expression;

#[IsGranted(new Expression("!is_granted('ROLE_USER')"))]
#[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(): Response
{
    //Seuls les utilisateurs ne possédant pas le rôle `ROLE_USER` ont accès à cette route.
}
```

Contrairement à l'utilisation habituelle que nous faisions de `#[IsGranted]`, on peut utiliser certaines fonctions et des opérateurs conditionnels, comme dans un template `Twig` et ainsi faire une condition du style `!is_granted('ROLE_USER')`, c'est-à-dire "n'est pas authentifié". Une autre fonction ayant le même effet (dans notre cas) est `is_authenticated_fully`. Cela peut être utile de l'utiliser si on ne donne pas par défaut le rôle `ROLE_USER` à tous nos utilisateurs connectés.

Cependant, il faut réfléchir en terme d'ergonomie : est-ce qu'un utilisateur connecté tentant d'accéder à ces pages doit recevoir une page d'erreur ou bien être redirigé vers une autre page ? Dans notre cas, nous allons plutôt privilégier la seconde solution. Pour cela, il suffit de regarder les permissions de l'utilisateur à l'intérieur de la route, avec `isGranted` :

```php
#[Route('/exemple', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(): Response
{
    //Déjà connecté...
    if($this->isGranted('ROLE_USER')) {
        return $this->redirectToRoute('maRoute');
    }
}
```

Cependant, gardez en mémoire l'utilisation de `IsGranted` avec une `Expression`, nous en aurons besoin pour le TD3 !

<div class="exercise">

1. Si un utilisateur déjà connecté tente d'accéder aux routes `inscription` ou `connexion`, redirigez-le vers la route `feed` (page principale).

2. Vérifiez que vous êtes effectivement redirigé vers la page principale si vous tentez d'accéder à ces routes en étant connecté.

</div>

## Finitions

Maintenant, il ne reste plus qu'à finaliser notre site, notamment en reliant nos entités `Utilisateur` et `Publication` afin d'enregistrer (et d'afficher) les auteurs d'une publication ! Nous verrons également comment créer une page listant les publications d'un utilisateur, et aussi comment personnaliser certaines pages d'erreurs.

### Auteur d'une publication

Avec **doctrine**, pour associer deux entités, il suffit de créer un attribut faisant référence à une autre classe et utiliser les attributs nécessaires pour préciser le sens et la cardinalité de cette association. Il est aussi possible de paramétrer la stratégie en cas de suppression (mettre à `null` les colonnes faisant référence ou bien supprimer les entités associées...).

Voici la liste des attributs disponibles, qui devraient notamment vous rappeller celles utilisées avec **hibernate** en base de données en 2ème année :

* `#[ORM\ManyToOne(targetEntity: Target::class, inversedBy: ...)]` : À utiliser dans une relation **1 - plusieurs**, du côté de l'entité qui doit posséder **une instance** de l'entité ciblée. Le paramètre `inversedBy` permet de spécifier le nom de l'attribut de la classe cible qui fait référence à l'entité (où on place cette annotation). Le paramètre `targetEntity` permet de spécifier la classe cible.

On peut aussi ajouter un attribut supplémentaire `#[ORM\JoinColumn(onDelete="SET NULL")]` si on veut appliquer la stratégie de mettre l'attribut référencé à `null` lors de la suppression de l'entité référencée (au lieu de supprimer complétement la ressource qui lui est liée).

* `[ORM\OneToMany](targetEntity : Target::class, mappedBy: ..., cascade: [...])` : À utiliser dans une relation **1 - plusieurs**, du côté de l'entité qui doit posséder une **collection** de l'entité ciblée (l'attribut est de type `iterable`). Le paramètre `mappedBy` fonctionne de la même manière que `inversedBy`. Le paramètre `targetEntity` fonctionne de la même manière que l'entité précédente.

* `#[OneToOne(targetEntity: Target::class, mappedBy: ...)]` : À utiliser dans une relation `1 - 1`. Dans l'autre entité, on utilise le même attribut en remplaçant `mappedBy` par `inversedBy`.

* `#[ManyToMany(targetEntity: Target::class, mappedBy: ...)]` : À utiliser dans une relation **plusieurs - plusieurs**. Dans l'autre entité, on utilise la même attribut en remplaçant `mappedBy` par `inversedBy`. Dans ce cas, une nouvelle table sera créée dans la base de données (table de jointure). Dans une des deux entités, au niveau de l'attribut concerné, il faut alors ajouter une autre attribut `#[JoinTable(name: 'nom_table_jointure')]` afin de nommer cette table.

La configuration des attributs présentée implique un système **bi-directionnel** où l'entité A connait l'entité B et inversement. Il est bien entendu possible de faire un système unidirectionnel. Pour cela, il faut placer seulement l'attribut dans une des entités concernées, de ne pas spécifier les paramètres `mappedBy` et `inversedBy` et de rajouter l'attribut `#[JoinColumn(name: 'parent_id', referencedColumnName: 'id')]` où `parent_id` référence le nom de l'attribut "clé étrangère" (qui va être créé) et `referencedColumnName` le nom de la clé primaire de la table référencée. Il est aussi possible de créer des auto-référence (référence vers la même entité).

Attention, au niveau des attributs des relations `OneToOne` ou `ManyToOne`, une clé étrangère est générée dans la base :

* Si on veut que cet attribut ne puisse pas être **null** dans la base, on ajoute un attribut `#[ORM\JoinColumn(nullable: false)]`.

* Si on veut autoriser le comportement de suppression en cascade du côté de la base lors de la suppression de l'entité cible, il faut aussi spécifier le paramètre `onDelete: "CASCADE"` dans cette même attribut. Par exemple `#[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]`

Vous pouvez également consulter [une documentation plus complète](https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/reference/association-mapping.html).

Fort heureusement, Symfony nous permet de mâcher ce travail en utilisant encore une fois la commande `make:entity` en mettant à jour notre entité cible. Il faut simplement :

1. Déclarer une nouvelle propriété

2. Quand on demande le **type**, sélectionner au choix : `ManyToOne`, `OneToMany`, `ManyToMany` ou `OneToOne`. Il est également possible de sélectionner `relation` : cela affichera des informations pour vous aider à choisir parmis les 4 valeurs précédentes.

3. On précise l'entité cible (avec le nom de sa classe).

4. Selon la relation, on précise ensuite si l'entité cible (clé étrangère) peut être nulle ou non, par exemple, dans le cas de `OneToMany`.

5. On nous demande ensuite si la relation doit être bi-directionnelle, c'est-à-dire s'il faut aussi mettre à jour l'entité avec laquelle on forme une relation. Si on sélectionne oui, on nous demande aussi si les entités orphelines doivent être supprimées (par exemple dans notre cas, si on retire une publication à un auteur...).

<div class="exercise">

1. Supprimez toutes les publications stockées dans votre base (car, n'ayant pas d'auteurs, cela va générer des erreurs quand on va forcer les publications à avoir un auteur...).

2. En utilisant la commande `make:entity`, mettez à jour votre entité `Publication` en ajoutant une propriété `auteur` qui sera en **Utilisateur** :

    * Trouvez le bon type à utiliser. Si vous hésitez le type `relation` peut vous aider à choisir.

    * La propriété ne peut pas être nulle.

    * La relation est bi-directionnelle (on doit avoir la liste des publications côté utilisateurs). Nommez cette propriété `publications` côté Utilisateur.

    * Activez la suppression des entités orphelines.

3. Observez le code généré dans `Publication` et `Utilisateur`.

4. Modifiez la classe `Publication` pour faire en sorte que quand un utilisateur est supprimé dans la base, ses publications soient toutes supprimées (correspondant à une contrainte `ON DELETE CASCADE`). Il vous suffit d'éditer un attribut (annotation) déjà existant...

    Vous vous faites peut-être la réflexion que cette contrainte semble redondant avec la suppression des entités orphelines. En fait, `onDelete: "CASCADE"` va créer une contrainte au niveau de la base de données. L'option `orphanRemoval` quant à elle agit au niveau de l'application (de l'ORM) : si on retire la publication à l'utilisateur (depuis sa collection de publications) la publication sera supprimée, car considérée comme orpheline (dans ce contexte, l'entité ne peut pas être possédée par plusieurs entités). Mais si on supprime l'utilisateur tout court, cela ne supprimera pas les publications, car on ne retire pas vraiment la publication d'un utilisateur dans ce contexte. C'est donc pour cela qu'on ajoute aussi la contrainte `ON DELETE CASCADE`.

    En résumé :

    * `orphanRemoval` permet de supprimer une publication si on la retire de l'utilisateur.
    * `ON DELETE CASCADE` : permet de supprimer les publications si l'auteur (l'utilisateur) est supprimé.

5. Mettez à jour la structure de votre base de données avec les commandes `make:migration` et `doctrine:migrations:migrate`.

6. Observez la nouvelle structure de votre base de données depuis votre interface de gestion.

</div>

Maintenant que toutes nos publications doivent avoir un auteur, il va falloir un peu modifier notre route `feed` ainsi que notre template `feed.html.twig`, afin de prendre en compte les données de l'utilisateur.

L'auteur d'une publication est l'utilisateur actuellement connecté qui envoie le formulaire. Du côté contrôleur, lors de la création d'une nouvelle publication, on peut récupérer l'utilisateur connecté ainsi :

```php
//Dans une méthode d'un contrôleur
$utilisateur = $this->getUser();
```

Du côté du template il faut, pour chaque publication :

* Remplacer le "Anonyme" par le login de l'auteur.

* Remplacer l'image de profil "anonyme.jpg" par l'image de profil de l'utilisateur. Cependant, s'il n'a **pas de photo de profil** (propriété `nomPhotoProfil` null) alors il faut continuer d'afficher l'image "anonyme.jpg" (image de profil "par défaut"). Pour rappel, les images de profil sont stockées dans `img/utilisateurs/uploads` et la propriété `nomPhotoProfil` donne simplement le nom de l'image enregistrée dans ce dossier.

Pour le dernier point, il y a plusieurs possibilités : utiliser un "if/else". Utiliser une **ternaire** `(Condition) ? (Statement1) : (Statement2);`. Définir une variable dans le template avec `set`...

Pour rappel, si une propriété d'un objet est **nulle**, alors un test conditionnel "objet.propriete" renvoi simplement `false`.

Aussi, avec `Twig`, pour **concaténer** des valeurs, on utilise le symbole `~` :

```twig
{% raw %}
{{ "Coucou " ~ prenom ~ " !" }}
{% endraw %}
```

Attention de bien respecter **un espace** avant et après `~`.

<div class="exercise">

1. Mettez à jour votre route `feed` dans le contrôleur `PublicationController` afin de récupérer l'utilisateur connecté et de l'affecter comme auteur de la publication avant de l'enregistrer dans la base de données (idéalement, on aurait pu créer un service comme nous l'avions fait pour les utilisateurs, mais là, il s'agit simplement d'une petite ligne de code à ajouter... Mais s'il y avait plus de code à gérer, il faudrait y penser !)

2. Mettez à jour votre template `feed.html.twig` pour afficher le login de l'utilisateur et son image de profil sur chaque publication. S'il n'a pas d'image de profil, il faut continuer d'afficher l'image par défaut `img/utilisateurs/anonyme.jpg`.

3. Connectez-vous (si ce n'est pas déjà fait) puis créez de nouvelles publications. Testez avec un compte ayant une image de profil et un autre compte n'en ayant pas. Vérifiez que tout s'affiche correctement.

</div>

Tout fonctionne bien, mais il y a néanmoins un petit problème : jetez un œil aux requêtes SQL exécutées, en fouillant dans la barre de débogage. Si vous avez X publications, il y a X+1 requêtes exécutées ! Pourquoi ça ?

Si vous vous souvenez de vos cours de base de données du semestre 3, nous avions parlé de deux modes de chargement de données : le **lazy loading** et le **eager loading**. Le lazy loading consiste à ne charger des données que quand on en a besoin alors que le eager loading permet de charger tout d'un coup (avec une seule requête, si possible).

Doctrine utilise notamment une de ses stratégies au niveau des entités en relation. Par exemple, quand on charge l'auteur d'une publication. Par défaut, doctrine utilise le **lazy loading** pour cet attribut. Cela signifie que :

* Quand on exécute `findAllOrderedByDate`, une première requête est exécutée pour récupérer toutes les publications, mais sans les données sur les auteurs.

* Quand, dans notre template `Twig`, on lit les données de l'auteur d'une publication, une nouvelle requête est exécutée pour récupérer ses données. Donc, une requête supplémentaire par publication.

Ceci est très mauvais niveau performance ! Notamment si on a beaucoup de publications. Et comme a priori, on souhaite pouvoir lire quelques données sur l'auteur à chaque fois qu'on charge une publication, il serait plus judicieux d'utiliser le **eager loading** dans ce contexte.

Pour changer la stratégie utilisée pour récupérer les données d'une propriété, il suffit de configurer le paramètre `fetch` (avec `EAGER` ou `LAZY`) dans l'attribut gérant la relation. Par exemple :

```php
#[ORM\ManyToOne(fetch: 'EAGER', ...)]
private ?Entite $monEntite = null;
```

<div class="exercise">

1. Faites en sorte que l'auteur d'une publication soit chargé en mode `EAGER`.

2. Rechargez la page principale et vérifiez qu'il n'y a plus qu'une requête exécutée ! Vous pouvez d'ailleurs observer le code SQL de cette requête.

</div>

Une stratégie plus poussée nommée [EXTRA_LAZY](https://www.doctrine-project.org/projects/doctrine-orm/en/2.16/tutorials/extra-lazy-associations.html) peut aussi être utilisée dans le cadre de collections d'entités, pour ne pas tout charger d'un coup si on la manipule.

Aussi, dans un site concret, on mettrait en place un système de **pagination** pour charger les publications petit à petit, pour ne pas charger tout à chaque fois (imaginez qu'il y ait 1 million de publications !). Par manque de temps, nous ne le ferons pas dans nos TDs, mais pensez-y si vous développez un site similaire dans le futur. N'oubliez pas que vous pouvez ajouter des méthodes à vos classes de repository et utiliser le [DQL](https://www.doctrine-project.org/projects/doctrine-orm/en/2.16/reference/dql-doctrine-query-language.html) pour faire des requêtes plus complexes.

### Page d'un utilisateur

Nous allons maintenant créer une page qui regroupera l'ensemble des publications d'un utilisateur précis. À terme, on ajoutera un lien permettant d'accéder à la page d'un utilisateur depuis une publication et un autre dans le menu de navigation afin que l'utilisateur connecté puisse accéder à sa propre page.

Pour récupérer les informations d'un utilisateur précis, on peut utiliser une route paramétrée comme nous l'avons déjà vu : `/route/{propriete}/test`. On pourrait ensuite alors utiliser le repository de l'entité ciblée puis utiliser `findOne` ou `findOneBy` (si la propriété n'est pas la clé primaire) pour retrouver l'entité :

```php
 #[Route('/route/{propriete}/test', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(string $propriete, ExempleRepository $repository): Response
{
    $exemple = $repository->findOneBy(["propriete" => $propriete]);
    if($exemple == null) {
        //Message d'erreur + redirection
    }

    //Traitement normal
}
```

Si cette méthode est bien valide, depuis sa dernière version, Symfony a introduit une méthode encore plus simple : l'attribut `#[MapEntity]`. Cet attribut se place à côté d'un paramètre type "entité" dans une méthode d'un contrôleur et permet de récupérer automatiquement l'entité avec le paramétrage de la route :

```php
 #[Route('/route/{propriete}/test', name: 'route_exemple', methods: ["GET"])]
public function methodeExemple(#[MapEntity] ?Exemple $exemple): Response
{
    if($exemple == null) {
        //Message d'erreur + redirection
    }

    //Traitement normal
}
```

Dans l'exemple ci-dessus, Symfony va automatiquement utiliser le repository "ExempleRepository" et faire un appel à `findOneBy(["propriete" => $propriete])` pour placer une valeur dans `Exemple`. Le `?` (devant le type) permet d'autoriser une valeur nulle pour l'attribut.

Dans le cas où on utilise `#[MapEntity]` il faut donc que le paramètre de la route porte **exactement** le même nom de la propriété visée dans l'entité et que cette propriété soit unique (pour nos utilisateurs, on pourrait donc soit utiliser l'id, soit le login, soit l'adresse email...)

<div class="exercise">

1. Dans le contrôleur `UtilisateurController` créez une route (et sa méthode) nommée `pagePerso` qui doit être déclenchée par les chemins type `/utilisateurs/{login}/feed` où le login est le login d'un utilisateur. La route est accessible en `GET` seulement. Vous devez faire en sorte de récupérer l'utilisateur correspondant au login passé en paramètre par la route puis :

    * Si l'utilisateur n'existe pas, afficher un message (flash) d'erreur "Utilisateur inexistant" puis rediriger vers la route `feed`.

    * Si l'utilisateur existe bien, retourner la page générée par le template `utilisateur/page_perso.html.twig` que nous allons créer juste après. Il faudra passer l'utilisateur que vous avez récupéré en paramètre de ce template.

2. Créez le template `page_perso.html.twig` dans le dossier `templates/utilisateur`.

    * Le contenu de cette page doit être la liste des publications de l'utilisateur. On veut le même style d'affichage que sur la page principale, vous pourrez reprendre le code de la liste des publications depuis `feed.html.twig` (et l'adapter) pour cette partie.

    * Importez et complétez le template suivant :

    ```twig
    {% raw %}
    {% extends 'base.html.twig' %}

    {% block page_title %}Page de <!-- login de l'utilisateur -->{% endblock %}

    {% block page_content %}
    <main>
        <div class="center">
            <p id="titre-page-perso">Page de <!-- login de l'utilisateur --></p>
        </div>
        <div id="feed">
           <!-- Liste des publications de l'utilisateur -->
        </div>
    </main>
    {% endblock %}
    {% endraw %}
    ```

3. Accédez aux différentes pages personnelles de vos utilisateurs pour vérifier que tout fonctionne.

</div>

Vous connaissez déjà la méthode `path` pour créer une URL depuis le nom d'une route dans un template Twig. Mais comment faire quand le chemin de la route contient des paramètres, comme pour les pages des utilisateurs ? Il suffit d'ajout les paramètres correspondants à `path` :

```twig
{% raw %}
<a href="{{ path('route_exemple', {'propriete' : 'coucou'}) }}">Exemple</a>
{% endraw %}
```

Donc, si la route `route_exemple` possède pour chemin `/route/{propriete}/test` l'exemple ci-dessus générera :

```html
<a href="/route/coucou/test">Exemple</a>
```

Autre élément important à connaître : Symfony met à disposition un objet `app.user` qui est un objet représentant l'utilisateur connecté. On peut donc accéder à ses propriétés, par exemple `app.user.id`, etc...

Vous aurez également remarqué que, dans les pages des utilisateurs, les publications ne sont pas triées par ordre décroissant des dates de publications contrairement sur la page principale. Ici, vous avez directement utilisé la propriété `utilisateur.publications` qui n'applique pas de tri (par défaut).

Pour remédier à ce problème, il suffit d'utiliser un attribut au-dessus de la propriété correspondante, pour indiquer comment elle doit être triée quand lue depuis la base de données :

`#[ORM\OrderBy(["propriete" => "DESC ou ASC", ...])]` : ici, on a le même fonctionnement que pour `findBy`. On indique dans un tableau la ou les propriétés avec lesquelles on souhaite trier les résultats et le sens (ASC ou DESC) :

```php
class Entreprise {

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Employe::class)]
    #[ORM\OrderBy(["salaire" => "DESC"])]
    private Collection $employes;

}
```

Ici, quand on lira la propriété `employes` d'une entité de type `Entreprise`, la collection d'employés sera triée selon le salaire des employés (du plus haut au plus bas). Il est possible d'ajouter d'autres critères, en cas d'égalité... Vous l'aurez compris, les propriétés à indiquer pour le tri appartiennent à l'entité cible de la collection.

<div class="exercise">

1. Faites en sorte que la liste des publications de chaque utilisateur soit triée. Vérifiez en chargeant la page personnelle d'un utilisateur.

2. Modifiez `feed.html.twig` et `page_perso.html.twig` afin d'inclure sur chaque publication un lien vers la page personnelle de l'auteur de la publication au niveau de son image de profil. L'élément `<a></a>` est déjà présent et entoure l'élément `<img>`, il faut simplement compléter la partie `href`.

3. Modifiez `base.html.twig` afin d'ajouter un lien "Ma page" dans le menu de navigation. Ce lien doit pointer vers la page personnelle de l'utilisateur connecté. Ce lien ne doit être visible qu'aux utilisateurs connectés.

4. Rechargez la page principale et testez vos nouveaux liens.

</div>

Comme évoqué plus tôt, dans un site concret, on aurait plutôt un système de pagination plutôt que de charger toutes les publications de l'utilisateur d'un seul coup, avec une requête dédiée (on pourrait alors éventuellement se passer de l'attribut `publications` dans la classe `Utilisateur`, ou bien l'utiliser différemment).

### Inclure des templates

Nous avons dupliqué le code permettant d'afficher la liste des publications dans `feed.html.twig` et `page_perso.html.twig` : ce n'est pas bon !

Une autre fonctionnalité de Twig que nous n'avons pas abordé jusqu'ici est **l'inclusion de template** : il est possible d'inclure le code d'un template dans un autre template. Ce mécanisme est différent de l'extension de template que nous utilisions jusqu'ici et qui consistait à "hériter" du code d'un template et redéfinir certaines parties. L'inclusion de template se rapproche plus d'une fonction qu'on peut réutiliser dans plusieurs autres templates. De plus, un peu comme une fonction, on peut passer des paramètres aux templates inclus.

L'instruction pour inclure un template est la suivante :

```twig
{% raw %}
{{ include(cheminTemplate, {'param1' : ..., 'param2' : ... }) }}
{% endraw %}
```

* `cheminTemplate` : correspond au chemin du template à partir de la racine : le dossier `templates` (comme on étend un template, ou qu'on l'utilise dans un contrôleur...)

* Le second paramètre est optionnel et permet de passer des paramètres utilisables par le template inclus.

Imaginons par exemple que je définisse le template `livres.html.twig` suivant, permettant de générer le code HTML pour présenter les détails d'un livre :

```twig
{% raw %}
<h2>Livre : {{ livre.tire }}</h2>
<p>Année : {{ livre.anneePublication }}<p>
<p>Auteur : {{ livre.auteur }}<p>
{% endraw %}
```

Je peux inclure ce template dans un autre template à tout moment, en passant le livre en paramètre. Par exemple, imaginons que je définisse un template `best_seller.html.twig` qui liste les trois livres les plus vendus cette année. Je possède un objet "top" contenant quatre propriétés : annee, livre1, livre2 et livre3.

```twig
{% raw %}
<h1>Best-sellers de {{ top.annee }} :<h1>
<p>Top 1 :</p>
{{ include('livres.html.twig', {'livre' : top.livre1}) }}
<p>Top 2 :</p>
{{ include('livres.html.twig', {'livre' : top.livre2}) }}
<p>Top 3 :</p>
{{ include('livres.html.twig', {'livre' : top.livre3}) }}
{% endraw %}
```

Bien sûr, la modélisation pour ce problème n'est pas la meilleure, et même dans le template, nous aurions pu utiliser une boucle, mais cela permet d'illustrer efficacement la fonctionnalité d'inclusion.

Tout cela va trouver son intérêt si le template est réutilisé dans plusieurs pages différentes. Par exemple, je pourrais réutiliser dans la page illustrant les détails d'un livre. Ou alors, si j'utilise un formulaire à plusieurs endroits de mon site, je peux le placer dans un template et l'inclure là où il y a besoin.

Il est intéressant de noter que le template inclus a accès à toutes les variables déjà accessibles (ou définies) par le template qui l'appelle.

<div class="exercise">

1. Créez un template `liste_publications.html.twig` dans `templates/publications` contenant le code de **la boucle** affichant la liste des publications.

2. Dans `feed.html.twig` et `page_perso.html.twig` remplacez le code affichant votre liste de publications en incluant votre nouveau template. Il faudra passer la liste des publications en paramètre.

3. Vérifiez que tout s'affiche toujours normalement, sur la page principale et sur une page personnelle.

</div>

### Environnement et pages d'erreurs

Actuellement, quand une page d'erreur s'affiche, vous avez une trace assez complète et beaucoup d'autres informations qui vous permettent de trouver l'erreur. Toutes ces données sont disponibles, car nous sommes dans un **environnement d'application** appelé `dev` sur Symfony. Cet environnement permet aussi d'accéder à la barre de débogage dont nous avons parlé plus tôt.

Bien sûr, toutes ces informations ne doivent pas apparaître quand le site est publié. On change alors d'environnement pour `prod`. Il est aussi possible d'avoir des fichiers de configurations différents par environnement (pas la même base de données entre `dev` et `prod`, par exemple).

Vous avez sans doute remarqué que parfois, le chargement des pages est un peu long après certaines de vos modifications. C'est aussi une des différences majeures entre l'environnement `prod` et `dev`. Quand on est sur l'environnement de production, Symfony utilise un cache qui permet d'accélérer les prochaines requêtes. Néanmoins, après une modification du code, il est nécessaire de vider le cache pour constater les mises à jour. 

En mode développement (`dev`), le cache n'est pas vraiment activé. Après une modification du code, il se régénérera automatiquement, ce qui fait que les requêtes sont plus longues de manière générale. À l'inverse, vous remarquerez que votre site est beaucoup plus rapide en mode `prod` (et heureusement !).

Il faut donc penser à vider le cache quand on fait un changement dans le mode `prod` avec la commande :

```bash
php bin/console cache:clear
```
Ce n'est pas très contraignant, car le développeur ne travaille pas (ou peu) dans le mode `prod`. Ce mode est utilisé là où le site est hébergé publiquement, qui va plutôt subir de "grosses" mises à jour ponctuelles plutôt que plein de petites mises à jour de code toutes les minutes, comme une application en développement. Après une grosse mise à jour du site, il suffit alors de vider le cache une fois.

Pour changer d'environnement, il suffit d'éditer la variable `APP_ENV` dans le fichier `.env` (ou `.env.local`) à la racine du projet.

Pour en revenir aux **pages d'erreurs** celles-ci ont un look bien différent en mode `prod` qu'en mode `dev`. Ce qui est bien, c'est que Symfony nous permet de les customiser ! 

Il faut d'abord créer le chemin de répertoires `templates/bundles/TwigBundle/Exception`. Ensuite, on peut créer :

* Un template pour un **code d'erreur HTTP spécifique** (403, 404, 500, etc...). Ce template doit s'appeler précisément `errorXXX.html.twig` où `XXX` est le code de l'erreur.

* Un template général `error.html.twig` qui sera chargé par défaut, s'il n'existe pas de template pour le code HTTP ayant déclenché l'erreur.

Ces templates seront chargé automatiquement (en mode `prod`) si une erreur survient.

<div class="exercise">

1. Changez l'environnement de l'application à `prod`.

2. Créez l'ensemble de répertoire nécessaire pour accueillir les templates de pages d'erreurs customisés.

3. On va gérer trois cas : l'erreur `403` (accès refusé), l'erreur `404` (page non trouvée) et les autres erreurs (avec le template général). Créez les trois templates nécessaires. On veut que chaque templates héritent de la structure de base de notre site (avec le menu de navigation, etc...) et aient toutes pour titre "Erreur".

    Le contenu principal de chaque page d'erreur sera assez similaire, on changera juste le titre de section et le message affiché :

    ```html
    <main>
        <div class="center">
            <h1><!-- Titre de section --></h1>
            <p><!-- Message --></p>
        </div>
    </main>
    ```

    * Pour l'erreur `403`, le titre de section est "Accès refusé" et le message "Vous ne pouvez pas accéder à cette page !".

    * Pour l'erreur `404`, le titre de section est "Page non trouvée !" et le message "La page demandée est introuvable !".

    * Enfin, pour toutes les autres erreurs, le titre de section est "Erreur !" et le message "Une erreur est survenue ! Veuillez réessayer plus tard.".

    Pour ne pas perdre trop de temps, ne vous embêtez pas à faire une hiérarchie particulière entre les templates d'erreurs pour généraliser certaines parties du code (même si cela serait judicieux). Tous les templates d'erreurs héritent simplement de `base.html.twig` et redéfinissent le block de contenu.

4. Videz le cache.

5. Essayez d'afficher vos pages d'erreurs :

    * Accédez à une route qui n'existe pas.

    * Ajoutez une erreur de syntaxe (temporaire) dans la route `feed` ce qui devrait générer une erreur 500. Videz le cache après avoir placé (cela fera une erreur, c'est normal) puis nettoyé l'erreur de syntaxe.

    * Pour le refus d'accès, c'est un peu plus complexe, car nous n'avons pas vraiment de route protégée pour le moment. Vous pouvez essayer d'ajouter temporairement un attribut `IsGranted` sur une route, en spécifiant un rôle qui n'existe pas (par exemple, `ROLE_TEST`). Videz le cache puis tentez d'accéder à cette route. La page d'erreur devrait s'afficher. N'oubliez pas de supprimer le `IsGranted` temporaire.

    N'oubliez pas que si vous faites des modifications pour améliorer ou corriger votre résultat, il faudra vider le cache (car nous sommes dans le mode `prod`).

6. Repassez l'environnement de l'application à `dev`.

</div>

Vous remarquerez que, si vous êtes connectés et que vous accédez à une route inexistante (404), la page vous apparait comme si vous étiez déconnecté. Malheureusement, c'est une petite limitation de Symfony à ce niveau, car l'ordre d'exécution des processus pour résoudre une route fait que les informations de l'utilisateur ne sont pas encore chargées à ce stade-là (car la route n'existe simplement pas dans l'application). C'est donc "normal" que le menu de navigation ne s'affiche pas comme pour les autres erreurs.

### Note sur les erreurs du formulaire

Avant de conclure, une petite information complémentaire sur les **erreurs** générées quand un formulaire n'est pas valide. Pendant le premier TD, nous avons mis en place tout un système pour que les erreurs soient ajoutées comme des messages flash, mais on aurait également pu utiliser `form_error(form.champ)` pour obtenir les erreurs liées à un champ en particulier. 

Par ailleurs, une bonne pratique de design consiste à plutôt faire apparaître l'erreur liée à un champ au niveau de son label.

Quand on utilise un framework css et/ou un thème de formulaire, Symfony se charge de placer adéquatement les informations liées aux erreurs. Vous pouvez retrouver diverses informations utiles à ce propos [ici](https://symfony.com/doc/current/form/form_customization.html).

## Conclusion

Vous possédez maintenant de solides bases pour construire des sites web à l'aide de Symfony ! Dans le prochain TD, nous irons un peu plus loin en intégrant du JavaScript, de nouvelles fonctionnalités, puis nous nous intéresserons également à la mise en place d'un système de paiement pour permettre à certains utilisateurs de bénéficier d'un accès "Premium" à notre site !
