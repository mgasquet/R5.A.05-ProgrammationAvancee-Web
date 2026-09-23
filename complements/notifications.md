---
title: Des notifications plus élégantes
subtitle:
layout: tutorial
lang: fr
---

Lorsqu' développe une application avec Symfony, il existe de très nombreux composants qu'il est possible d'installer afin d'ajouter de nouvelles fonctionnalités plus facilement ou bien, pour améliorer l'ergonomie de l'interface.

Dans l'application **The Feed**, nous utilisons actuellement le système natif de messages flash dans le but d'émettre et afficher des notifications pour informer l'utilisateur. Dans ce mini-tutoriel, nous vous proposons de voir comment en installer un dans le but d'améliorer le style de nos notifications.

Le composant que nous allons installer s'appelle [PHPFlasher](https://php-flasher.io/symfony/) et permet d'afficher des notifications customisées.

Son installation est très aisée :

```bash
composer require php-flasher/flasher-symfony
php bin/console flasher:install -c
```

Si la racine de notre site n'est pas directement sur localhost, il faut éditer un paramètre pour indiquer le sous-chemin de l'application. Par exemple, dans le cas de nos TDs, il s'agit de `/the_feed/public`. Le fichier à éditer est `src/config/packages/flasher.yaml` :

```yaml
#src/config/packages/flasher.yaml
flasher:
    ...

    public_path: '/the_feed/public'
```

Il faudra bien sûr adapter ce paramètre, lors du déploiement sur un serveur en production (comme `webinfo`).

Il y a d'autres paramètres qu'il peut être utile de modifier, afin de customiser l'affichage.

Toutes les options de configuration peuvent être trouvées [sur cette page](https://php-flasher.io/symfony/).

Une fois installé, l'utilisation de la librairie est extrêmement simple. Une fonction `flash` est accessible dans toute l'application (à utiliser à la place de notre système de flash natif à Symfony).

Par exemple, dans un contrôleur :

```php
final class ExempleController extends AbstractController
{
    #[Route('/exemple', name: "exemple", methods: ["GET", "POST"])]
    public function exemple(...) : Response {
        //Message de succès
        flash()->success("Exemple!");

        //Message d'erreur
        flash()->error("Exemple!");

        //Message d'avertissement
        flash()->warning("Exemple!");

        //Message d'information
        flash()->info("Exemple!");
    }
}
```

Et le message s'affichera lors du chargement de la page.

Il est bien sûr possible de customiser tout un tas d'options (durée d'affichage, position, etc...)

```php
final class ExempleController extends AbstractController
{
    #[Route('/exemple', name: "exemple", methods: ["GET", "POST"])]
    public function exemple(...) : Response {
        //S'affichera en bas à droite, pendant trois secondes.
        flash()->options(["position" => "bottom-right", "timeout" => 3000])->success("Exemple!");
    }
}
```

Il est aussi possible d'utiliser un service dédié injecté à la place de la fonction `flash` :

```php
use Flasher\Prime\FlasherInterface;

final class ExempleController extends AbstractController
{
    #[Route('/exemple', name: "exemple", methods: ["GET", "POST"])]
    public function exemple(FlasherInterface $flasher) : Response {
        //S'affichera en bas à droite, pendant trois secondes.
        $flasher->options(["position" => "bottom-right", "timeout" => 3000])->success("Exemple!");
    }
}
```

Vous pourrez retrouver la documentation complète de cet outil [ici](https://php-flasher.io/symfony/).

Autre fait intéressant : par défaut, la librairie traduit tous les messages flashs ajoutés par le système natif de Symfony, vers les messages flashs de la librairie. Si ce système vous intéresse, il faut supprimer la section qui lit et consomme les messages flashs dans `base.html.twig` et configurer globalement `flasher` pour adapter la manière dont sont affichées les notifications.

Attention, la librairie ne fonctionne pas très bien si vous souhaitez ajouter un message flash avant de renvoyer des **streams** ou **fragments** de **Turbo**, que nous abordons dans le TD3 (car le script correspondant n'est pas rechargé, vu que seul un bout de la page est envoyé). Il est cependant possible d'adapter ce système pour que cela fonctionne, au besoin.
