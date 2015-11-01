<?php
require_once "transport_http.inc.php";
require_once "api_ucos_ast.inc.php";
include "../common.php";

$debug = true;

$USER = "script";
$PASS = "script";
$HOST = '192.168.174.13';

$API = new api_ucos_ast();
$API->connect($USER, $PASS, $HOST);

//$RESULT = $API->getClusterInfo();
//$RESULT = $API->getRegisteredDevices();
$RESULT = $API->getServices();
print_r($RESULT);

?>