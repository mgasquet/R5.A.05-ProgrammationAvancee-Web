---
title: Projet - Annuaire
subtitle: Symfony
layout: tutorial
lang: fr
---

## Sujet - Annuaire en ligne

Ce projet se fera en **trinôme** et s'intéressera au développement d'une application **d'annuaire en ligne**.

L'objectif est de développer un site web classique en "server-side rendering" (qui gère à la fois la partie client et serveur) en utilisant Symfony et Twig ([TD1]({{site.baseurl}}/tutorials/tutorial1), [TD2]({{site.baseurl}}/tutorials/tutorial2) et [TD3]({{site.baseurl}}/tutorials/tutorial3) de Symfony).

Voici les détails du service qui devra être développé :

* Le site web est un annuaire en ligne qui permet à chaque utilisateur de créer et de compléter un profil avec un certain nombre de champs informatifs.

* Le profil de l'utilisateur possède l'un des deux modes de visibilité suivants :
    * **Publique** : le profil est accessible par tous et est listé dans l'annuaire. Il est donc listé dans l'annuaire.
    * **Non répertorié** : le profil est toujours accessible par tous (via l'URL du profil) mais il n'est pas listé dans l'annuaire.
    * **Privé** : le profil n'est accessible que par son propriétaire (même si quelqu'un d'autre dispose de l'URL), il n'est pas listé dans l'annuaire.

* La page principale liste tous les profils **publics** enregistrés dans l'application. À partir de cette page, on doit aussi pouvoir accéder facilement aux pages de profils des profils listés.

* Attention, comme expliqué juste avant, même si le profil est **non répertorié**, il peut toujours être consulté via l'adresse et le code du profil (contrairement aux profils privés). S'il est **non répertorié**, il n'est simplement pas listé sur la page principale de l'annuaire.

* Lors de l'inscription (via un formulaire) l'utilisateur précise seulement un minimum d'informations : login, adresse email, mot de passe et la visibilité du profil (public/non répertorié/privé).

* Chaque profil doit être associé à un **code unique**. Pendant l'inscription, l'utilisateur peut choisir de préciser lui-même ce code ou non (à condition qu'il ne soit pas déjà pris). S'il ne précise rien, un code aléatoire sera alors généré.

* Quand l'utilisateur décide lui-même de saisir un code, l'application doit vérifier en temps réel (sans rechargement de la page) que le code n'est pas déjà pris, avant la soumission du formulaire (donc, en utilisant du **JavaScript** et des **requêtes asynchrones**). Le code ne doit contenir que des caractères alphanumériques. De la même façon, le site vérifie également en direct que le login et l'adresse email spécifiés ne sont pas déjà pris. Le même système de vérification est mis en place lors de la modification du compte (pour l'adresse email et le code).

* Une fois connecté, l'utilisateur peut **éditer son profil** avec des **informations complémentaires** de votre choix (par exemple, numéro de téléphone, pays, adresse postale, réseaux sociaux, etc.). À vous de trouver les données qui vous semblent intéressantes à préciser sur le profil. Il faut que ces données soient suffisamment riches et variées !

* Le **formulaire d'édition** du profil doit être automatiquement **pré-rempli** avec les informations.

* À tout moment, l'utilisateur peut **changer le code associé à son profil** (soit en spécifiant un nouveau, soit en demandant la génération d'un code aléatoire).

* L'utilisateur peut **supprimer son compte**.

* Une route incluant le **code du profil** permet d'accéder et de visualiser la page de profil d'un utilisateur (par exemple `/profil/{code}`). Il n'y a pas besoin d'être connecté pour cela. Bien sûr, ce lien ne doit pas fonctionner si le profil est en mode privé, si quelqu'un d'autre que le propriétaire essaye de le consulter.

* L'utilisateur peut **changer la visibilité de son profil** à tout moment.

* En plus de la route qui permet de visualiser le profil de l'utilisateur sur une page dédiée, une autre route (qui inclue donc aussi le code secret du profil) doit renvoyer les informations de l'utilisateur au format `JSON` (donc, pas une page web complète, seulement les données). Cela vous servira plus tard, lors du 3ᵉ projet où vous utiliserez directement de ce service. Attention, comme pour le lien de la page de profil, ce lien ne fonctionne pas si le profil est en mode privé.

* Sur le profil, l'application doit afficher **la dernière date où a été édité le profil**. **Attention**, vous devrez faire en sorte que cette date soit mise à jour dès que **l'objet** (entité) stockant l'utilisateur est mise à jour, peu importe l'endroit où cela est fait : dans un contrôleur, dans un service, dans une commande, etc. Il faut ainsi faire en sorte de ne pas avoir à dupliquer le code gérant cette logique si une nouvelle portion de code mettant à jour cette entité est implémentée. Par contre, **il ne faut pas que la date d'édition du profil** soit automatiquement mise à jour dès que l'utilisateur se connecte simplement.

* Le site doit pouvoir être passé en **mode maintenance** à l'aide d'un nouveau paramètre que vous pourrez définir et modifier dans le fichier `.env` (ou `services.yaml`). Quand le site est en mode maintenance, toutes les pages du site doivent rediriger sur une page qui affiche un message expliquant que le site est actuellement en maintenance.

* Certains utilisateurs peuvent posséder le rôle d'**administrateur**. Sur la page principale, en plus des profils **visibles**, un les profils **non répertoriés** et **privés** sont également listés, et il peut y accéder. De même, il **peut tout à fait accéder aux à la page de détails d'un profil privé**. Aussi, à partir d'un profil, un administrateur peut **supprimer le compte** de l'utilisateur qui possède ce profil, sauf si cet utilisateur est aussi un administrateur.

* Un système permet aux utilisateurs de **signaler** un profil dont le contenu est inapproprié, avec un commentaire. Les administrateurs ont alors accès à une page spéciale qui liste les signalements, avec les différentes informations nécessaires (utilisateur à l'origine du signalement, commentaire, lien vers le profil signalé...). Il doit être possible de supprimer un signalement (afin qu'il ne reste pas listé une fois qu'il a été traité...).

* Plusieurs commandes (**Symfony**) doivent être ajoutées :
    * Une commande qui permet de créer un utilisateur depuis le terminal en précisant ses informations et son rôle (normal/administrateur).
    * Une commande qui permet de supprimer un utilisateur à partir de son login.
    * Une commande qui permet d'élever un utilisateur au rôle d'administrateur (à partir de son login).
    * Une commande qui permet de retirer le rôle d'administrateur à un utilisateur (à partir de son login).

## Contraintes techniques

* Seul le **JavaScript** vu en TD est autorisé (pas de framework JS réactif comme React, Angular, etc... Juste le fonctionnement du JavaScript dans Symfony vu dans le TD3, notamment avec les controllers Stimulus).

* Pour le style du site, faites ce que vous voulez, tant que ce n'est pas trop laid ! Cependant, **il est interdit de reprendre le style de The Feed**. Par contre, vous pouvez utiliser n'importe quel Framework CSS (par exemple [bootstrap](https://getbootstrap.com/docs/5.3/getting-started/download/), [tailwind css](https://tailwindcss.com/) ou bien quelque chose d'encore plus simple comme [bulma](https://bulma.io/)). De plus, **Symfony** vous permet d'intégrer facilement [bootstrap](https://symfony.com/doc/7.4/form/bootstrap5.html) pour générer facilement des formulaires stylisés. Le site de [tailwind css](https://tailwindcss.com/docs/guides/symfony) possède aussi un guide d'installation pour Symfony.

* Faites en sorte de ne pas avoir d'actions (dans les contrôleurs) trop grosses, de séparer les responsabilités, de créer et utiliser des **services**, etc.

* Vous pouvez utiliser les fonctionnalités **Turbo** au besoin (frame, stream) abordés dans le TD3, mais aucune obligation. Vous pouvez remplacer les fonctionnalités dynamiques proposées par ce système par du JavaScript.

## Aide et pistes

Pour vous aider dans la réalisation du projet, voici quelques pistes :

* Pour **modifier** un objet (entité) déjà existant (par exemple, un utilisateur), on récupère simplement l'objet correspondant et on applique les modifications (par exemple, via un formulaire). Ensuite, on utilise là-aussi le service `EntityManager` afin de synchroniser les modifications avec la base de données en utilisant la méthode `flush`. Plus d'information à ce propos sur [la documentation officielle](https://symfony.com/doc/7.4/doctrine.html#updating-an-object).

* Dans le [TD1]({{site.baseurl}}/tutorials/tutorial1), nous avons utilisé l'attribut `#[ORM\PrePersist]` afin de créer la date de publication d'un message automatiquement juste avant l'enregistrement en base de données. [D'autres attributs similaires](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/events.html) peuvent vous permettre d'implémenter la fonctionnalité relative à la **date de dernière édition du profil**. En utilisant certains de ces attributs, vous avez accès à un objet lié à l'événement qui permet de récupérer certaines informations (par exemple, connaître **les propriétés qui ont été modifiées**).

* L'attribut `#[ORM\PrePersist]` n'est utilisé que dans le cas d'une insertion dans la base de données (donc, quand l'entité est créée, pas lorsqu'elle est mise à jour).

* Nous avons vu comment générer une chaîne aléatoire lors de la sauvegarde de la photo de profil de l'utilisateur dans le [TD2]({{site.baseurl}}/tutorials/tutorial2).

* Dans les [TD2]({{site.baseurl}}/tutorials/tutorial2), nous avons vu comment capter des **événements** (`LoginSuccessEvent`, `LoginFailureEvent` et `LogoutEvent`) afin d'exécuter du code lorsque ces événements surviennent. [D'autres événements](https://symfony.com/doc/7.4/event_dispatcher.html) peuvent vous permettre de facilement implémenter la fonctionnalité relative à **la date de dernière connexion** d'un utilisateur ainsi que le **système de maintenance**.

* Dans le [TD2]({{site.baseurl}}/tutorials/tutorial2), nous avons vu comment capter des **événements** (`LoginSuccessEvent`, `LoginFailureEvent` et `LogoutEvent`) afin d'exécuter du code lorsque ces événements surviennent. [D'autres événements](https://symfony.com/doc/7.4/event_dispatcher.html) peuvent vous permettre de facilement implémenter la fonctionnalité relative au **système de maintenance**.

* Dans le [TD3]({{site.baseurl}}/tutorials/tutorial3), nous avons vu qu'il faut renvoyer un objet `JsonResponse` quand on veut renvoyer une réponse au format JSON plutôt qu'une page web complète (générée avec **Twig**). Vous trouverez plus d'informations [ici](https://symfony.com/doc/7.4/components/http_foundation.html#creating-a-json-response).

* Pour réaliser la fonctionnalité qui vérifie l'unicité du login, du code du profil et de l'adresse email à l'aide des requêtes asynchrones, vous pouvez vous inspirer de [ce TD de JavaScript](https://gitlabinfo.iutmontp.univ-montp2.fr/r4.01-developpementweb/TD5) de 2ᵉ année, notamment pour mettre en place un système de **debouncing**.

* La création de commandes et la gestion des rôles et permissions sont également abordées lors du [TD3]({{site.baseurl}}/tutorials/tutorial3).

## Hébergement et déploiement

L'application doit être hébergée et déployée dans le dossier `public_html` d'un des membres de l'équipe (sur le serveur `webinfo` de l'IUT).

Vous pouvez consulter [cette note complémentaire]({{site.baseurl}}/complements/deploiement) afin de déployer votre site sur le serveur. Il est conseillé de tester le déploiement au plus tôt pour ne pas avoir de mauvaises surprises le jour du rendu.

## Rendu

La **deadline** du projet est le **dimanche 25 octobre 2026, 23h59**.

Le projet sera à rendre sur **Moodle** (adresse communiquée prochainement). Un seul membre du groupe projet dépose une archive **zip** nommée selon le format : `NomPrenomMembre1-NomPrenomMembre2-NomPrenomMembre3-NomPrenomMembre4.zip`.

Cette archive devra contenir :

* Les sources de votre projet. Attention à ne pas inclure les répertoires superflus comme **vendor** et **var**, **.idea**, **.git**, etc.

* Un fichier **README** qui contient :

    * Le lien du dépôt git où le code source de l'application est stocké.

    * Un mini manuel d'utilisation qui détaille les fonctionnalités de l'application, comment y accéder, les utiliser, etc.

    * Éventuellement, des indications supplémentaires s'il y a des choses particulières à faire pour lancer et tester votre application en local (autrement que de lancer le serveur, configurer et générer la base de données, etc...).
    
    * Tout autre commentaire que vous jugez pertinent.

* Un fichier **IDENTIFIANTS** qui donne les identifiants de **plusieurs** comptes utilisateurs (normaux et administrateurs) sur votre application hébergée sur `webinfo`.

* Un fichier **TRAVAIL_GROUPE** qui détaille, **pour chaque membre du groupe** :
    * Un **pourcentage** de son investissement sur le projet.
    * La liste **précise** du travail qu'il a réalisé (fonctionnalités, sécurité, etc). Il faut donc penser à bien noter tout cela au fur et à mesure du projet (utilisez des outils de gestion de projet adaptés).

## Soutenances et notation

Des soutenances (oraux) auront lieu quelque temps après la remise du projet. Le document **TRAVAIL_GROUPE** que vous aurez remis servira comme base à cette soutenance. Diverses questions techniques seront posées à chaque membre du groupe selon le travail qu'il a réalisé. Il faudra alors bien maîtriser le code de votre projet pour pouvoir y répondre. Lors de cette soutenance, vous devrez venir avec le code source chargé dans votre IDE, afin de pouvoir vous appuyer dessus pour répondre aux questions. Votre application devra aussi être disponible afin d'effectuer d'éventuels tests en direct. Le but de ces oraux est d'évaluer à quel point vous comprenez et vous maîtrisez le projet que vous avez produit et les notions abordées en cours.

La note du projet sera à la fois composée du résultat (est-ce que l'application fonctionne, remplit le cahier des charges, est ergonomique, ne présente pas de bugs, etc) qui sera évalué en amont, puis de la note de la soutenance, qui aura un poids bien plus important et permettra d'individualiser la note de chaque membre du groupe.

## Déroulement du projet et accompagnement

Globalement, la plupart des fonctionnalités sont réalisables à partir des connaissances que vous avez acquises pendant ce cours, mais il faudra parfois aller chercher un peu plus loin. Par exemple, nous n'avons pas fait de fonctionnalité "modifier le profil" sur le site de base de **The Feed**, mais vous devriez être capable d'implémenter une telle fonctionnalité sur votre projet. Aussi, il faudra vous référer à la section "Aide et pistes" de cette page pour pouvoir implémenter certaines fonctionnalités.

Il faudra aussi faire des recherches et vous documenter par vous-même. Du côté de **Symfony**, vous pouvez notamment consulter la [documentation officielle](https://symfony.com/doc/7.4/index.html), mais vous trouverez également de l'aide sur de nombreux forums, ou avec un LLM (mais il faut bien relire, évaluer et comprendre le code généré). Vous pouvez aussi chatter directement sur le [Slack](https://symfony-devs.slack.com/ssb/redirect) de Symfony.

Bien entendu, vous pouvez utiliser des outils de gestion de projet appropriés (Trello par exemple ?). 

Il faudra aussi utiliser et bien organiser un dépôt git. N'oubliez pas que vous pouvez utiliser [le Gitlab du département](https://gitlabinfo.iutmontp.univ-montp2.fr).

N'hésitez pas à poser des questions à votre enseignant chargé de TD et à montrer votre avancement ! Bon projet.