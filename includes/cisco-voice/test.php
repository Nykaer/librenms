<?php
require_once "transport_http.inc.php";
require_once "api_cucm_perfmon.inc.php";
include "../common.php";

$debug = true;

$USER = "script";
$PASS = "script";
$HOST = array('192.168.174.13');

$API = new api_cucm_perfmon();
$API->connect($USER, $PASS, $HOST);

//$RESULT = $API->collectCounterData('192.168.174.13','Cisco CallManager');
//print_r($RESULT);

/*$RESULT = $API->listInstance('192.168.174.13','Cisco SIP');

$ARRAY = array();
foreach ($RESULT as $VALUE) {
    $ARRAY[] = '\\\\192.168.174.13\Cisco SIP('.$VALUE.')\CallsActive';
}
print_r ($ARRAY);
*/

$ARRAY[] = '\\\\192.168.174.13\Cisco CallManager\CallsActive';
if ($API->addCounter($ARRAY)) {
    echo "Counter(s) Added\n";
    $RESULT = $API->collectSessionData();

    if ($RESULT !== false) {
        print_r($RESULT);
        $RESULT = $API->closeSession();
    }
}

?>