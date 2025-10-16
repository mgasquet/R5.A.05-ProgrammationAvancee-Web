---
title: Projet - My Avatar
subtitle: Symfony
layout: tutorial
lang: fr
---

## Sujet

Ce projet se fera en **quadrinôme** ou en **trinôme** et s'intéressera au développement d'une application de **gestion d'avatar**.

L'objectif est de développer un site web classique en "server-side rendering" (qui gère à la fois la partie client et serveur) en utilisant Symfony et Twig ([TD1]({{site.baseurl}}/tutorials/tutorial1), [TD2]({{site.baseurl}}/tutorials/tutorial2) et [TD3]({{site.baseurl}}/tutorials/tutorial3) de Symfony).

L'application à produire sera une imitation du service [Gravatar](https://fr.gravatar.com/).

Ce service permet (en vous inscrivant) d'associer votre adresse mail à une photo de profil. Ainsi, à partir de votre adresse, les différents sites web et applications peuvent charger votre image de profil depuis ce service sans avoir besoin de stocker cette image de leur côté et sans demander à l'utilisateur d'uploader cette image. Il suffit de faire une simple requête à **Gravatar**. Certains sites comme **Bitbucket** utilisent ce mécanisme.

Ainsi, n'importe quelle application manipulant **l'adresse email** d'un utilisateur peut facilement d'aller charger une image depuis **Gravatar**.

Pour obtenir l'image de profil **Gravatar** d'un utilisateur, on prend son adresse mail, on la **hache** avec l'algorithme **SHA256** et on fait appel à l'URL : [https://www.gravatar.com/avatar/hash](https://www.gravatar.com/avatar/hash)

Par exemple, si on prend l'email d'exemple suivant : `gravatar.exemple.iut.mtp@gmail.com`.

On la hache avec `SHA256` (par exemple avec [ce site](https://emn178.github.io/online-tools/sha256.html)) : `045e5536817c1efc94745289b76b39e72197e7f1ad9a5a11b72622b1dbbded8c`.

Et on obtient l'adresse de l'image de profil via ce lien : [https://www.gravatar.com/avatar/045e5536817c1efc94745289b76b39e72197e7f1ad9a5a11b72622b1dbbded8c](https://www.gravatar.com/avatar/045e5536817c1efc94745289b76b39e72197e7f1ad9a5a11b72622b1dbbded8c)

L'idée du projet **MyAvatar** est donc de faire la même chose.

### Cahier des charges

Voici les détails du service qui devra être développé :

* Le site permet de s'inscrire en précisant un **login**, un **mot de passe**, une **adresse email** et éventuellement une photo de profil (non obligatoire).

* Une fois connecté, l'utilisateur a accès à un espace de gestion privé où il peut notamment **visualiser sa photo de profil** (s'il en a une actuellement), la **modifier** et la **supprimer** au besoin.

* L'utilisateur peut aussi **modifier** certaines informations de son compte via une **page dédiée** : son mot de passe et son adresse email. Afin de valider ces modifications, **l'utilisateur doit saisir son mot de passe actuel**. Dans ce formulaire, l'adresse email doit être automatiquement **pré-remplie**.

* L'utilisateur peut aussi **supprimer son compte**.

* Lors de l'inscription, le site vérifie en direct (sans rechargement de la page) que le login et l'adresse email spécifiés ne sont pas déjà pris (grâce à `JavaScript` et des requêtes `AJAX`). Le même système de vérification est mis en place lors de la modification du compte (pour l'adresse email).

* N'importe qui peut accéder à la photo de profil à partir d'une URL contenant le code obtenu en **chiffrant l'adresse email de l'utilisateur** dont on veut obtenir l'image de profil en `SHA256` (par exemple, via une route `/avatar/{hash}`). Si l'utilisateur n'existe pas ou s'il n'a pas de photo de profil actuellement, une image par défaut est renvoyée.

* Un utilisateur peut **masquer** son profil. On ne doit pas pouvoir accéder à la photo de profil d'un utilisateur dont le profil est masqué. L'utilisateur peut masquer/démasquer son profil depuis son espace de gestion avec un simple clic sur un bouton, sans rechargement de la page (encore avec du `JavaScript` + `AJAX`).

* Le site doit pouvoir être passé en **mode maintenance** à l'aide d'un nouveau paramètre que vous pourrez définir et modifier dans le fichier `services.yaml`. Quand le site est en mode maintenance, toutes les pages du site doivent rediriger sur une page qui affiche un message expliquant que le site est actuellement en maintenance (même l'accès aux photos de profils des utilisateurs, etc).

* Certains utilisateurs peuvent posséder le rôle d'**administrateur**. Depuis leur page de gestion, ils ont accès à une nouvelle page qui liste tous les utilisateurs. À partir de cette page, un administrateur peut **supprimer les différents comptes utilisateurs** sauf ceux des administrateurs.

* Une commande (**Symfony**) doit permettre de créer un utilisateur depuis le terminal en précisant ses informations et son rôle (normal/administrateur), à l'exception de sa photo de profil. Les informations pourront être données directement en argument de la commande, ou alors en mode interactif.

* Une commande (**Symfony**) doit permettre de supprimer un utilisateur à partir de son login.

### Contraintes techniques

* La fonction [hash](https://www.php.net/manual/en/function.hash.php) peut vous aider à encoder certaines données en `SHA256`.

* Le lieu où sont stockées les photos de profil ne doit pas être accessible publiquement par un client (notamment car un utilisateur peut masquer son profil à certains moments). Il ne faut pas stocker ces images dans `public`, par exemple. Il faudra donc faire en sorte que **Symfony serve le fichier via une action d'un contrôleur** quand quelqu'un souhaite accéder à un avatar (par exemple, via une route `/avatar/{hash}`), et que le client n'accède jamais directement au fichier via un chemin vers le répertoire de stockage des photos. Symfony peut retourner une donnée de type [BinaryFileResponse](https://symfony.com/doc/current/components/http_foundation.html#serving-files) (par exemple, pour répondre avec une image) dans une action d'un contrôleur (à la place d'utiliser `$this->render` pour renvoyer une page web générée avec twig).

* Attention, lors de l'édition de profil, il faudra bien penser à mettre certaines données à jour en cas de modification de l'adresse email (car le hash `SHA256` ne sera plus le même !).

* De même, attention à bien supprimer la photo de profil en cas de suppression de compte...

* Seul le **JavaScript** "nature" est autorisé (pas de framework JS, juste de simples fichiers **JavaScript**, comme dans le TD3).

* Pour le style du site, faites ce que vous voulez, tant que ce n'est pas trop laid ! Cependant, **il est interdit de reprendre le style de The Feed**. Par contre, vous pouvez utiliser n'importe quel Framework CSS (par exemple [bootstrap](https://getbootstrap.com/docs/5.3/getting-started/download/), [tailwind css](https://tailwindcss.com/) ou bien quelque chose d'encore plus simple comme [bulma](https://bulma.io/)). De plus, **Symfony** vous permet d'intégrer facilement [bootstrap](https://symfony.com/doc/current/form/bootstrap5.html) pour générer facilement des formulaires stylisés. Le site de [tailwind css](https://tailwindcss.com/docs/guides/symfony) possède aussi un guide d'installation pour Symfony.

## Aide et pistes

Pour vous aider dans la réalisation du projet, voici quelques pistes :

* Normalement, dans un contexte réel, la racine du site pointe sur le dossier `public` de l'application, ce qui fait que les autres répertoires (au-dessus) ne sont naturellement pas accessibles par le client/navigateur. Cependant, comme nous utilisons une architecture particulière en TD, vous pouvez renforcer la sécurité des autres répertoires en interdisant leur accès en ajoutant un fichier `.htaccess` qui contient l'instruction `Require all denied` à la racine de votre projet et en ajoutant `Require all granted` au début du fichier `.htaccess` contenu dans `public`.

* Pour **modifier** un objet (entité) déjà existant (par exemple, un utilisateur), on récupère simplement l'objet correspondant et on applique les modifications (par exemple, via un formulaire). Ensuite, on utilise là-aussi le service `EntityManager` afin de synchroniser les modifications avec la base de données en utilisant la méthode `flush`. Plus d'information à ce propos sur [la documentation officielle](https://symfony.com/doc/current/doctrine.html#updating-an-object).

* Pour la modification de l'image de profil d'un utilisateur, comme elle se fait à part de la modification des informations de l'utilisateur, vous allez probablement passer par un formulaire qui permettra de transmettre la nouvelle image. Cependant, la gestion de ce formulaire est un peu spéciale, car il ne sera (probablement) pas directement lié à une entité (un objet instancié) comme nous l'avons fait en TD (ou comme dans le point concernant la mise à jour cité au-dessus), parce qu'on envoie seulement une image, qui devra être traitée par l'application, sans directement l'injecter telle quelle dans l'utilisateur courant (par contre, on pourra ultimement stocker son nom ou autre...). **Symfony permet de récupérer et traiter les données d'un formulaire sans nécessairement passer par une entité** (mais on peut quand même utiliser une classe qui étend `AbstractType`). Plus d'information à ce propos sur [la documentation officielle](https://symfony.com/doc/current/form/without_class.html).

* Vous avez déjà eu à gérer une **image de profil** envoyée via un **formulaire** dans le [TD2]({{site.baseurl}}/tutorials/tutorial2).

* Dans le [TD2]({{site.baseurl}}/tutorials/tutorial2), nous avons vu comment capter des **événements** (`LoginSuccessEvent`, `LoginFailureEvent` et `LogoutEvent`) afin d'exécuter du code lorsque ces événements surviennent. [D'autres événements](https://symfony.com/doc/current/event_dispatcher.html) peuvent vous permettre de facilement implémenter la fonctionnalité relative au **système de maintenance**.

* Pour réaliser la fonctionnalité qui vérifie l'unicité du login et de l'adresse email à l'aide des requêtes asynchrones, vous pouvez vous inspirer de [ce TD de javascript](https://gitlabinfo.iutmontp.univ-montp2.fr/r4.01-developpementweb/TD5) de 2ᵉ année, notamment pour mettre en place un système de **debouncing**.

* La création de commandes et la gestion des rôles sont également abordés lors du [TD3]({{site.baseurl}}/tutorials/tutorial3).

## Hébergement

L'application doit être hébergée dans le dossier `public_html` d'un des membres de l'équipe (sur le serveur de l'IUT).

Il faudra installer les sources (sans le dossier vendor) dans le dossier (avec `FTP` par exemple), puis exécuter un `composer install` à la racine de l'application (qui installera les différentes dépendances). Vous pouvez utiliser `SSH` pour cela, en vous connectant à votre session sur le serveur de l'IUT.

Il ne faut pas oublier de donner au serveur de l'IUT les droits d'écriture sur vos projets contenu dans `public_html` (une fois le projet installé). Par exemple, depuis `SSH` :

```bash
setfacl -R -m u:www-data:r-w-x ~/public_html
setfacl -R -m d:u:www-data:r-w-x ~/public_html
```

Une fois installé, il faudra bien vérifier que votre application est bien accessible depuis l'extérieur de l'iut sur l'adresse : [http://webinfo.iutmontp.univ-montp2.fr/~login_depot/sous-adresse-du-projet](http://webinfo.iutmontp.univ-montp2.fr/~login_depot/sous-adresse-du-projet).

Concernant la base de données, vous pourrez utiliser la base de données MySQL ou PostgreSQL d'un des membres de l'équipe, mises à dispositions par l'IUT.

Vous trouverez toutes les informations à propos de la connexion en [FTP et SSH](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/acces-aux-serveurs/) et aux [bases de données](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/bases-de-donnees/) sur [l'intranet](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet) du département informatique.

## Rendu

La **deadline** du projet est le **dimanche 26 octobre 2025, 23h59**.

Le projet sera à rendre sur **Moodle** [à cette adresse](https://moodle.umontpellier.fr/course/section.php?id=237151). Un seul membre du groupe projet dépose une archive **zip** nommée selon le format : `NomPrenomMembre1-NomPrenomMembre2-NomPrenomMembre3-NomPrenomMembre4.zip`.

Cette archive devra contenir :

* Les sources de votre projet. Attention à ne pas inclure les répertoires **vendor** et **var**.

* Un fichier **README** qui contient :

    * Le lien du dépôt git où le code source de l'application est stocké.

    * Un mini manuel d'utilisation : comment utiliser l'application, comment accéder à un avatar, gérer son profil, les différentes routes, comment utiliser la commande pour créer/supprimer des utilisateurs, etc.

    * Un récapitulatif de l’investissement de chaque membre du groupe dans le projet (globalement, qui a fait quoi).

    * Éventuellement, des indications supplémentaires s'il y a des choses particulières à faire pour lancer et tester votre application en local (autrement que de lancer le serveur, configurer et générer la base de données, etc...)

## Déroulement du projet et accompagnement

Globalement, la plupart des fonctionnalités sont réalisables à partir des connaissances que vous avez acquises pendant ce cours, mais il faudra parfois aller chercher un peu plus loin. Par exemple, nous n'avons pas fait de fonctionnalité "modifier le profil" sur le site de base de The Feed, mais vous devriez être capable d'implémenter une telle fonctionnalité sur votre projet. Aussi, il faudra vous référer à la section "Aide et pistes" de cette page pour pouvoir implémenter certaines fonctionnalités.

Il faudra aussi faire des recherches et vous documenter par vous-même. Du côté de **Symfony**, vous pouvez notamment consulter la [documentation officielle](https://symfony.com/doc/current/index.html), mais vous trouverez également de l'aide sur de nombreux forums. Vous pouvez même chatter directement sur le [Slack](https://symfony-devs.slack.com/ssb/redirect) de Symfony.

Bien entendu, vous pouvez utiliser des outils de gestion de projet appropriés (Trello par exemple ?). 

Il faudra aussi utiliser et bien organiser un dépôt git. N'oubliez pas que vous pouvez utiliser [le Gitlab du département](https://gitlabinfo.iutmontp.univ-montp2.fr).

N'hésitez pas à poser des questions à votre enseignant chargé de TD et à montrer votre avancement ! Bon projet.