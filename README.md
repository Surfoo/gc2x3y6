GC2X3Y6 - Bomb Alert
====================

Mystery cache available here : [http://geocaching.vaguelibre.net/GC2X3Y6/](http://geocaching.vaguelibre.net/GC2X3Y6/)

Installation
============

You need to install [composer](https://getcomposer.org/doc/00-intro.md#system-requirements), and [install the dependency](https://getcomposer.org/doc/00-intro.md#using-composer) before playing :)

Fill the file config.php
========================

Values to define :
* GCCODE      : GCcode of your cache
* COORDINATES : final coordinates
* NB_ATTEMPTS : warning, there is an UNIQUE KEY on the database schema
* OAUTH_KEY   : for staging and live
* OAUTH_SECRET: for staging and live
* SERVER_NAME : localhost by default
* DB_DRIVER   : mysql by default
* DB_USER
* DB_PASSWORD
* DB_NAME
* COOKIE_CHECK_FORMAT : change *secret_key_to_change_here* by a random string
* CENTER_LAT  : latitude of the center of the map
* CENTER_LNG  : longitude of the center of the map
* RADIUS      : Radius of the circle to plant the bomb
