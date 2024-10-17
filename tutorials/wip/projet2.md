---
title: Projet - API REST (gestion d'événements)
subtitle: API REST, Symfony
layout: tutorial
lang: fr
---

## Sujet - API de gestion d'événements

Ce projet se fera avec le même **trinôme** que le premier projet et s'intéressera au développement d'une **API REST** de **gestions d'événements**.

L'objectif est de développer cette API à l'aide de **Symfony** et **API Platform** (utilisé lors du [TD4]({{site.baseurl}}/tutorials/tutorial4)).

Attention, ici, on vous demande seulement une **API**, donc, **sans interface graphique** (cela viendra lors du troisième projet).

Voici les détails de l'**API REST** qui devra être développée :

* L'API doit permettre gérer des **événements** d'un certain **thème** (un **domaine d'événements** que l'équipe choisira) organisés par différents utilisateurs.

* L'API permet à des utilisateurs de s'**inscrire**, de s'**authentifier**, de **modifier** les informations de leurs comptes, de supprimer leur compte, etc.

* Un utilisateur peut créer un **événement** en précisant diverses informations générales : nom, dates et horaires de début et de fin, si l'événement sera payant (et dans ce cas, son prix) et le nombre maximum de participants.

* Des informations spécifiques au **thème** retenu devront aussi être renseignées : par exemple, on peut imaginer que certains événements sportifs ou e-sportifs sont liés à un cash prize, qu'une LAN party va lister le matériel à amener, les jeux à installer, que la projection d'un film va lister le genre du film, son synopsis, etc... Bien sûr, certaines informations peuvent être obligatoires ou optionnelles.

* Lors de la création d'un événement, l'utilisateur doit pouvoir choisir de rendre publique ou non la **liste des participants**. Si cette liste est **publique**, elle est donc **sérialisée** quand les données dans l'événement sont lues.

* L'utilisateur ayant créé un événement peut le **modifier**, le **supprimer**.

* Les utilisateurs peuvent consulter les événements existants et s'**inscrire** à un événement (s'il reste des places et qu'ils ne sont pas déjà inscrits à un événement qui rentre en conflit, à cause des horaires). Ils peuvent aussi se **désinscrire** d'un événement.

* Les utilisateurs peuvent consulter la liste et les détails de tous les événements auxquels ils sont **inscrits**.

* Les utilisateurs peuvent consulter la liste et les détails de tous les événements qu'ils **organisent**.

* Des utilisateurs ayant le rôle **modérateur** peuvent supprimer des événements.

* Des utilisateurs ayant le rôle **administrateur** ont les mêmes permissions que les **modérateurs** et peuvent aussi supprimer des comptes.

## Aide et pistes

Pour vous aider dans la réalisation du projet, voici quelques pistes :

* Pour **modifier** un objet (entité) déjà existant (par exemple, un utilisateur), on récupère simplement l'objet correspondant et on applique les modifications (par exemple, via un formulaire). Ensuite, on utilise là-aussi le service `EntityManager` afin de synchroniser les modifications avec la base de données en utilisant la méthode `flush`. Plus d'information à ce propos sur [la documentation officielle](https://symfony.com/doc/current/doctrine.html#updating-an-object).

* Dans le [TD1]({{site.baseurl}}/tutorials/tutorial1), nous avons utilisé l'attribut `#[ORM\PrePersist]` afin de créer la date de publication d'un message automatiquement juste avant l'enregistrement en base de données. [D'autres attributs similaires](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/events.html) peuvent vous permettre d'implémenter la fonctionnalité relative à la **date de dernière édition du profil**. En utilisant certains de ces attributs, vous avez accès à un objet lié à l'événement qui permet de récupérer certaines informations (par exemple, connaître **les propriétés qui ont été modifiées**).

* L'attribut `#[ORM\PrePersist]` n'est utilisé que dans le cas d'une insertion dans la base de données (donc, quand l'entité est créée, pas lorsqu'elle est mise à jour).

* Lors des TDs PHP de deuxième année, vous avez vu comment générer une chaîne aléatoire (par exemple [ici](https://romainlebreton.github.io/R3.01-DeveloppementWeb/tutorials/tutorial8.html), au niveau de la section **1.2**).

* Dans les [TD2]({{site.baseurl}}/tutorials/tutorial2), nous avons vu comment capter des **événements** (`LoginSuccessEvent`, `LoginFailureEvent` et `LogoutEvent`) afin d'exécuter du code lorsque ces événements surviennent. [D'autres événements](https://symfony.com/doc/current/event_dispatcher.html) peuvent vous permettre de facilement implémenter la fonctionnalité relative à **la date de dernière connexion** d'un utilisateur ainsi que le **système de maintenance**.

* Pour réaliser la fonctionnalité qui vérifie l'unicité du code du profil pendant l'inscription, à l'aide des requêtes asynchrones, vous pouvez vous inspirer de [ce TD de javascript](https://gitlabinfo.iutmontp.univ-montp2.fr/r4.01-developpementweb/TD5) de 2ᵉ année, notamment pour mettre en place un système de **debouncing**.

* Dans le [TD3]({{site.baseurl}}/tutorials/tutorial3), nous avons vu qu'il faut renvoyer un objet `JsonResponse` quand on veut renvoyer une réponse au format JSON plutôt qu'une page web complète (générée avec **twig**). Vous trouverez plus d'informations [ici](https://symfony.com/doc/current/components/http_foundation.html#creating-a-json-response).

* La création de commandes et la gestion des rôles sont également abordées lors du [TD3]({{site.baseurl}}/tutorials/tutorial3).

* Pour le style du site, faites ce que vous voulez, tant que ce n'est pas trop laid ! Cependant, **il est interdit de reprendre le style de The Feed**. Par contre, vous pouvez utiliser n'importe quel Framework CSS (par exemple [bootstrap](https://getbootstrap.com/docs/5.3/getting-started/download/), [tailwind css](https://tailwindcss.com/) ou bien quelque chose d'encore plus simple comme [bulma](https://bulma.io/)). De plus, **Symfony** vous permet d'intégrer facilement [bootstrap](https://symfony.com/doc/current/form/bootstrap5.html) pour générer facilement des formulaires stylisés. Le site de [tailwind css](https://tailwindcss.com/docs/guides/symfony) possède aussi un guide d'installation pour Symfony.

## Rendu

La **deadline** du projet est le **samedi 28 septembre 2024, 23h59**.

Le projet sera à rendre sur **Moodle** [à cette adresse](https://moodle.umontpellier.fr/course/view.php?id=31511#coursecontentcollapse2). Un seul membre du trinôme dépose une archive **zip** nommée selon le format : `NomPrenomMembre1-NomPrenomMembre2-NomPrenomMembre3.zip`.

Cette archive devra contenir :

* Les sources de votre projet. Attention à ne pas inclure les répertoires **vendor** et **var**.

* Un fichier **README** qui contient :

    * Le lien du dépôt git où le code source de l'application est hébergé.

    * Le fonctionnement de l'annuaire : comment utiliser l'application comment accéder à un profil, les différentes routes, comment utiliser la commande pour créer des utilisateurs, etc.

    * Un récapitulatif de l’investissement de chaque membre du groupe dans le projet (globalement, qui a fait quoi).

    * Éventuellement, des indications supplémentaires s'il y a des choses particulières à faire pour lancer et tester votre application en local (autrement que de lancer le serveur, configurer et générer la base de données, etc...)

## Déroulement du projet et accompagnement

Globalement, la plupart des fonctionnalités sont réalisables à partir des connaissances que vous avez acquises pendant ce cours. Parfois, il faudra un peu adapter. Par exemple, nous n'avons pas fait de fonctionnalité "modifier le profil" sur le site de base de The Feed, mais vous devriez être capable d'implémenter une telle fonctionnalité sur votre projet. Aussi, il faudra vous référer à la section "Aide et pistes" de cette page pour pouvoir implémenter certaines fonctionnalités.

Il faudra aussi faire des recherches et vous documenter par vous-même. Du côté de **Symfony**, vous pouvez notamment consulter la [documentation officielle](https://symfony.com/doc/current/index.html), mais vous trouverez également de l'aide sur de nombreux forums. Vous pouvez même chatter directement sur le [Slack](https://symfony-devs.slack.com/ssb/redirect) de Symfony.

Bien entendu, vous pouvez utiliser des outils de gestion de projet appropriés (Trello par exemple ?). 

Il faudra aussi utiliser et bien organiser un dépôt git. N'oubliez pas que vous pouvez utiliser [le Gitlab du département](https://gitlabinfo.iutmontp.univ-montp2.fr).

N'hésitez pas à poser des questions à votre enseignant chargé de TD et à montrer votre avancement ! Bon projet.
