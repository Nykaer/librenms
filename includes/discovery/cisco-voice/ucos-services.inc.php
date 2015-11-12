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

if ($device['os_group'] == "ucos") {

    $MODULE = 'UCOS-SERVICES';
    echo $MODULE.': ';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_ucos_ast.inc.php';
    require_once 'includes/component.php';

    $COMPONENT = new component();
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>$MODULE));

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

        foreach ($RESULT['Service'] as $ARRAY) {
            $status = $ARRAY["@attributes"]['ServiceStatus'];
            /* A note on Service Status
             * 1 = Service Started - Operating normally
             * 2 = Service Not Started, Deactivated - not an issue, has been manually disabled.
             * 3 = Unknown - Guessing Stopping state
             * 4 = Unknown - Guessing Starting State
             * 5 = Service Stopped but should be running - Error State.
             */
            if (($status == 1) || ($status == 5)) {
                if ($status == 1) {
                    $status = 1;
                }
                else {
                    $status = 0;
                }
                // Add a component for each Running Service
                $SERVICES[] = array('label'=>$ARRAY["@attributes"]['ServiceName'], 'status'=>$status);
            }
        }

        /*
         * Ok, we have our 2 array's (Components and Services) now we need
         * to compare and see what needs to be added/updated.
         */
        foreach ($SERVICES as $key => $array) {
            $COMPONENT_KEY = false;

            // Loop over our components to determine if the component exists, or we need to add it.
            foreach ($COMPONENTS as $COMPID => $CHILD) {
                if ($CHILD['label'] === $array['label']) {
                    $COMPONENT_KEY = $COMPID;
                }
            }

            if (!$COMPONENT_KEY) {
                // The component doesn't exist, we need to ADD it - ADD.
                $NEW_COMPONENT = $COMPONENT->createComponent($device['device_id'],$MODULE);
                $COMPONENT_KEY = key($NEW_COMPONENT);
                $COMPONENTS[$COMPONENT_KEY] = array_merge($NEW_COMPONENT[$COMPONENT_KEY], $array);
                echo "+";
            }
            else {
                // The component does exist, merge the details in - UPDATE.
                $COMPONENTS[$COMPONENT_KEY] = array_merge($COMPONENTS[$COMPONENT_KEY], $array);
                echo ".";
            } // End If
        } // End foreach

        /*
         * Loop over the Component data to see if we need to DELETE any components.
         */
        foreach ($COMPONENTS as $key => $array) {
            // Guilty until proven innocent
            $FOUND = false;

            foreach ($SERVICES as $k => $v) {
                if ($array['label'] == $v['label']) {
                    // Yay, we found it...
                    $FOUND = true;
                }
            }

            if ($FOUND === false) {
                // The component has not been found. we should delete it.
                echo "-";
                $COMPONENT->deleteComponent($key);
            }
        }

        // Write the Components back to the DB.
        $COMPONENT->setComponentPrefs($device['device_id'],$COMPONENTS);
        echo "\n";
    } // End if $RESULT

}