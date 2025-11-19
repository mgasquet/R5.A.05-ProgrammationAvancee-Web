---
title: Projet - Site de critiques
subtitle: API REST, Symfony, Vue.js, Cleint-Serveur
layout: tutorial
lang: fr
---

## Sujet - Site de critiques

Ce projet se fera en **quadrinôme** et s'intéressera au développement de **deux applications** qui vont communiquer entre elles en utilisant les différentes technologies que nous avons abordées pendant ce cours : **Symfony**, **API Platform** et **Vue.js**.

L'objectif est de développer :

* Une **API Rest** avec **Symfony**, en utilisant **API Platform** ([TD4 de Symfony]({{site.baseurl}}/tutorials/tutorial4)). Il s'agit de la partie **back-end** du service que vous allez développer.

* Une application cliente développée avec Vue.js qui communiquera avec cette API afin de réaliser une interface pour ce service ([TD3 de Vue.js](https://matthieu-rosenfeld.github.io/tutorials/TD3.html)). Il s'agit donc de la partie **front-end** de votre service.

Le choix du thème est "semi-libre" (ou semi-imposé si vous préférez :P) : nous allons vous donner une description générale du thème du service à développer et vous devrez vous-même le préciser. Nous allons en reparler dans la section suivante.

### API REST de "Critiques" (Symfony + API Platform)

La première partie consiste donc au développement d'une **API REST** autour du thème des **critiques** en utilisant Symfony et API Platform, comme dans le [TD4]({{site.baseurl}}/tutorials/tutorial4).

L'idée est que vous choisissiez un type "d'objet critiquable" sur lequel portera votre application (par exemple des films, ou bien des livres, des établissements, des jeux-vidéos, ce que vous voulez tant que c'est validé par votre enseignant). Par la suite, quand on parlera d'objet critiquable, on parlera donc du type d'objet sur lequel porte l'application.

Voici les détails de l'**API REST** qui devra être développée :

* L'API permet à des utilisateurs de s'**inscrire**, de s'**authentifier**, de **modifier** les informations de leurs comptes, de supprimer leur compte, etc.

* Il existe différents **rôles** sur l'application : les utilisateurs basiques (permissions par défaut), les gestionnaires et les administrateurs.

* Un utilisateur ayant le rôle de gestionnaire ou d'administrateur peut créer un **objet critiquable** (par exemple, la fiche d'un film) en précisant diverses **informations spécifiques au thème** retenu (par exemple, pour un film, son titre, sa durée, les acteurs, etc). Il faudra que les informations soient **suffisamment variées**.

* Les gestionnaires et les administrateurs peuvent modifier et supprimer n'importe quel "objet critiquable".

* Les utilisateurs basiques peuvent aussi **créer des objets critiquables** (par exemple, ajouter la fiche d'un film s'il n'existe pas), **qui devront toutefois être validées** par un gestionnaire ou un administrateur avant d'être accessibles publiquement.

* N'importe quel utilisateur connecté peut **créer une critique** d'un des objets de l'application (mais pas plusieurs sur le même objet). Le contenu de la critique sera composé d'une **note**, d'un **texte** et de plusieurs **critères** spécifiques au type d'objet traité par l'application. L'utilisateur peut aussi modifier et supprimer ses critiques.

* N'importe qui (sans être connecté) peut :

    * Accéder aux **détails d'un objet critiquable** (s'il est publié) et à ses **critiques**. Les détails de l'objet incluront également le **nombre de critiques** et la **note moyenne** (issue de l'ensemble des notes des critiques de l'objet).

    * Accéder aux **détails des critiques** créées par un utilisateur.

* Les utilisateurs peuvent s'**abonner** à des objets critiquables et/ou à des utilisateurs (ce qui permettra de retrouver ,sur le client web, les dernières critiques des objets ou des utilisateurs suivis).

* Les gestionnaires et les administrateurs peuvent **supprimer** n'importe quelle **critique**.

* Les administrateurs peuvent **supprimer** n'importe quel **compte**, sauf celui d'un autre administrateur.

* Les gestionnaires et les administrateurs peuvent **valider** la publication d'un **objet critiquable** soumis par un utilisateur basique.

Attention, le **thème** que vous allez choisir doit être soumis à la validation de votre enseignant. 
Il est interdit de prendre un sujet déjà pris par une autre équipe de votre groupe TD. Premiers arrivés, premiers servis !

Vous serez attentif à ce que votre projet contienne :
* un **modèle de données** suffisamment riche,
* des **groupes** de normalisation, dénormalisation, de validation, correctement exploités afin de contrôler les données que l'on récupère ou que l'on écrit.
* des **ressources** en relation avec votre thème (comme `/utilisateurs`, `/objets`, etc), et des **sous-ressources adéquates** (par exemple, nous avions créé la sous-ressource `/utilisateurs/{id}/publications` en TD),
* des **attributs** pour gérer les différentes contraintes (**assertions**) métiers au niveau des propriétés des ressources,
* de la **documentation** (au niveau du code et de l'API),
* l'exploitation des différents **verbes HTTP** (GET, POST, PATCH, DELETE...). Il faudra notamment limiter les verbes utiliser sur certaines ressources,
* l'authentification avec des **JWTs** qui renvoient des cookies et diverses informations (comme dans le TD), et un système de **rafraîchissement** par token,
* des **StateProcessor**,
* une gestion convenable des **permissions**, notamment avec le paramètre **security** des attributs liés à API Platformn, des **rôles** et des **voters**.

La seconde partie du projet consistera à la réalisation d'une interface (front-end en Vue.js) qui exploitera/consommera votre API.

Pour vous aider dans la réalisation du projet, voici quelques pistes :

* Le [TD4]({{site.baseurl}}/tutorials/tutorial4) vous donne une bonne introduction à **API Platform**. La plupart des choses dont vous aurez besoin pour réaliser votre projet sont introduites dans ce TD.

* La [documentation officielle](https://api-platform.com/docs/symfony/) d'API Platform est assez complète et peut beaucoup vous aider!

* En TD, nous avons abordé la génération automatique de **groupes de validation**. Il est possible de faire la même chose pour les [groupes de sérialisation](https://api-platform.com/docs/core/serialization/#changing-the-serialization-context-on-a-per-item-basis-for-symfony).

### Application cliente (Vue.js)

La seconde partie du projet consiste à développer une application front-end en **Vue.js** permettant **d'exploiter votre API de critique** ! Globalement, c'est toute l'idée du [TD3](https://matthieu-rosenfeld.github.io/tutorials/TD3.html) où on construit le front-end de The Feed.

On attend de vous que vous exploitiez au mieux le concept de **réactivité** que nous avons exploré pendant les cours associés.

Là-aussi, il faudra adapter la présentation de l'interface au thème choisi. Vous n'allez pas présenter de la même façon un site permettant de gérer des critiques de films qu'un site permettant de critiquer des jeux-vidéos.

Ce front devra donc permettre d'utiliser les fonctionnalités suivantes :

* Permettre à des utilisateurs de s'**inscrire**, de s'**authentifier**, de **modifier** les informations de leurs comptes, de **supprimer** leur compte, etc.

* Permettre aux **utilisateurs connectés** de créer un **objet critiquable** :

    * Pour un gestionnaire ou un administrateur, il sera publié immédiatement.

    * Pour un utilisateur basique, il sera soumis à validation.

* Permettre aux utilisateurs connectés de **critiquer** un objet (et de modifier/supprimer leurs propres critiques).

* Permettre aux gestionnaires et aux administrateurs de **supprimer** des **objets critiquables** et des **critiques**.

* Permettre aux administrateurs de **supprimer** des **comptes** (sauf ceux des autres administrateurs).

* N'importe quel utilisateur (connecté ou non) doit pouvoir consulter **la liste et les détails des objets critiquables** (publiés) et des critiques.

* La page d'un objet critiquable inclut :

    * Les informations de l'objet.

    * Le nombre de critiques et la note moyenne.

    * Le détail des critiques.

* La page d'un utilisateur doit présenter **la liste de ses critiques**.

* Un utilisateur connecté doit pouvoir s'**abonner** à des objets critiquables et à des utilisateurs et retrouver sur des pages dédiées :

    * La liste des dernières critiques des objets suivis.

    * La liste des dernières critiques des utilisateurs suivis.

* Les **gestionnaires** et les **administrateurs** peuvent retrouver les objets critiquables en **attente de validation** sur une page et les **valider**.

* Ce projet sera aussi relié au projet **MyAvatar** afin de charger les photos de profils de vos utilisateurs en utilisant leur adresse email (information que vous donne l'API). Il suffit de hacher son adresse email et d'accéder au fichier avec un lien vers le site de **MyAvatar**. Vous devrez installer une librairie adéquate pour chiffrer l'adresse email avec `SHA-256`.

{% comment %}
* Vous pouvez si vous le désirez ajouter d'autres fonctionnalités, notamment pour les autres rôles, mais cela ne vous est pas demandé. 
{% endcomment %}

Vous serez attentif à ce que votre projet contienne :
* des routes nommées,
* une gestion correcte de la connexion / déconnexion / rafraichissement du token s'il expire pendant la navigation,
* un découpage pertinent en vues et composants,
* une gestion des différentes erreurs possibles lors des requêtes à l'API,
* un code correctement typé avec TypeScript,
* des notifications (messages flashes) pour améliorer la navigation.

## Hébergement des applications

Vous devrez héberger les deux applications (front-end et back-end) dans le dossier `public_html` d'un des membres de l'équipe (pas nécessairement le même pour toutes les applications).

Attention, vous n'avez qu'une seule base MySQL chacun et vous ne pourrez pas déployer vos deux projets Symfony sur la même base. Vous pouvez soit utiliser la base d'une autre membre du groupe pour le deuxième projet ou simplement utiliser des bases SQLite.

Pour pouvoir accéder à votre *home* à distance et y déposer des fichiers, il faudra d'abord trouver vos identifiants de connexion login et mot de passe.

Vous pourrez utiliser FileZilla (il faudra certainement l'installer) pour déposer des fichiers dans votre dossier `~/public_html` sur le serveur de l'IUT. Pour vous connecter, il faudra choisir comme protocole `SFTP`, comme hôte `ftpinfo.iutmontp.univ-montp2.fr` et vous devrez utiliser votre login et mot de passe. Vous pouvez alors facilement déplacer des fichiers de votre machine vers votre `home`.

Vous aurez surtout besoin de vous connecter en `ssh` pour pouvoir exécuter les commandes nécessaires au bon déploiement. Vous pouvez d'ailleurs utiliser git depuis `ssh` pour récupérer vos projets sans utiliser FTP. Pour les projets Symfony, il faudra notamment faire les `composer install` et probablement créer les bases. Pour le projet Vue, vous pouvez normalement copier directement la version build du projet.

Sous Linux, la commande vous permettant de vous connecter au serveur est la suivante (en remplaçant `mon_login_IUT` par votre login)

```sh
ssh mon_login_IUT@162.38.222.93 -p 6666
```

Il faudra ensuite entrer votre mot de passe et répondre "yes" à la question "Are you sure you want to continue connecting". Vous êtes alors connectés en `ssh` et les commandes que vous tapez sont exécutées sur la machine cible. Vous pouvez alors procéder au déploiement de votre site.

Il faudra aussi certainement donner les droits au serveur Apache de lire vos fichiers. Pour cela depuis le terminal connecté en `ssh` vous pourrez utiliser les deux commandes suivantes :

```sh
   # On modifie (-m) récursivement (-R) les droits r-x
   # de l'utilisateur (u:) www-data
   setfacl -R -m u:www-data:r-x ~/public_html
   # On fait de même avec des droits par défaut (d:)
   # (les nouveaux fichiers prendront ces droits)
   setfacl -R -m d:u:www-data:r-x ~/public_html
```

Concernant l'API REST développée avec `Symfony`, elle devra être déployée en environnement **prod** (et pas **dev**). Vous devrez aussi renforcer la sécurité en interdisant l'accès aux autres répertoies que `public` en ajoutant un fichier `.htaccess` qui contient l'instruction `Require all denied` à la racine de votre projet et en ajoutant `Require all granted` au début du fichier `.htaccess` contenu dans `public`. Cela permettra notamment d'éviter que n'importe qui puisse lire le contenu de votre fichier `.env`, par exemple.

Il faudra bien vérifier que vos applications sont accessibles depuis l'extérieur de l'IUT sur l'adresse : [http://webinfo.iutmontp.univ-montp2.fr/~login_depot/sous-adresse-du-projet](http://webinfo.iutmontp.univ-montp2.fr/~login_depot/sous-adresse-du-projet).

Concernant la base de données, vous pourrez utiliser la base de données MySQL ou PostgreSQL d'un des membres de l'équipe, mises à dispositions par l'IUT.

Vous trouverez toutes les informations à propos de la connexion aux [bases de données](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/bases-de-donnees/) sur [l'intranet](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet) du département informatique.

Des sources complémentaires sur comment se connecter en FTP et en SSH à `public_html` (pour y déposer des fichiers) :

* [https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/votre-espace-de-travail/](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/votre-espace-de-travail/)
* [https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/acces-aux-serveurs/](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/acces-aux-serveurs/)
* [https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/partager-public_html/](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/partager-public_html/)
* [https://docs.google.com/document/d/1rLb4QWt0uOxE8IuLqeUxjZTivMIA6KbLzieNvsSa_p0/edit#heading=h.l1xavd57lfgb](https://docs.google.com/document/d/1rLb4QWt0uOxE8IuLqeUxjZTivMIA6KbLzieNvsSa_p0/edit#heading=h.l1xavd57lfgb)

## Rendu

La **deadline** du projet est le **dimanche 4 janvier, 23h59**.

Le projet sera à rendre sur **Moodle** (l'adresse sera fournie ultérieurement). Un seul membre du groupe dépose une archive **zip** nommée selon le format : `NomPrenomMembre1-NomPrenomMembre2-NomPrenomMembre3-NomPrenomMembre4.zip`.

Cette archive devra contenir :

* Les sources de vos deux projets dans deux dossiers séparés (c'est-à-dire, au niveau du conteneur Docker, le projet Symfony du dossier `public_html` et le projet Vue.js du le dossier `workspace`). Attention à ne pas inclure les répertoires **var** et **vendor** pour l'API réalisée avec Symfony et les dossiers **node_modules** et **dist** du client vue.

* Une manière de faire fonctionner une BD non vide quand le code tourne en local. Plusieurs solutions sont possibles :
    * utiliser SQLite et nous fournir le fichier de la BD,
    * nous fournir un fichier d'import MySQL,
    * fournir une commande PHP qui peuple la base,
    * connecter la version dev à la BD déployé sur webinfo.

* Un fichier **README** qui contient :

    * Les URL où les deux projets sont déployés.

    * Les liens des dépôts git où le code source de chaque application est hébergé.

    * Un récapitulatif de l’investissement de chaque membre du groupe dans les deux derniers projets (globalement, qui a fait quoi, avec des pourcentages).

    * Éventuellement, des indications supplémentaires s'il y a des choses particulières à faire pour lancer et tester vos applications en local (autrement que de lancer le serveur, faire les `composer/npm install` des projets, configurer et générer la base de données, etc.).
    
    * Une explication sur comment peupler la BD.

    * Des logins/mot de passe d'utilisateurs intéressants, pour tester les diverses fonctionnalités (un utilisateur basique, un gestionnaire, un administrateur...).

    * Une présentation du thème choisi (quel type d'objet est critiqué, quelles informations sont présentées sur la page d'un objet, quelles sont les critères d'une critique, etc).

    * Le fonctionnement de l'API : comment l'utiliser, les différentes routes, etc.

    * Tout autre commentaire que vous jugez pertinent.

## Déroulement du projet et accompagnement

Le projet donnera lieu à **2 notes**, une note pour chaque partie.

Attention, il y a beaucoup à faire, répartissez-vous bien les tâches.

Aussi, c'est un projet assez conséquent et en équipe : cela signifie donc des outils de gestions appropriés (Trello par exemple ?) et surtout l'utilisation et la bonne organisation d'un dépôt git. Il faudra donc utiliser et bien organiser un dépôt sur [le Gitlab du département](https://gitlabinfo.iutmontp.univ-montp2.fr).

N'hésitez pas à poser des questions à votre enseignant chargé de TD et à montrer votre avancement ! Bon projet.
