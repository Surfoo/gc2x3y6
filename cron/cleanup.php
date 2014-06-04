<?php

/**
 * Vidage de la table des essais
 */
require dirname(__DIR__) . '/config.php';

$pdo->query('TRUNCATE targets;');
echo "Cleaned!\n";