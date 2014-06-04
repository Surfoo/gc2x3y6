<?php

function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo   = deg2rad($latitudeTo);
    $lonTo   = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

    return $angle * $earthRadius;
}

function resetSession($param = '') {
    setcookie('ACCESS_TOKEN', '', time()-3600);
    setcookie('user_checking', '', time()-3600);
    setcookie(session_name(), '', time()-3600);
    session_destroy();
    $url = empty($param) ? 'index.php' : 'index.php?p=' . urlencode($param);
    header('Location: ' . $url);
    exit(0);
}
