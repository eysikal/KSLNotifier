<?php

require_once './vendor/autoload.php';
require_once './KSLScraper.php';

if (!$argv[1]) {
    throw new Exception('No search string provided. Nothing to search.');
}

$searchString = rawurlencode($argv[1]);

(new KSLScraper())->go($searchString);