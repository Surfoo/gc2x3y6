<?php

/**
 * Vérification des coordonnées envoyées par l'utilisateur
 */
require __DIR__ . '/config.php';

$_SESSION[GCCODE]['current_check'] = time();


if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

if(!array_key_exists('lat', $_POST) || !array_key_exists('lng', $_POST) ||
   !isset($_SESSION[GCCODE]['user_name']) || empty($_SESSION[GCCODE]['user_name']) ||
   !isset($_SESSION[GCCODE]['user_id']) || empty($_SESSION[GCCODE]['user_id'])) {
    header("HTTP/1.0 400 Bad Request");
    exit(0);
}

if (isset($_SESSION[GCCODE]['last_check']) && 
    ($_SESSION[GCCODE]['current_check'] - $_SESSION[GCCODE]['last_check']) < PAUSE_CHECKING) {
    echo json_encode(array('success' => false, 'message' => 'Bien tenté ;-)'));
    exit(0);
}

$_SESSION[GCCODE]['last_check'] = $_SESSION[GCCODE]['current_check'];

//Check si la bombe a été désamorcée
$query = "SELECT COUNT(*) AS nb_defused
          FROM checks
          INNER JOIN bombs
          ON checks.id_bomb = bombs.id
          AND DATE_FORMAT(bombs.created_on, '%Y-%m-%d') = CURDATE()";
$result = $pdo->query($query);
$defused = $result->fetchObject();

//check du nombre de cible testé
$query = 'SELECT COUNT(*) AS nb_target
          FROM targets
          WHERE user_id = ' . (int) $_SESSION[GCCODE]['user_id'];
$result = $pdo->query($query);
$targeted = $result->fetchObject();

// Si la bombe a été trouvée et que le joueur n'a rien joué, il ne peux pas trouver la bombe,
// il sera redirigé
if($defused->nb_defused > 0 && $targeted->nb_target == 0) {
    echo json_encode(array('success' => false, 'redirect' => true));
    exit(0);
}

sleep(PAUSE_CHECKING);

//Récupération des coordonnées de la bombe
$query = 'SELECT `id`, `lat`, `lng`'.
         'FROM `bombs` '.
         'WHERE `created_on` = CURDATE();';
$result = $pdo->query($query);
$bomb = $result->fetchObject();
if (is_null($bomb)) {
    echo json_encode(array('success' => false, 'message' => 'Erreur, pas de bombe implantée.'));
    exit(0);
}

$lat = (float) $_POST['lat'];
$lng = (float) $_POST['lng'];
$distance = haversineGreatCircleDistance($lat, $lng, $bomb->lat, $bomb->lng);
if ($distance > 1) {
    echo json_encode(array('success' => false));
    exit(0);
}
$sth = $pdo->prepare('INSERT INTO `checks` (user_id, user_name, id_bomb, created_on)
                      VALUES(:user_id, :user_name, :bomb_id, NOW());');
$sth->execute(array(':user_id' => (int) $_SESSION[GCCODE]['user_id'],
                    ':user_name' => $_SESSION[GCCODE]['user_name'],
                    ':bomb_id' => (int) $bomb->id));


//Récupération des joueurs ayant déjà trouvé la bombe
$query = 'SELECT user_name FROM `checks` GROUP BY user_id ORDER BY created_on;';
$minesweepers = $pdo->query($query);

$rows = $minesweepers->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $user) {
    $data[] = $user['user_name'];
}

$count_winners = count($data);

define('NB_COLUMNS'  ,   3);
define('COLUMN_WIDTH', 170);

$width = NB_COLUMNS * COLUMN_WIDTH;
$height = 35 + 17 * ceil($count_winners/NB_COLUMNS);

putenv('GDFONTPATH=/usr/share/fonts/truetype/ttf-dejavu/');

$im    = imagecreatetruecolor($width, $height);

$white = imagecolorallocate($im, 255, 255, 255);
$grey  = imagecolorallocate($im, 128, 128, 128);
$black = imagecolorallocate($im, 0, 0, 0);

imagefilledrectangle($im, 0, 0, $width, $height, $white);

$text = 'Liste officielle :';
$font = 'DejaVuSans.ttf';

//Titre
// Ajout d'ombres au texte
imagettftext($im, 16, 0, 11, 26, $grey, $font, $text);
// Ajout du texte
imagettftext($im, 16, 0, 10, 25, $black, $font, $text);

$data = array_chunk($data, ceil($count_winners/NB_COLUMNS), true);
foreach ($data as $idx_column => $columns) {
    $y_start = 47;
    $x_start = 10 + $idx_column * COLUMN_WIDTH;
    foreach($columns as $index => $username) {
        imagettftext($im, 9, 0, $x_start, $y_start, $black, $font, sprintf('%02d.', $index + 1) . ' ' . $username);
        $y_start += 17;
    }
}
imagepng($im, 'img/minesweepers.png', 8);

echo json_encode(array('success' => true, 'coordinates' => COORDINATES));
exit(0);
