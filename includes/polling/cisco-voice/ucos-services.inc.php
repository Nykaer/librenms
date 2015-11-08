<?php
/*
 * LibreNMS module to Graph basic resources from a Cisco CallManager Server
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

    $MODULE = 'UCOS-SERVICES';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_ucos_ast.inc.php';
    require_once 'includes/component.php';

    $COMPONENT = new component();
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>$MODULE,'ignore'=>0));

    // Grab the details UCOS requires.
    $USER = get_dev_attrib($device, 'ucosaxl_user');
    $PASS = get_dev_attrib($device, 'ucosaxl_pass');;
    $HOST = get_dev_attrib($device, 'ucosaxl_host');

    $API = new api_ucos_ast();
    $API->connect($USER, $PASS, $HOST);

    // Begin the master array, all data will be processed into this array.
    $SERVICES = array();

    // Extract all Services.
    $RESULT = $API->getServices();
    if ($RESULT === false) {
        d_echo("No Data was returned.\n");
        echo "Error\n";
    }
    else {
        d_echo("We have Services.\n");

        // Refactor the array so the data is more accessible.
        $STATISTICS = array();
        foreach ($RESULT['Service'] as $VALUE) {
            $STATISTICS[$VALUE["@attributes"]['ServiceName']] = array('status'=>$VALUE["@attributes"]['ServiceStatus'],'uptime'=>$VALUE["@attributes"]['ElapsedTime']);
        }

        // We should be able to retrieve the counter data now..
        foreach($COMPONENTS as $COMPID => &$ARRAY) {
            /* A note on Service Status
             * 1 = Service Started - Operating normally
             * 2 = Service Not Started, Deactivated - not an issue, has been manually disabled.
             * 3 = Unknown - Guessing Stopping state
             * 4 = Unknown - Guessing Starting State
             * 5 = Service Stopped but should be running - Error State.
             */
            $status = $STATISTICS[$ARRAY['label']]['status'];

            if ($status == 1) {
                $ARRAY['status'] = 1;
            }
            else {
                $ARRAY['status'] = 0;
            }

            // Alert if the uptime has been restarted
            if ($STATISTICS[$ARRAY['label']]['uptime'] < $ARRAY['uptime']) {
                $ARRAY['status'] = 0;
            }
            // Update our uptime to the new value
            $ARRAY['uptime'] = $STATISTICS[$ARRAY['label']]['uptime'];
        } // End foreach COMPONENT

        echo $MODULE.' ';
    }

    // Write the Components back to the DB.
    $COMPONENT->setComponentPrefs($device['device_id'],$COMPONENTS);

    unset($RESULT, $MODULE, $API, $COMPONENTS, $COMPONENT);
}