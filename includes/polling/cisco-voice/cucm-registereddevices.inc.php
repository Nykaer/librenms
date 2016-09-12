<?php
/*
 * LibreNMS module to Graph registered devices on a Cisco CallManager Server
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

if ($device['os'] == "cucm") {

    $MODULE = 'CUCM-RegisteredDevices';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_ucos_ast.inc.php';

    // Grab the details UCOS requires.
    $USER = get_dev_attrib($device, 'ucosaxl_user');
    $PASS = get_dev_attrib($device, 'ucosaxl_pass');;
    $HOST = get_dev_attrib($device, 'ucosaxl_host');

    $API = new api_ucos_ast();
    $API->connect($USER, $PASS, $HOST);

    $RESULT = $API->getRegisteredDevices();

    if (isset($RESULT['RegisteredPhone'])) {
        $rrd_name = $module;
        unset($fields);

        $rrd_def = array(
            'DS:phone-total:GAUGE:600:0:U',
            'DS:phone-sip:GAUGE:600:0:U',
            'DS:phone-sccp:GAUGE:600:0:U',
            'DS:phone-partial:GAUGE:600:0:U',
            'DS:phone-failed:GAUGE:600:0:U',
            'DS:gw-total:GAUGE:600:0:U',
            'DS:gw-fxs:GAUGE:600:0:U',
            'DS:gw-fxo:GAUGE:600:0:U',
            'DS:gw-t1cas:GAUGE:600:0:U',
            'DS:gw-pri:GAUGE:600:0:U',
            'DS:mr-total:GAUGE:600:0:U',
            'DS:mr-moh:GAUGE:600:0:U',
            'DS:mr-mtp:GAUGE:600:0:U',
            'DS:mr-xcode:GAUGE:600:0:U',
            'DS:mr-cfb:GAUGE:600:0:U',
            'DS:h323-total:GAUGE:600:0:U',
        );

        if (isset($RESULT['RegisteredPhone']["@attributes"]['Total'])) {
            $fields['phone-total'] = $RESULT['RegisteredPhone']["@attributes"]['Total'];
        } else {
            $fields['phone-total'] = "U";
        }

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalSIP'])) {
            $fields['phone-sip'] = $RESULT['RegisteredPhone']["@attributes"]['TotalSIP'];
        } else {
            $fields['phone-sip'] = "U";
        }

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalSCCP'])) {
            $fields['phone-sccp'] = $RESULT['RegisteredPhone']["@attributes"]['TotalSCCP'];
        } else {
            $fields['phone-sccp'] = "U";
        }

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalPartiallyRegistered'])) {
            $fields['phone-partial'] = $RESULT['RegisteredPhone']["@attributes"]['TotalPartiallyRegistered'];
        } else {
            $fields['phone-partial'] = "U";
        }

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalFailedAttempts'])) {
            $fields['phone-failed'] = $RESULT['RegisteredPhone']["@attributes"]['TotalFailedAttempts'];
        } else {
            $fields['phone-failed'] = "U";
        }

        if (isset($RESULT['RegisteredGateway']["@attributes"]['Total'])) {
            $fields['gw-total'] = $RESULT['RegisteredGateway']["@attributes"]['Total'];
        } else {
            $fields['gw-total'] = "U";
        }

        if (isset($RESULT['RegisteredGateway']["@attributes"]['FXS'])) {
            $fields['gw-fxs'] = $RESULT['RegisteredGateway']["@attributes"]['FXS'];
        } else {
            $fields['gw-fxs'] = "U";
        }

        if (isset($RESULT['RegisteredGateway']["@attributes"]['FXO'])) {
            $fields['gw-fxo'] = $RESULT['RegisteredGateway']["@attributes"]['FXO'];
        } else {
            $fields['gw-fxo'] = "U";
        }

        if (isset($RESULT['RegisteredGateway']["@attributes"]['T1CAS'])) {
            $fields['gw-t1cas'] = $RESULT['RegisteredGateway']["@attributes"]['T1CAS'];
        } else {
            $fields['gw-t1cas'] = "U";
        }

        if (isset($RESULT['RegisteredGateway']["@attributes"]['PRI'])) {
            $fields['gw-pri'] = $RESULT['RegisteredGateway']["@attributes"]['PRI'];
        } else {
            $fields['gw-pri'] = "U";
        }

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['Total'])) {
            $fields['mr-total'] = $RESULT['RegisteredMediaResource']["@attributes"]['Total'];
        } else {
            $fields['mr-total'] = "U";
        }

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['MOH'])) {
            $fields['mr-moh'] = $RESULT['RegisteredMediaResource']["@attributes"]['MOH'];
        } else {
            $fields['mr-moh'] = "U";
        }

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['MTP'])) {
            $fields['mr-mtp'] = $RESULT['RegisteredMediaResource']["@attributes"]['MTP'];
        } else {
            $fields['mr-mtp'] = "U";
        }

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['XCODE'])) {
            $fields['mr-xcode'] = $RESULT['RegisteredMediaResource']["@attributes"]['XCODE'];
        } else {
            $fields['mr-xcode'] = "U";
        }

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['CFB'])) {
            $fields['mr-cfb'] = $RESULT['RegisteredMediaResource']["@attributes"]['CFB'];
        } else {
            $fields['mr-cfb'] = "U";
        }

        if (isset($RESULT['H323']["@attributes"]['Total'])) {
            $fields['h323-total'] = $RESULT['H323']["@attributes"]['Total'];
        } else {
            $fields['h323-total'] = "U";
        }

        $tags = compact('rrd_name', 'rrd_def');
        data_update($device, $module, $tags, $fields);
    }

    // Enable the graphs.
    $graphs[$MODULE.'-total'] = TRUE;
    $graphs[$MODULE.'-phonebytype'] = TRUE;
    $graphs[$MODULE.'-phonebystatus'] = TRUE;
    $graphs[$MODULE.'-gw'] = TRUE;
    $graphs[$MODULE.'-mr'] = TRUE;
    $graphs[$MODULE.'-h323'] = TRUE;

    echo $MODULE.' ';
    unset($RESULT, $MODULE, $API);
}
