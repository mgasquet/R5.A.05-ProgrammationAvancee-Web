---
title: Déploiement sur le serveur du département
subtitle:
layout: tutorial
lang: fr
---

Il est possible d'utiliser le serveur web du département informatique l'IUT pour déployer et héberger votre application web développée avec Symfony. Cela sera notamment utile pour les deux projets que vous allez être amené à réaliser au cours de ce semestre.

Les applications doivent être déployées dans le dossier `~/public_html` de votre session, qui sert à héberger vos applications web, afin qu'elle soit accessible publiquement.

## Préparation des fichiers

Avant de déployer votre application développée avec **Symfony**, il faut préparer les fichiers à un passage en mode **production**.

Tout d'abord, il faut éditer le fichier `.env` à la racine du projet :

```yaml
# Pour passer le site en mode production
APP_ENV=prod

# Adresse publique du site. Utile si on se sert du bundle FosJsRoutingBundle 
DEFAULT_URI=https://webinfo.iutmontp.univ-montp2.fr/~login/monapp/public/

# Adresse et informations de la base de données utilisée en production
DATABASE_URL="..."

# Et éventuellement d'autres paramètres relatifs au projet (secrets, clés, adresses, etc...)
```

Concernant l'URI, cela dépendra de votre login et du nom de votre projet. Par exemple, si votre login est `toto` et que le projet est contenu dans un répertoire `site` déposé directement dans votre dossier `~/public_html` sur le serveur de l'IUT, l'adresse sera [https://webinfo.iutmontp.univ-montp2.fr/~toto/site/public/](https://webinfo.iutmontp.univ-montp2.fr/~toto/site/public/).

Pour la base de données, il faudra spécifier les informations de connexion de l'une des bases de données du département. Comme dans les TDs, nous utilisons une base de données **MySQL** il est fortement conseillé de faire de même en production (ainsi, les migrations déjà créées fonctionneront directement).

Si vous souhaitez utiliser votre base de données MySQL, vous pouvez utiliser l'adresse suivante :

```yaml
DATABASE_URL="mysql://votre_login:votre_mot_de_passe@db:3316/votre_login?serverVersion=mariadb-10.5"
```

Il est aussi possible d'utiliser **PostgreSQL** ou **SQLite** (base de données dans un fichier) mais il faudra dans ce cas effacer et recréer les fichiers de migrations.

Si notre serveur n'hébergerait qu'une seule application, dans un contexte plus concret, la racine du site devrait directement pointer sur le dossier `public` de l'application, ce qui fait que les autres répertoires (au-dessus) ne seraient naturellement pas accessibles par le client/navigateur. Cependant, comme nous utilisons une architecture particulière en TD (et sur le serveur `webinfo`) qui héberge plusieurs projets à la fois, vous devrez renforcer la sécurité des autres répertoires en interdisant leur accès en ajoutant un nouveau fichier `.htaccess` **à la racine de votre projet** qui contient l'instruction suivante :

```
Require all denied
```

Cela permettra notamment d'éviter que n'importe qui puisse lire le contenu de votre fichier `.env` (entre autres).

Ensuite, dans le fichier `public/.htaccess` (qui existe déjà), il faut ajouter cette ligne (par exemple, au début du fichier) pour donner les droits d'accès uniquement à ce dossier :

```
Require all granted
```

Enfin, il ne faut pas envoyer tous les dossiers sur le serveur. Vous pouvez notamment omettre :
* **vendor** (dépendances qui seront ré-installées rapidement)
* **var** (fichier de cache, logs, etc)
* Les éventuels fichiers relatifs à votre IDE (par exemple `.idea`).

Bref, normalement, tout ce qui ne devrait pas être versionné sur un dépôt **git** ! Le projet sera alors très léger et facilement transportable (via FTP, par exemple).

Si vous avez un dossier `public/assets`, il serait aussi judicieux de le supprimer avant le déploiement.

## Transfert des fichiers vers le serveur

Pour transférer le dossier contenant votre application vers `~/public_html` vous pouvez notamment utiliser un client **FTP** pour transférer les fichiers depuis votre machine vers `~/public_html`, à distance. Il est possible de faire cela en ligne de commande, ou bien simplement grâce aux logiciels **FileZilla** ou **gFtp**. Pour vous connecter, il faudra choisir comme protocole `SFTP`, comme hôte `ftpinfo.iutmontp.univ-montp2.fr`, comme port `22`, et vous devrez utiliser votre login et mot de passe habituels. Vous pouvez alors facilement déplacer des fichiers de votre machine vers votre `home`, et notamment vers le répertoire `public_html`.

Alternativement, il est aussi possible de vous connecter sur le serveur en **SSH** afin de récupérer le projet directement depuis votre session (depuis GitLab, par exemple). Pour la connexion via ce système, référez-vous à la section suivante.

## Configuration

Une fois votre projet transféré, il faudra accéder à votre session afin d'y exécuter des commandes.

Il existe deux solutions :
* Se connecter directement depuis un ordinateur d'une salle machine de l'IUT.
* Ou bien plus simplement, **se connecter à distance via SSH**. Pour cela, vous pouvez utiliser la commande `ssh` :
```sh
ssh login_IUT@162.38.222.93 -p 6666
```

Une fois dans votre session, vous y trouverez le dossier `public_html` où se situe votre projet.

Rendez-vous dans le dossier hébergeant votre projet (qui est normalement déjà prêt à être déployé, sans les fichiers superflus, avec les bons fichiers `.htaccess`, etc...)

Ensuite, on termine le travail en exécutant plusieurs commandes :

```bash
# Installation de toutes les dépendances
composer install

# Execution des migrations
php bin/console doctrine:migrations:migrate  

# Génération du fichier contenant les routes exposées (si FosJsRoutingBundle installé)
php bin/console fos:js-routing:dump --format=js --target=assets/routes/fos_routes.js --callback="export default  "

# Compilation des assets (et transfert vers public)
php bin/console asset-map:compile

# Nettoyage du cache
php bin/console c:c

# On modifie (-m) récursivement (-R) les droits r-x de l'utilisateur (u:) www-data
setfacl -R -m u:www-data:r-x ~/public_html

# On fait de même avec des droits par défaut (d:) (les nouveaux fichiers prendront ces droits)
setfacl -R -m d:u:www-data:r-x ~/public_html
```

Petite précision : on n'exécute pas la commande pour créer la base de données ici, car elle existe déjà (elle est nommée d'après votre login, sur le serveur du département). Cela est vrai pour les deux types de SGBD mis à disposition au département (MySQL ou PostgreSQL). Bien entendu, dans un contexte réel, pour un premier déploiement sur un serveur, il faudrait créer cette base.

Si vous n'utilisez pas la même type de base de données que lors du développement (par exemple, si vous utilisiez PostgreSQL lors du développement et MySQL lors de la mise en production), il faudra supprimer les migrations, et exécuter `php bin/console doctrine:migrations:diff` (afin d'en générer de nouvelles) avant d'effectuer la migration de la base.

Enfin, **si vous avez beaucoup d'erreurs que vous n'arrivez pas à résoudre et que vous souhaitez forcer la mise à jour de la base de données** vous pouvez éventuellement utiliser cette commande (mais seulement en dernier recours, il est déconseillé de l'utiliser, surtout en production) :

```bash
php bin/console doctrine:schema:update --force
```

Une fois que tout est en place, votre application sera accessible via cette URL : [https://webinfo.iutmontp.univ-montp2.fr/~votre_login/nom_application/public/](https://webinfo.iutmontp.univ-montp2.fr/~votre_login/nom_application/public/).

## Ressources

Vous trouverez toutes les informations à propos de la connexion aux [bases de données](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/bases-de-donnees/) sur [l'intranet](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet) du département informatique.

Des sources complémentaires sur comment se connecter en FTP et en SSH à `public_html` (pour y déposer des fichiers) :

* [https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/votre-espace-de-travail/](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/votre-espace-de-travail/)
* [https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/acces-aux-serveurs/](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/acces-aux-serveurs/)
* [https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/partager-public_html/](https://iutdepinfo.iutmontp.univ-montp2.fr/intranet/partager-public_html/)
* [https://docs.google.com/document/d/1rLb4QWt0uOxE8IuLqeUxjZTivMIA6KbLzieNvsSa_p0/edit#heading=h.l1xavd57lfgb](https://docs.google.com/document/d/1rLb4QWt0uOxE8IuLqeUxjZTivMIA6KbLzieNvsSa_p0/edit#heading=h.l1xavd57lfgb)

Concernant votre base de données `MySQL`, vous pouvez interagir avec via [phpMyAdmin](https://webinfo.iutmontp.univ-montp2.fr/my/).