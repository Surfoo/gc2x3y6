<?php

error_reporting(-1);
ini_set('display_errors', '1');

define('ROOT', __DIR__);

define('GCCODE', 'GC2X3Y6');
define('COORDINATES', 'N xx° xx.xxx E xxx° xx.xxx');
define('NB_ATTEMPTS', 1);

//API Geocaching
define('PRODUCTION', true);

if(!PRODUCTION) {
    define('OAUTH_KEY', '');
    define('OAUTH_SECRET', '');
    define('OAUTH_URL_TYPE', 'staging');
}
else {
    define('OAUTH_KEY', '');
    define('OAUTH_SECRET', '');
    define('OAUTH_URL_TYPE', 'live');
}

// Base de données
define('SERVER_NAME', 'localhost');
define('DB_DRIVER', 'mysql');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');

define('COOKIE_CHECK_FORMAT', '%d-%s-*secret_key_to_change_here*');
define('COOKIE_EXPIRE', 3600 * 24 * 30);

define('PAUSE_CHECKING', 5);

// Centre de la carte, coordonnées à mettre aussi dans le fichier bomb.js
define('CENTER_LAT', 48.856578); 
define('CENTER_LNG', 2.351828);

//Rayon dans lequel sera placé la bombe
define('RADIUS', 6000);

date_default_timezone_set('Europe/Paris');

session_start();

header('Content-Type: text/html; charset=UTF-8');

require 'vendor/autoload.php';

try {
    $pdo = new PDO(DB_DRIVER . ':host='. SERVER_NAME . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
}
catch(Exception $e) {
    echo "Connexion impossible : ". $e->getMessage();
    die();
}

require ROOT . '/utils.php';
