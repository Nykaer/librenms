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
    $ctype = 'CUCM-RegisteredDevices';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_ucos_ast.inc.php';

    // Grab the details UCOS requires.
    $user = get_dev_attrib($device, 'ucosaxl_user');
    $pass = get_dev_attrib($device, 'ucosaxl_pass');;
    $host = get_dev_attrib($device, 'ucosaxl_host');

    $api = new api_ucos_ast();
    $api->connect($user, $pass, $host);

    $result = $api->getRegisteredDevices();

    if (isset($result['RegisteredPhone'])) {
        $rrd_name = $ctype;
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

        if (isset($result['RegisteredPhone']["@attributes"]['Total'])) {
            $fields['phone-total'] = $result['RegisteredPhone']["@attributes"]['Total'];
        } else {
            $fields['phone-total'] = "U";
        }

        if (isset($result['RegisteredPhone']["@attributes"]['TotalSIP'])) {
            $fields['phone-sip'] = $result['RegisteredPhone']["@attributes"]['TotalSIP'];
        } else {
            $fields['phone-sip'] = "U";
        }

        if (isset($result['RegisteredPhone']["@attributes"]['TotalSCCP'])) {
            $fields['phone-sccp'] = $result['RegisteredPhone']["@attributes"]['TotalSCCP'];
        } else {
            $fields['phone-sccp'] = "U";
        }

        if (isset($result['RegisteredPhone']["@attributes"]['TotalPartiallyRegistered'])) {
            $fields['phone-partial'] = $result['RegisteredPhone']["@attributes"]['TotalPartiallyRegistered'];
        } else {
            $fields['phone-partial'] = "U";
        }

        if (isset($result['RegisteredPhone']["@attributes"]['TotalFailedAttempts'])) {
            $fields['phone-failed'] = $result['RegisteredPhone']["@attributes"]['TotalFailedAttempts'];
        } else {
            $fields['phone-failed'] = "U";
        }

        if (isset($result['RegisteredGateway']["@attributes"]['Total'])) {
            $fields['gw-total'] = $result['RegisteredGateway']["@attributes"]['Total'];
        } else {
            $fields['gw-total'] = "U";
        }

        if (isset($result['RegisteredGateway']["@attributes"]['FXS'])) {
            $fields['gw-fxs'] = $result['RegisteredGateway']["@attributes"]['FXS'];
        } else {
            $fields['gw-fxs'] = "U";
        }

        if (isset($result['RegisteredGateway']["@attributes"]['FXO'])) {
            $fields['gw-fxo'] = $result['RegisteredGateway']["@attributes"]['FXO'];
        } else {
            $fields['gw-fxo'] = "U";
        }

        if (isset($result['RegisteredGateway']["@attributes"]['T1CAS'])) {
            $fields['gw-t1cas'] = $result['RegisteredGateway']["@attributes"]['T1CAS'];
        } else {
            $fields['gw-t1cas'] = "U";
        }

        if (isset($result['RegisteredGateway']["@attributes"]['PRI'])) {
            $fields['gw-pri'] = $result['RegisteredGateway']["@attributes"]['PRI'];
        } else {
            $fields['gw-pri'] = "U";
        }

        if (isset($result['RegisteredMediaResource']["@attributes"]['Total'])) {
            $fields['mr-total'] = $result['RegisteredMediaResource']["@attributes"]['Total'];
        } else {
            $fields['mr-total'] = "U";
        }

        if (isset($result['RegisteredMediaResource']["@attributes"]['MOH'])) {
            $fields['mr-moh'] = $result['RegisteredMediaResource']["@attributes"]['MOH'];
        } else {
            $fields['mr-moh'] = "U";
        }

        if (isset($result['RegisteredMediaResource']["@attributes"]['MTP'])) {
            $fields['mr-mtp'] = $result['RegisteredMediaResource']["@attributes"]['MTP'];
        } else {
            $fields['mr-mtp'] = "U";
        }

        if (isset($result['RegisteredMediaResource']["@attributes"]['XCODE'])) {
            $fields['mr-xcode'] = $result['RegisteredMediaResource']["@attributes"]['XCODE'];
        } else {
            $fields['mr-xcode'] = "U";
        }

        if (isset($result['RegisteredMediaResource']["@attributes"]['CFB'])) {
            $fields['mr-cfb'] = $result['RegisteredMediaResource']["@attributes"]['CFB'];
        } else {
            $fields['mr-cfb'] = "U";
        }

        if (isset($result['H323']["@attributes"]['Total'])) {
            $fields['h323-total'] = $result['H323']["@attributes"]['Total'];
        } else {
            $fields['h323-total'] = "U";
        }

        $tags = compact('rrd_name', 'rrd_def');
        data_update($device, $ctype, $tags, $fields);
    }

    // Enable the graphs.
    $graphs[$ctype.'-total'] = TRUE;
    $graphs[$ctype.'-phonebytype'] = TRUE;
    $graphs[$ctype.'-phonebystatus'] = TRUE;
    $graphs[$ctype.'-gw'] = TRUE;
    $graphs[$ctype.'-mr'] = TRUE;
    $graphs[$ctype.'-h323'] = TRUE;

    echo $ctype.' ';
    unset($result, $ctype, $api);
}
