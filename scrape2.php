<?php

require_once './vendor/autoload.php';
require_once './KSLScraper2.php';

if (!$argv[1]) {
    throw new Exception('No search string provided. Nothing to search.');
}
$searchString = rawurlencode($argv[1]);

$email = isset($argv[2])
    ? $argv[2]
    : null;

(new KSLScraper($searchString, $email))->go();
