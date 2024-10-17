---
title: Projet - API REST (gestion d'événements)
subtitle: API REST, Symfony
layout: tutorial
lang: fr
---

## Sujet - API de gestion d'événements

Ce projet se fera avec en **trinôme** (vous n'êtes pas obligé de garder la même équipe que pour le premier projet) et s'intéressera au développement d'une **API REST** de **gestions d'événements**.

L'objectif est de développer cette API à l'aide de **Symfony** et **API Platform** (utilisé lors du [TD4]({{site.baseurl}}/tutorials/tutorial4)).

Attention, ici, on vous demande seulement une **API**, donc, **sans interface graphique** (cela viendra lors du troisième projet).

Voici les détails de l'**API REST** qui devra être développée :

* L'API doit permettre de gérer des **événements** d'un certain **thème** (un **domaine d'événements** que l'équipe choisira) organisés par différents utilisateurs.

* L'API permet à des utilisateurs de s'**inscrire**, de s'**authentifier**, de **modifier** les informations de leurs comptes, de supprimer leur compte, etc.

* Un utilisateur ayant le statut **organisateur** peut créer un **événement** en précisant diverses informations générales : nom, dates et horaires de début et de fin, si l'événement sera payant (et dans ce cas, son prix) et le nombre maximum de participants.

* Des informations spécifiques au **thème** retenu devront aussi être renseignées : par exemple, on peut imaginer que certains événements sportifs ou e-sportifs sont liés à un cash prize, qu'une LAN party va lister le matériel à amener, les jeux à installer, que la projection d'un film va lister le genre du film, son synopsis, etc... Bien sûr, certaines informations peuvent être obligatoires ou optionnelles.

* Seuls les utilisateurs ayant le statut d'**organisateur** peuvent créer des événements.

* Lors de la création d'un événement, l'organisateur doit pouvoir choisir de rendre publique ou non la **liste des participants**. Si cette liste est **publique**, elle est donc **sérialisée** quand les données dans l'événement sont lues.

* L'organisateur ayant créé un événement peut le **modifier** et le **supprimer**.

* Les utilisateurs peuvent consulter les événements existants et s'**inscrire** à un événement (s'il reste des places et qu'ils ne sont pas déjà inscrits à un événement qui rentre en conflit, à cause des horaires). Ils peuvent aussi se **désinscrire** d'un événement.

* Les utilisateurs peuvent consulter la liste et les détails de tous les événements auxquels ils sont **inscrits**.

* Les organisateurs peuvent consulter la liste et les détails de tous les événements qu'ils **organisent**.

* Les utilisateurs ayant le rôle **administrateur** peuvent supprimer des événements et des comptes utilisateur.

Attention, le **thème** que vous allez choisir doit être soumis à la validation de votre enseignant. 
Il est interdit de prendre un sujet déjà pris par une autre équipe de votre groupe TD. Premiers arrivés, premiers servis !

Ce projet sera fortement lié au **3ème projet** qui consistera à la réalisation d'une interface (front-end en Vue.js) qui explopitera votre API.

## Aide et pistes

Pour vous aider dans la réalisation du projet, voici quelques pistes :

* Le [TD4]({{site.baseurl}}/tutorials/tutorial4) vous donne une bonne introduction à **API Platform**. La plupart des choses dont vous aurez besoin pour réaliser votre projet sont introduites dans ce TD.

* La [documentation officielle](https://api-platform.com/docs/symfony/) d'API Platform est assez complète et peut beaucoup vous aider!

* En TD, nous avons abordé la génération automatique de **groupes de validation**. Il est possible de faire la même chose pour les [groupes de sérialisation](https://api-platform.com/docs/core/serialization/#changing-the-serialization-context-dynamically).

* Dans certains cas, les relations **ManyToMany** demandent un traitement et des choix particuliers au niveau de l'API. Nous vous donnerons prochainement quelques ressources et exemples pour vous aider à ce niveau.

## Premier rendu

Concrètement, ce projet sera évalué en deux temps.

Vous allez d'abord devoir rendre un premier rendu intermédiaire qui doit être suffisament avancé. 

Cette première version de l'API n'a pas besoin de contenir toutes les foncitonnalités demandées dans la présentation du sujet, mais elle doit au moins inclure tous les aspects abordés dans le [TD4]({{site.baseurl}}/tutorials/tutorial4) : 

* Gestion d'utilisateurs
* Groupes de normalisation, dénormalisation, validation.
* Des ressources comme `/utilisateurs`, au moins une sous-ressource comme `/utilisateurs/{id}/publications`.
* Gestion des différents verbes (GET, POST, PATCH, DELETE...).
* Authentification avec des JWTs qui renvoient des cookies et diverses informations (comme dans le TD).
* Au moins un **StateProcessor**.
* Gestion de la sécurité des actions.
* Générateur de groupes (validation, normalisation, denormalisation).

Par la suite, lorsque vous développerez le 3ème projet, vous allez être amené à étendre votre API.

La **deadline** de ce premier rendu est le **dimanche 27 octobre 2024 à 15h00**.

Le projet sera à rendre sur **Moodle** [à cette adresse](https://moodle.umontpellier.fr/course/view.php?id=31511#coursecontentcollapse2).
Un seul membre du trinôme dépose une archive **zip** nommée selon le format : `NomPrenomMembre1-NomPrenomMembre2-NomPrenomMembre3.zip`.

Cette archive devra contenir :

* Les sources de votre projet. Attention à ne pas inclure les répertoires **vendor** et **var**.

* Un fichier **README** qui contient :

    * Le lien du dépôt git où le code source de l'application est hébergé.

    * Le fonctionnement de l'API : comment l'utiliser, les différentes routes, etc.

    * Un récapitulatif de l’investissement de chaque membre du groupe dans le projet (globalement, qui a fait quoi).

    * Éventuellement, des indications supplémentaires s'il y a des choses particulières à faire pour lancer et tester votre application en local (autrement que de lancer le serveur, configurer et générer la base de données, etc...).

## Second rendu

Le second rendu viendra compléter votre API (avec toutes les fonctionnalités demandées en introduction) et sera livré en même temps que le 3ème projet (front de l'application). C'est donc pour plus tard !

## Déroulement du projet et accompagnement

Comme pour le précédent projet, il faudra aussi utiliser et bien organiser un dépôt git. N'oubliez pas que vous pouvez utiliser [le Gitlab du département](https://gitlabinfo.iutmontp.univ-montp2.fr).

N'hésitez pas à poser des questions à votre enseignant chargé de TD et à montrer votre avancement ! Bon projet.