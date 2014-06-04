<?php

/**
 * Récupère la liste des cibles jouées pour les afficher sur la carte
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

$query = 'SELECT `lat`, `lng`, `radius`, `inside` '.
         'FROM `targets` '.
         'ORDER BY `radius` DESC';

$targets = $pdo->query($query);

$dom = new DOMDocument('1.0');
$node = $dom->createElement('markers');
$parnode = $dom->appendChild($node);
$dom->formatOutput = true;

foreach ($targets as $target) {
    $node = $dom->createElement("marker");
    $newnode = $parnode->appendChild($node);
    $newnode->setAttribute('lat', $target['lat']);
    $newnode->setAttribute('lng', $target['lng']);
    $newnode->setAttribute('radius', $target['radius']);
    $newnode->setAttribute('inside', $target['inside']);
}

header('Content-type: text/xml');
echo $dom->saveXML();
