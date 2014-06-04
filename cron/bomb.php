<?php

require dirname(__DIR__) . '/config.php';

// http://www.cosmocode.de/en/blog/gohr/2010-06/29-calculate-a-destination-coordinate-based-on-distance-and-bearing-in-php
/**
 * Calculate a new coordinate based on start, distance and bearing
 *
 * @param $start array - start coordinate as decimal lat/lon pair
 * @param $dist  float - distance in meters
 * @param $brng  float - bearing in degrees (compass direction)
 */
function geo_destination($start, $dist, $brng) {
    $lat1 = deg2rad($start[0]);
    $lon1 = deg2rad($start[1]);
    $dist = $dist/6371000; //Earth's radius in km
    $brng = deg2rad($brng);
    $lat2 = asin(sin($lat1)*cos($dist) + cos($lat1)*sin($dist)*cos($brng));
    $lon2 = $lon1 + atan2(sin($brng)*sin($dist)*cos($lat1), cos($dist)-sin($lat1)*sin($lat2));
    $lon2 = fmod(($lon2 + 3 * M_PI), (2 * M_PI)) - M_PI;

    return array('lat' => toDeg($lat2),
                 'lng' => toDeg($lon2));
}

function toDeg($rad) {
    return $rad * 180 / M_PI;
}

$distance = mt_rand(0, RADIUS);
$angle    = mt_rand(0, 36000)/100;

$bomb = geo_destination(array(CENTER_LAT, CENTER_LNG), $distance, $angle);

$query = 'SELECT DATE_ADD(MAX(created_on), INTERVAL 1 day) AS next_date FROM `bombs`';
$result = $pdo->query($query);
$row = $result->fetchObject();
if (is_null($row->next_date)) {
    $row->next_date = date('Y-m-d');
}
$query = 'INSERT INTO bombs (lat, lng, created_on) VALUES(:lat, :lng, :next_date);';
$sth = $pdo->prepare($query);
$sth->execute(array(':lat' => $bomb['lat'], ':lng' => $bomb['lng'], 'next_date' => $row->next_date));


echo "The bomb has been planted!\n";