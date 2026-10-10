<?php

error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$target = $argv[1];
$markerFile = $argv[2];

require $target;

if (is_string($markerFile) && $markerFile !== '') {
    file_put_contents($markerFile, 'reached');
}

echo 'done';
