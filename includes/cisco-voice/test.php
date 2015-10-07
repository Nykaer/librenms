<?php
require_once "transport_http.inc.php";
require_once "api_cucm_perfmon.inc.php";
include "../common.php";

$debug = true;

$USER = "script";
$PASS = "script";
$HOST = '192.168.174.13';

$API = new api_cucm_perfmon();
$API->connect($USER, $PASS, array($HOST));

$RESULT = $API->collectCounterData('192.168.174.13','Cisco SIP');
//print_r($RESULT);

/*
$RESULT = $API->listInstance('192.168.174.13','Cisco Locations LBM');

print_r($RESULT);
$ARRAY = array();
foreach ($RESULT as $VALUE) {
    if (preg_match('/->/', $VALUE['Name'])) {
        $ARRAY[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$VALUE['Name'].')\BandwidthMaximum';
        $ARRAY[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$VALUE['Name'].')\ImmersiveVideoBandwidthMaximum';
        $ARRAY[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$VALUE['Name'].')\VideoBandwidthMaximum';
    }
}
//print_r ($ARRAY);

//$ARRAY[] = '\\\\192.168.174.13\Cisco CallManager\CallsActive';
if ($API->addCounter($ARRAY)) {
    echo "Counter(s) Added\n";
    $RESULT = $API->collectSessionData();
*/
    if ($RESULT !== false) {
        foreach ($RESULT as $VALUE) {
            print $VALUE['Value']." - ".$VALUE['Name']."\n";
        }
//        $RESULT = $API->closeSession();
//    }
}
/*
*/

?>