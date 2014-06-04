<?php

/**
 * Récupère le nombre de tir déjà fait par le joueur
 */
require __DIR__ . '/config.php';

if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

if (!isset($_SESSION[GCCODE]['user_name']) || empty($_SESSION[GCCODE]['user_name'])) {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}
if (!isset($_SESSION[GCCODE]['user_id']) || empty($_SESSION[GCCODE]['user_id'])) {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

$user_id  = (int) $_SESSION[GCCODE]['user_id'];

$query = 'SELECT COUNT(*) AS nb_target FROM targets WHERE user_id = ' . $user_id;
$result = $pdo->query($query);
$row = $result->fetchObject();
$nb_target = (int) $row->nb_target;

$message = false;

//Affichage des coordonnées si le joueur a déjà joué.
$query = 'SELECT COUNT(user_id) AS count FROM checks WHERE user_id = ' . $user_id;
$result = $pdo->query($query);
$row = $result->fetchObject();
if($row->count > 0) {
    $message = "Bravo, vous avez déjà trouvé la bombe !\n\nPour rappel, voilà les coordonnées de la cache :\n" . COORDINATES;
}
echo json_encode(array('success' => true, 'max_target' => NB_ATTEMPTS, 'nb_target' => $nb_target, 'message' => $message));
