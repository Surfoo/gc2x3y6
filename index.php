<?php
require __DIR__ . '/config.php';

use Geocaching\OAuth\OAuth;
use Geocaching\Api\GeocachingApi;

/**
 * Authentification OAuth
 */
$callback_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']).'/';
if (array_key_exists('authenticate', $_GET)) {
    $consumer = new OAuth(OAUTH_KEY, OAUTH_SECRET, $callback_url, OAUTH_URL_TYPE);
    //$consumer->setLogging('/tmp/');
    $token = $consumer->getRequestToken();
    $_SESSION[GCCODE]['REQUEST_TOKEN'] = serialize($token);
    $consumer->redirect();
}
//Go back from Geocaching OAuth URL, retrieve the token
if (!empty($_GET) && isset($_SESSION[GCCODE]['REQUEST_TOKEN'])) {
    $consumer = new OAuth(OAUTH_KEY, OAUTH_SECRET, $callback_url, OAUTH_URL_TYPE);
    //$consumer->setLogging('/tmp/');
    $token = $consumer->getAccessToken($_GET, unserialize($_SESSION[GCCODE]['REQUEST_TOKEN']));
    $_SESSION[GCCODE]['ACCESS_TOKEN'] = $token['oauth_token'];
    setcookie('ACCESS_TOKEN', $token['oauth_token'], time() + COOKIE_EXPIRE);
    header('Location: index.php');
    exit(0);
}

$display_map = false;

//Test du cookie à la réouverture du navigateur
if (!array_key_exists('ACCESS_TOKEN', $_COOKIE) || empty($_COOKIE['ACCESS_TOKEN'])) {
    //echo "pas identifié";
    $authentified = false;
} else {
    $api = new GeocachingApi($_COOKIE['ACCESS_TOKEN'], PRODUCTION);

    if(!array_key_exists('user_checking', $_COOKIE) || empty($_COOKIE['user_checking'])) {
        //echo "pas de cookie";
        try {
            $user = $api->getYourUserProfile(array('GeocacheData' => true, 'PublicProfileData' => true));
        } catch (Exception $e) {
            resetSession('api');
        }

        $_SESSION[GCCODE]['user_name']    = $user->Profile->User->UserName;
        $_SESSION[GCCODE]['user_id']      = $user->Profile->User->Id;
        $_SESSION[GCCODE]['ACCESS_TOKEN'] = $_COOKIE['ACCESS_TOKEN'];
        setcookie('user_checking', sha1(sprintf(COOKIE_CHECK_FORMAT, $_SESSION[GCCODE]['user_id'], $_SESSION[GCCODE]['user_name'])), time() + COOKIE_EXPIRE);

    } elseif(!isset($_SESSION[GCCODE]['user_id']) && array_key_exists('user_checking', $_COOKIE) && !empty($_COOKIE['user_checking'])) {
        //echo 'check des données des cookies';
        try {
            $user = $api->getYourUserProfile();
        } catch (Exception $e) {
            resetSession('api');
        }

        $hash = sha1(sprintf(COOKIE_CHECK_FORMAT, $user->Profile->User->Id, $user->Profile->User->UserName));

        if ($hash != $_COOKIE['user_checking']) {
            resetSession();
        }

        // echo "cookie";
        $_SESSION[GCCODE]['user_name']    = $user->Profile->User->UserName;
        $_SESSION[GCCODE]['user_id']      = $user->Profile->User->Id;
        $_SESSION[GCCODE]['ACCESS_TOKEN'] = $_COOKIE['ACCESS_TOKEN'];
        setcookie('user_checking', sha1(sprintf(COOKIE_CHECK_FORMAT, $_SESSION[GCCODE]['user_id'], $_SESSION[GCCODE]['user_name'])), time() + COOKIE_EXPIRE);
    }
    else {
        //echo 'check session';
        $hash = sha1(sprintf(COOKIE_CHECK_FORMAT, $_SESSION[GCCODE]['user_id'], $_SESSION[GCCODE]['user_name']));

        if ($hash != $_COOKIE['user_checking']) {
            resetSession();
        }
    }

    $authentified = true;

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

    $display_map = true;
    // Si la bombe a été trouvée et que le joueur n'a rien joué, pas d'affichage de la carte
    if($defused->nb_defused > 0 && $targeted->nb_target == 0) {
        $display_map = false;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>GC2X3Y6 - Mystery Game : Alerte à la bombe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" media="all" />
    <link rel="stylesheet" href="bomb.css" media="all" />
</head>
<body>
    <div class="row">
        <div class="col-md-4">
            <h1><a href="http://coord.info/GC2X3Y6" onclick="window.open(this.href);return false;">GC2X3Y6<br/> Mystery Game : Alerte à la bombe</a></h1>
            <?php
                if (!$authentified) {
            ?>
                <div class="alert alert-warning" id="authenticate">
                    <p>Bonjour,<br />
                      L'accès au radar n'est autorisé qu'aux personnes de l'<strong>Agence GC</strong>, merci de vous identifier en cliquant sur le bouton ci dessous.
                    </p>
                    <p class="link"><strong><a href="?authenticate" class="btn btn-warning">S'identifier</a></strong></p>
                </div>
            <?php
                } else {
            ?>
              <div class="alert alert-info" id="infos">
                <p>Bonjour agent <?php echo $_SESSION[GCCODE]['user_name']; ?>,<br />
                <p>Pour trouver et désamorcer la bombe, vous devrez utiliser le radar sur la carte. <br />
                En cliquant sur la carte, votre zone de recherche apparaît en noir,
                   et peut être déplacée sur la carte avec le marqueur noir central.<br />
                   Vous pouvez réduire ou agrandir son champ de vision en glissant le curseur <img src="img/resize-off.png" alt="" /><p>
                <p>Une fois votre zone délimitée, validez votre choix en double cliquant sur le marqueur noir.<br />
                Si elle devient rouge, la bombe n'est pas dans la zone. Dans le cas contraire, elle devient verte.<p>
                <p><strong>Attention, vous ne pouvez utiliser votre radar qu'une seule fois.</strong><p>
                <p>Une fois que vous avez trouvé l'emplacement supposé de la bombe, placez vous au dernier niveau de zoom (le plus détaillé) et 
                faites un clic droit pour la désarmorcer. Le nombre d'essai n'est pas limité.<br />
                Si vous avez réussi, un message apparaîtra au bout de quelques secondes.</p>
              </div>

                <?php
                    if($defused && $targeted && $defused->nb_defused > 0 && $targeted->nb_target > 0) {
                        echo '<p class="alert alert-warning"><strong>La bombe a été trouvée et désamorcée par un de vos collègues !<br />'.
                             'Maintenant, trouvez les coordonnées de la bombe et vous serez récompensé.</strong></p>';
                    }
                ?>

                <p id="check">Coordonnées testées : <br /><span class="coordinates"></span></p>
            <?php
                }
            ?>
            <p id="powered" class="navbar-fixed-bottom col-md-4 hidden-xs hidden-sm hidden-md"><a href="http://www.geocaching.com/live" onclick="window.open(this.href);return false;">Powered by Geocaching Live<br /><img src="img/live.png" alt="Geocaching API"></a></p>
            </div>
        <div class="col-md-8">
            <?php
                if(array_key_exists(GCCODE, $_SESSION) && isset($_SESSION[GCCODE]['ACCESS_TOKEN']) && $display_map) {
                    echo '<div id="map-canvas"></div>'."\n";
                }
                if ($authentified && isset($display_map) && !$display_map) {
                    echo '<div class="alert alert-warning" style="margin-top: 3em;font-size: 1.4em;">
                    La bombe a été trouvée et désarmorcée ! <br />
                    Seul les agents ayant participé aux recherches sont récompensés. <br /><br />
                    Une nouvelle bombe sera placée dès demain.</div>'."\n";
                }
            ?>
        </div>
    </div>
  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
  <script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
<?php
    if (isset($_SESSION[GCCODE]['ACCESS_TOKEN']) && $display_map) {
        echo '<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false&amp;language=fr"></script>'."\n";
        echo '<script type="text/javascript" src="js/bomb.js?20140604"></script>'."\n";
    }
?>
</body>
</html>
