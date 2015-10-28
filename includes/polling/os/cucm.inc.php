<?php
/*
 * Cisco Unified Communications Operating System - UCOS
 */

require_once 'includes/cisco-voice/transport_http.inc.php';
require_once 'includes/cisco-voice/api_ucos_generic.inc.php';

$API = new api_ucos_generic();
// Grab the details UCOS requires.
$USER = get_dev_attrib($device, 'ucosaxl_user');
$PASS = get_dev_attrib($device, 'ucosaxl_pass');;

$API->connect($USER, $PASS, $device['hostname']);
$RESULT = $API->getProduct();

if ($RESULT ==! false) {
    $version = $RESULT['ProductVersion'];
}