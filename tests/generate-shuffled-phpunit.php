<?php

error_reporting(-1);
$base = dirname(__FILE__);
$manifest = require $base . '/expected-suite.php';
$files = $manifest['files'];
$paths = array();
foreach ($files as $file) {
    $paths[] = $base . '/' . substr($file, strlen('tests/'));
}
mt_srand(20261010);
for ($i = count($paths) - 1; $i > 0; --$i) {
    $j = mt_rand(0, $i);
    $tmp = $paths[$i];
    $paths[$i] = $paths[$j];
    $paths[$j] = $tmp;
}
$out = $argv[1];
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<phpunit bootstrap="' . $base . '/bootstrap.php"' . "\n";
$xml .= '         colors="true"' . "\n";
$xml .= '         convertErrorsToExceptions="true"' . "\n";
$xml .= '         convertNoticesToExceptions="true"' . "\n";
$xml .= '         convertWarningsToExceptions="true"' . "\n";
$xml .= '         beStrictAboutTestsThatDoNotTestAnything="true"' . "\n";
$xml .= '         beStrictAboutOutputDuringTests="true">' . "\n";
$xml .= '    <testsuites><testsuite name="module-tests">' . "\n";
foreach ($paths as $path) {
    $xml .= '            <file>' . $path . '</file>' . "\n";
}
$xml .= '        </testsuite></testsuites></phpunit>' . "\n";
file_put_contents($out, $xml);
