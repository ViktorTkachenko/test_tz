#!/usr/bin/php
<?php
ini_set('error_reporting', E_ALL);
error_reporting(-1);
ini_set("display_errors", 1);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '2048M');

include 'Classes' . DIRECTORY_SEPARATOR . 'IpAustralia.php';
include 'Classes' . DIRECTORY_SEPARATOR . 'PhpQuery.php';


$parse = new IpAustralia($argc, $argv);
$parse->printTradeMarksList();

