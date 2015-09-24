<?php
include "transport_http.inc.php";
//include "api_cucm_axl.inc.php";
include "api_cucm_perfmon.inc.php";
include "../common.php";

$debug = true;

$USER = "script";
$PASS = "script";
$HOST = array('192.168.174.13');

$API = new api_cucm_perfmon();
$API->connect($USER, $PASS, $HOST);

$RESULT = $API->addCounter(array('\\\\192.168.174.13\Cisco CallManager\CallsActive'));

print_r($RESULT)."\n";

?>