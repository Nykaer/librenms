<?php
require_once "transport_http.inc.php";
require_once "api_ucos_generic.inc.php";
include "../common.php";

$debug = true;

$USER = "script";
$PASS = "script";
$HOST = '192.168.174.13';

$API = new api_ucos_generic();
$API->connect($USER, $PASS, $HOST);

$RESULT = $API->getProduct();
print_r($RESULT);

?>