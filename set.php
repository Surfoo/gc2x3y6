<?php

/**
 * Ajoute une cible jouée par l'utilisateur
 */
require __DIR__ . '/config.php';

if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}
if(!array_key_exists('lat', $_POST) || !array_key_exists('lng', $_POST) || !array_key_exists('radius', $_POST) ||
    !isset($_SESSION[GCCODE]['user_name']) || empty($_SESSION[GCCODE]['user_name']) ||
    !isset($_SESSION[GCCODE]['user_id']) || empty($_SESSION[GCCODE]['user_id'])) {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

$user_latitude  = (float) $_POST['lat'];
$user_longitude = (float) $_POST['lng'];
$user_radius    = (int) round($_POST['radius'], 0);
$user_id        = (int) $_SESSION[GCCODE]['user_id'];
$user_name      = $_SESSION[GCCODE]['user_name'];

//Check du nombre de coups déjà effectué par le joueur
$sth = $pdo->prepare('SELECT COUNT(*) AS nb_target FROM targets WHERE user_id = :user_id');
$sth->execute(array(':user_id' => $user_id));
$row = $sth->fetchObject();
$nb_target = (int) $row->nb_target;
if ($nb_target >= NB_ATTEMPTS) {
    echo json_encode(array('success' => false, 'message' => 'Bien tenté ;-)'));
    exit(0);
}

//Récupération des coordonnées de la bombe
$query = 'SELECT `lat`, `lng`'.
         'FROM `bombs` '.
         'WHERE `created_on` = CURDATE();';

$result = $pdo->query($query);
$bomb = $result->fetchObject();
if (is_null($bomb)) {
    echo json_encode(array('success' => false, 'message' => 'Erreur, pas de bombe implantée.'));
    exit(0);
}

$distance = haversineGreatCircleDistance($user_latitude, $user_longitude, $bomb->lat, $bomb->lng);
$user_inside = ($distance < $user_radius) ? true : false;

$sth = $pdo->prepare('INSERT INTO targets (user_id, user_name, lat, lng, radius, inside, created_on)'.
                     'VALUES(:user_id, :user_name, :user_latitude, :user_longitude, :user_radius, :user_inside, NOW())');
$sth->execute(array(':user_id'        => $user_id,
                    ':user_name'      => $user_name,
                    ':user_latitude'  => $user_latitude,
                    ':user_longitude' => $user_longitude,
                    ':user_radius'    => $user_radius,
                    ':user_inside'    => (int) $user_inside));

$sth = $pdo->prepare('INSERT INTO logs (user_id, user_name, lat, lng, radius, inside, created_on)'.
                     'VALUES(:user_id, :user_name, :user_latitude, :user_longitude, :user_radius, :user_inside, NOW())');
$sth->execute(array(':user_id'        => $user_id,
                    ':user_name'      => $user_name,
                    ':user_latitude'  => $user_latitude,
                    ':user_longitude' => $user_longitude,
                    ':user_radius'    => $user_radius,
                    ':user_inside'    => (int) $user_inside));

echo json_encode(array('success' => true, 'inside' => $user_inside));
