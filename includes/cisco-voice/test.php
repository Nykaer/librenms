<?php
require_once "transport_http.inc.php";
require_once "api_cucm_axl.inc.php";
include "../common.php";

$debug = true;

$USER = "script";
$PASS = "script";
$HOST = '192.168.174.13';

$API = new api_cucm_axl();
$API->connect($USER, $PASS, array($HOST));

$RESULT = $API->getEndUser('adaniels');
print_r($RESULT);

?>