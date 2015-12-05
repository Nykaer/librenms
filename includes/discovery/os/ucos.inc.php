<?php
/*
 * Cisco Unified Communications Operating System - UCOS
 */

if (strstr($sysObjectId, '.1.3.6.1.4.1.9.1.1348')) {
    // So, it looks like we have a UCOS appliance, lets determine which one.
    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_ucos_ast.inc.php';

    $API = new api_ucos_ast();
    // Grab the details UCOS requires.
    $USER = get_dev_attrib($device, 'ucosaxl_user');
    $PASS = get_dev_attrib($device, 'ucosaxl_pass');;

    $API->connect($USER, $PASS, $device['hostname']);
    $RESULT = $API->getProduct();

    // Lets set a default.
    $os = "ucos";

    if ($RESULT ==! false) {
        // We have a UCOS Product
        if ($RESULT['DeploymentID'] == "callmanager") {
            $os = "cucm";
            $version = $RESULT['ProductVersion'];
        }
    }

}
