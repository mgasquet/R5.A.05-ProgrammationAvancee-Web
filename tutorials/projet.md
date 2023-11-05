---
title: Projet
subtitle: API REST, Symfony, Vue.js, Client-Serveur
layout: tutorial
lang: fr
---

## Sujet

Ce projet se fera en **quadrinôme** et s'intéressera au développement de **trois applications** qui vont communiquer entre elles en utilisant les différentes technologies que nous avons abordées pendant ce cours : **Symfony**, **API Platform**, **Vue.js**.

L'objectif est de développer :

* Une **API Rest** avec Symfony, en utilisant **API Platform** ([TD4 de Symfony]({{site.baseurl}}/tutorials/tutorial4)). Il s'agit de la partie **back-end** du service que vous allez développer.

* Une application cliente développée avec Vue.js qui communiquera avec cette API afin de réaliser une interface pour ce service ([TD3 de Vue.js](https://matthieu-rosenfeld.github.io/tutorials/TD3.html)). Il s'agit donc de la partie **front-end** de votre service.

* Un site web classique en "server-side rendering" (qui gère à la fois la partie client et serveur) en utilisant Symfony et Twig ([TD1]({{site.baseurl}}/tutorials/tutorial1), [TD2]({{site.baseurl}}/tutorials/tutorial2) et [TD3]({{site.baseurl}}/tutorials/tutorial3) de Symfony). Ce site consistera en un micro-service qui permettra d'améliorer le client développé en Vue.js (et même d'autres projets !).

Le choix du thème est "semi-libre" (ou semi-imposé si vous préférez :P) : nous allons vous donner une description générale du thème du service à développer et vous devrez vous-même le préciser. Nous allons en reparler dans la section suivante.

### API REST de "Recettes" (Symfony + API Platform)

La première partie consiste donc au développement d'une **API REST** autour du thème des **recettes** en utilisant Symfony et API Platform, comme dans le [TD4]({{site.baseurl}}/tutorials/tutorial4).

L'idée générale de ce service est la suivante :

* On a différents **ingrédients** nommés.

* Une **recette** est composée de différents **ingrédients** (avec différentes quantités) et permettent de produire quelque-chose de nouveau.

* On doit pouvoir connaître la liste des ingrédients, en ajouter, en modifier, en supprimer...

* On doit pouvoir connaître la liste des recettes, en ajouter, en modifier, en supprimer...

* On doit pouvoir retrouver les différentes recettes dans lequel est utilisé un ingrédient.

* On doit pouvoir connaitre les composants d'une recette.

* Etc...

Ceci est une description assez **vague** et très générale que **vous allez devoir préciser** avec un **thème** que vous allez nous soumettre. Il faut que vous trouviez un sujet incorporant un système de recette.

Voici quelques idées :

* Minecraft et son système de crafting (les items sont des ingrédients et servent à crafter d'autres items).

* Marmiton : littéralement un site de recette de cuisine. Chaque recette possède une description, une difficulté, un temps de préparation, et bien sûr, les ingrédients...

* Potion Craft : un jeu où on combine différents ingrédients pour réaliser des potions.

* Little Alchemy : Un [jeu en ligne](https://littlealchemy2.com/) où la combinaison de deux éléments permet de donner un nouvel élément. On commence avec quelques éléments basiques et on on obtient de nouveaux au fil des combinaisons.

* Le système de crafting du jeu Terraria. Certains objets ne peuvent être fabriqués que dans des stations spécifiques...

Cette liste est non exhaustive et vous pouvez bien sûr faire des propositions !

En fait, vous pouvez choisir n'importe quel système incorporant un système de recette ayant ses propres règles. Cela peut être quelque-chose où cet aspect est le centre du système (comme dans Little Alchemy) où bien comme simple composant (comme dans Minecraft). N'importe quel jeu vidéo avec un système de crafting peut donc potentiellement fonctionner.

Votre API de recette doit donc reprendre l'idée générale qui vous a été présentée en introduction (gérer des ingrédients, recettes, etc.) en adaptant et en étendant le concept au thème ciblé. Il faut que votre API soit conforme aux règles du système de recette du thème choisi.

Par exemple :

* Sur Minecraft, une recette est composée de 9 ingrédients **au maximum**, qui peuvent se répéter. Chaque ingrédient est à un emplacement précis (dessine une forme).

* Sur Marmiton, chaque ingrédient n'apparait qu'une fois, avec une quantité et une unité (par exemple, 100 grammes de beurre). On a aussi une liste d'instructions à suivre (préchauffer le four à 180 degrés, battre les œufs avec le lait...)

Une fois le thème choisi, prévenez votre enseignant qui validera ou non le sujet retenu. **Vous n'avez pas le droit de choisir le même thème qu'une autre équipe de votre groupe TD.** Premier arrivé, premier servi ! Aussi, si le système de recette d'un thème est trop proche d'un autre, il pourra être rejeté.

Décidez donc rapidement du thème et ne vous lancez pas dans le projet avant d'avoir eu le feu vert de votre enseignant.

Au-delà de l'aspect recette, votre API devra aussi gérer des **utilisateurs** (qui peuvent s'inscrire, se connecter, gérer leur compte, etc.). L'authentification sera gérée avec des **JWT**. 

Certaines actions ne devront être accessibles qu'aux utilisateurs connectés (comme le fait de créer une recette, par exemple).

Certaines catégories d'utilisateurs devront avoir accès à des fonctionnalités supplémentaires, un peu comme le système de **premium** sur The Feed.

Enfin, on souhaite aussi avoir au moins **un rôle** supplémentaire avec des permissions plus élevées que celles de l'utilisateur. Comme un rôle **administrateur** par exemple, mais cela peut être autre chose.

En interne, vous pouvez ajouter des **commandes symfony** pour effectuer certaines actions, comme changer la catégorie d'un utilisateur, donner/enlever un rôle, etc...

### Application cliente (Vue.js)

La seconde partie du projet consiste à développer une application front-end en Vue.js permettant **d'exploiter complétement votre API de recette** ! Globalement, c'est toute l'idée du [TD3](https://matthieu-rosenfeld.github.io/tutorials/TD3.html) où on construit le front-end de The Feed.

On attend de vous que vous exploitiez au mieux le concept de **réactivité** que nous avons exploré pendant les cours associés.

Là-aussi, il faudra adapter la présentation de l'interface au thème choisi. Vous n'allez pas présenter de la même façon un site permettant de gérer des recettes Minecraft qu'un site permettant de gérer des recettes de cuisine.

Comme vous gérez des utilisateurs via l'API, il faut aussi avoir tout cet aspect sur le site (inscription, connexion, gestion du compte, etc.).

N'hésitez pas à être ambitieux dans votre projet de manière générale et de proposer quelque-chose de sympa en accord avec le thème choisi. Par exemple, un site avec un thème style "Little Alchemy" pourrait bénéficier d'une partie avec une interface simple où on combine deux éléments pour en obtenir un nouveau.

### Site web classique "MyAvatar" (Symfony)

Pour terminer, vous allez développer un site "classique" nommé **MyAvatar** en mode **server-side rendering**, avec Symfony (c'est-à-dire, pas une API). Globalement, ce que vous avez fait du TD1 au TD3 de Symfony, en utilisant **twig** pour l'interface.

Ici, même thème pour tout le monde : une imitation du service [Gravatar](https://fr.gravatar.com/).

Ce service permet (en vous inscrivant) d'associer votre adresse mail à une photo de profil. Ainsi, à partir de votre adresse, les différents sites web et applications peuvent charger votre image de profil depuis ce service sans avoir besoin de stocker cette image de leur côté et sans demander à l'utilisateur d'uploader cette image. Il suffit de faire une simple requête à **Gravatar**. Certains sites comme **Bitbucket** utilisent ce mécanisme.

Ainsi, n'importe quelle application manipulant **l'adresse email** d'un utilisateur peut facilement d'aller charger une image depuis **Gravatar**.

Pour obtenir l'image de profil **Gravatar** d'un utilisateur, on prend son adresse mail, on la **hache** avec l'algorithme **MD5** et on fait appel à l'URL : [https://www.gravatar.com/avatar/hash](https://www.gravatar.com/avatar/hash)

Par exemple, si on prend l'email d'exemple suivant : `gravatar.exemple.iut.mtp@gmail.com`.

On la hache avec `MD5` (par exemple avec [ce site](https://www.md5hashgenerator.com/)) : `ed2e85b77eea9cd92a5348c421ff812b`.

Et on obtient l'adresse de l'image de profil via ce lien : [https://www.gravatar.com/avatar/ed2e85b77eea9cd92a5348c421ff812b](https://www.gravatar.com/avatar/ed2e85b77eea9cd92a5348c421ff812b)

L'idée du projet **MyAvatar** est donc de faire la même chose :

* Le site permet de s'inscrire en précisant un login, un mot de passe, une adresse email et surtout une photo de profil.

* L'utilisateur peut modifier les informations de son compte, notamment son adresse email et sa photo de profil. Il peut même supprimer son compte. Attention à bien gérer le fichier contenant la photo de profil dans tous ces cas !

* On peut accéder à l'image de profil à partir du code obtenu en chiffrant l'adresse email de l'utilisateur en MD5.

Vous pouvez adopter différentes stratégies :

* Directement stocker et maintenir la photo de profil avec un nom correspondant au chiffrement MD5 de l'adresse email. Ce qui permet d'avoir un lien direct vers la photo de profil (sans faire de requête passant par le framework). Mais on a moins de contrôle sur l'accès à cette ressource.

* Faire une autre méthode de stockage et avoir une action dans un controller pour retourner l'image de profil. Cela permet d'avoir plus de contrôle sur ce qui est retourné (par exemple, si l'utilisateur n'existe pas). On peut retourner une donné de type [BinaryFileResponse](https://symfony.com/doc/current/components/http_foundation.html#serving-files) dans le controller pour cela.

Du côté de votre **application Vue.js**, vous devrez utiliser le service "MyAvatar" pour charger les photos de profils de vos utilisateurs en utilisant leur adresse email (information que vous donne l'API). Il suffit de hacher son adresse email et d'accéder au fichier avec un lien vers le site de "MyAvatar".

Si l'utilisateur n'existe pas, il faudrait afficher une image par défaut (comme l'image "anonyme" sur The Feed). À vous de voir si vous gérez cela du côté du service 'MyAvatar" ou au niveau de votre application Vue.js, selon vos choix d'implémentations.

Le package [crypto-js](https://www.npmjs.com/package/crypto-js) permet de chiffrer du texte, notamment en **MD5** (`npm install crypto-js`).

Pour le style du site MyAvatar, faites ce que vous voulez, tant que ce n'est pas trop laid ! Cependant, **il est interdit de reprendre le style de The Feed**. Par contre, vous pouvez utiliser n'importe quel Framework CSS (style **bootstrap**). De même, si votre site comporte du JavaScript, vous pouvez utiliser **jQuery**, par exemple.

## Hébergement des applications

Les différentes applications doivent être hébergées dans le dossier `public_html` d'un des membres de l'équipe (pas nécessairement le même pour toutes les applications).

Il faudra bien vérifier que vos applications sont accessibles depuis l'extérieur de l'iut sur l'adresse : [http://webinfo.iutmontp.univ-montp2.fr/~login_depot/sous-adresse-du-projet](http://webinfo.iutmontp.univ-montp2.fr/~login_depot/sous-adresse-du-projet).

Il ne faut pas oublier de donner au serveur de l'IUT les droits d'écriture sur vos projets contenu dans `public_html` :

```bash
setfacl -R -m u:www-data:r-w-x ~/public_html
setfacl -R -m d:u:www-data:r-w-x ~/public_html
```

## Rendu

La **deadline** du projet est le **date à définir**.

Le projet sera à rendre sur **Moodle**. Un seul membre du quadrinôme dépose une archive **zip** nommée selon le format : `NomPrenomMembre1-NomPrenomMembre2-NomPrenomMembre3-NomPrenomMembre4.zip`.

Cette archive devra contenir :

* Les sources de vos trois projets dans trois dossiers séparés. Attention à ne pas inclure les répertoires liés aux librairies comme **vendor** pour les deux projets liés à Symfony.

* Un fichier **README** qui contient :

    * Le lien vers la page où est hébergé chaque application.

    * Une présentation du thème choisi pour les recettes (spécificités, règles...).

    * Une présentation de l'API produite, ses fonctionnalités, etc.

    * Une présentation de l'application Vue.js produite, ses fonctionnalités, etc.

    * Le fonctionnement de "MyAvatar" : comment faire pour récupérer l'image de profil d'un utilisateur. Aussi, éventuellement préciser si vous avez ajouté par rapport à ce qui était demandé.

    * Un récapitulatif de l'investissement de chaque membre du groupe dans le projet (globalement, qui à fait quoi).

## Déroulement du projet et accompagnement

Le projet donnera lieu à **3 notes**, une note pour chaque partie.

Attention, il y a beaucoup à faire, répartissez-vous bien les tâches.

Globalement, la plupart des fonctionnalités sont réalisables à partir des connaissances que vous avez acquises pendant ce cours. Parfois, il faudra un peu adapter. Par exemple, nous n'avons pas fait de fonctionnalité "modifier le profil" sur le site de base de The Feed, mais vous devriez être capable d'implémenter une telle fonctionnalité sur "My Avatar".

Pour certaines fonctionnalités plus poussées et selon votre modélisation métier du système de recette, il faudra peut-être faire des recherches et vous documenter par vous-même. Du côté de **Symfony** vous pouvez notamment consulter la [documentation officielle](https://symfony.com/doc/current/index.html), mais vous trouverez également de l'aide sur de nombreux forums. Vous pouvez même chatter directement sur le [Slack](https://symfony-devs.slack.com/ssb/redirect) de Symfony.

De même, vous pouvez consulter [la documentation](https://vuejs.org/guide/introduction.html) du Vue.js.

Aussi, c'est un projet assez conséquent et en équipe : cela signifie donc des outils de gestions appropriés (Trello par exemple ?) et surtout l'utilisation et la bonne organisation d'un dépôt git. N'oubliez pas que vous pouvez utiliser [le Gitlab du département](https://gitlabinfo.iutmontp.univ-montp2.fr).

Quoi qu'il en soit, n'hésitez pas à poser des questions à votre enseignant chargé de TD et à montrer votre avancement ! Bon projet.
