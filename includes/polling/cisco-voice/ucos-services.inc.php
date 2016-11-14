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
    $ctype = 'UCOS-SERVICES';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_ucos_ast.inc.php';

    $component = new LibreNMS\Component();
    $components = $component->getComponents($device['device_id'],array('type'=>$ctype,'ignore'=>0));

    // We only care about our device id.
    $components = $components[$device['device_id']];

    // Grab the details UCOS requires.
    $user = get_dev_attrib($device, 'ucosaxl_user');
    $pass = get_dev_attrib($device, 'ucosaxl_pass');;
    $host = get_dev_attrib($device, 'ucosaxl_host');

    $api = new api_ucos_ast();
    $api->connect($user, $pass, $host);

    // Begin the master array, all data will be processed into this array.
    $services = array();

    // Extract all Services.
    $result = $api->getServices();
    if ($result === false) {
        d_echo("No Data was returned.\n");
        echo "Error\n";
    }
    else {
        d_echo("We have Services.\n");

        // Refactor the array so the data is more accessible.
        $statistics = array();
        foreach ($result['Service'] as $value) {
            $statistics[$value["@attributes"]['ServiceName']] = array('status'=>$value["@attributes"]['ServiceStatus'],'uptime'=>$value["@attributes"]['ElapsedTime']);
        }

        // We should be able to retrieve the counter data now..
        foreach($components as $compid => &$array) {
            /* A note on Service Status
             * 1 = Service Started - Operating normally
             * 2 = Service Not Started, Deactivated - not an issue, has been manually disabled.
             * 3 = Unknown - Guessing Stopping state
             * 4 = Unknown - Guessing Down State
             * 5 = Service Stopped by Admin
             */
            $status = $statistics[$array['label']]['status'];

            if (($status == 3) || ($status == 4)) {
                $array['status'] = 2;
            } else {
                $array['status'] = 0;
            }

            // Warning if the uptime has been restarted
            if ($statistics[$array['label']]['uptime'] < $array['uptime']) {
                $array['status'] = 1;
            }
            // Update our uptime to the new value
            $array['uptime'] = $statistics[$array['label']]['uptime'];
        } // End foreach COMPONENT
        echo $ctype.' ';
    }

    // Write the Components back to the DB.
    $component->setComponentPrefs($device['device_id'],$components);

    unset($result, $ctype, $api, $components, $component);
}
