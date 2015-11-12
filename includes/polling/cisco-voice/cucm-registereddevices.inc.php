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
        $RRD = array();
        $RRD['filename'] = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ($MODULE.".rrd");

        $RRD['create'] = " DS:phone-total:GAUGE:600:0:U DS:phone-sip:GAUGE:600:0:U DS:phone-sccp:GAUGE:600:0:U DS:phone-partial:GAUGE:600:0:U DS:phone-failed:GAUGE:600:0:U DS:gw-total:GAUGE:600:0:U DS:gw-fxs:GAUGE:600:0:U DS:gw-fxo:GAUGE:600:0:U DS:gw-t1cas:GAUGE:600:0:U DS:gw-pri:GAUGE:600:0:U DS:mr-total:GAUGE:600:0:U DS:mr-moh:GAUGE:600:0:U DS:mr-mtp:GAUGE:600:0:U DS:mr-xcode:GAUGE:600:0:U DS:mr-cfb:GAUGE:600:0:U DS:h323-total:GAUGE:600:0:U";

        if (isset($RESULT['RegisteredPhone']["@attributes"]['Total'])) {
            $VALUE = $RESULT['RegisteredPhone']["@attributes"]['Total'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = "N:".$VALUE;

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalSIP'])) {
            $VALUE = $RESULT['RegisteredPhone']["@attributes"]['TotalSIP'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalSCCP'])) {
            $VALUE = $RESULT['RegisteredPhone']["@attributes"]['TotalSCCP'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalPartiallyRegistered'])) {
            $VALUE = $RESULT['RegisteredPhone']["@attributes"]['TotalPartiallyRegistered'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredPhone']["@attributes"]['TotalFailedAttempts'])) {
            $VALUE = $RESULT['RegisteredPhone']["@attributes"]['TotalFailedAttempts'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredGateway']["@attributes"]['Total'])) {
            $VALUE = $RESULT['RegisteredGateway']["@attributes"]['Total'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredGateway']["@attributes"]['FXS'])) {
            $VALUE = $RESULT['RegisteredGateway']["@attributes"]['FXS'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredGateway']["@attributes"]['FXO'])) {
            $VALUE = $RESULT['RegisteredGateway']["@attributes"]['FXO'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredGateway']["@attributes"]['T1CAS'])) {
            $VALUE = $RESULT['RegisteredGateway']["@attributes"]['T1CAS'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredGateway']["@attributes"]['PRI'])) {
            $VALUE = $RESULT['RegisteredGateway']["@attributes"]['PRI'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['Total'])) {
            $VALUE = $RESULT['RegisteredMediaResource']["@attributes"]['Total'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['MOH'])) {
            $VALUE = $RESULT['RegisteredMediaResource']["@attributes"]['MOH'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['MTP'])) {
            $VALUE = $RESULT['RegisteredMediaResource']["@attributes"]['MTP'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['XCODE'])) {
            $VALUE = $RESULT['RegisteredMediaResource']["@attributes"]['XCODE'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['RegisteredMediaResource']["@attributes"]['CFB'])) {
            $VALUE = $RESULT['RegisteredMediaResource']["@attributes"]['CFB'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        if (isset($RESULT['H323']["@attributes"]['Total'])) {
            $VALUE = $RESULT['H323']["@attributes"]['Total'];
        }
        else {
            $VALUE = "U";
        }
        $RRD['data'] = ":".$VALUE;

        // Create the RRD if it doesn't exist.
        if (!file_exists ($RRD['filename'])) {
            rrdtool_create ($RRD['filename'], $RRD['create'] . $config['rrd_rra']);
        }

        // Add the data to the RRD if it exists.
        if (isset($RRD['data'])) {
            rrdtool_update ($RRD['filename'], $RRD['data']);
        }
    }

    // Enable the graphs.
    $graphs[$MODULE.'-total'] = TRUE;
    $graphs[$MODULE.'-phonebytype'] = TRUE;
    $graphs[$MODULE.'-phonebystatus'] = TRUE;
    $graphs[$MODULE.'-gw'] = TRUE;
    $graphs[$MODULE.'-mr'] = TRUE;
    $graphs[$MODULE.'-h323'] = TRUE;

    echo $MODULE.' ';
    unset($RRD, $RESULT, $MODULE, $API);
}