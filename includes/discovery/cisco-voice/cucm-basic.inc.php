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

if ($device['os'] == "ucos") {

    $MODULE = 'CUCM-Basic';
    echo $MODULE.': ';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_cucm_perfmon.inc.php';
    require_once 'includes/component.php';

    $COMPONENT = new component();
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>$MODULE));

    // Pull from DB.
    $USER = "script";
    $PASS = "script";
    $HOST = "192.168.174.13";

    $API = new api_cucm_perfmon();
    $API->connect($USER, $PASS, array($HOST));

    // Begin the master array, all data will be processed into this array.
    $CUCM = array();

    // Add all the counters we are interested in.
    $COUNTER = array();
    // Basic
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\InitializationState';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHTotalMulticastResources';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources';

    // Can we add the counters.
    if ($API->addCounter($COUNTER)) {
        d_echo("Counter(s) Added\n");
        $RESULT = $API->collectSessionData();

        if ($RESULT === false) {
            d_echo("No Data was returned.\n");
        }
        else {
            // We have counter data..
            d_echo("We have counter data.\n");

            // Extract the counter data from the response.
            foreach ($RESULT as $VALUE) {
                if ($VALUE['Value'] > 0) {
                    // If we have a non-zero value, add the counter
                    switch ($VALUE['Name']) {
                        case '\\\\'.$HOST.'\Cisco CallManager\InitializationState':
                            $CUCM[] = array('label'=>'InitializationState');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal':
                            $CUCM[] = array('label'=>'AnnunciatorResource','total'=>$VALUE['Value']);
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal':
                            $CUCM[] = array('label'=>'MTPResource','total'=>$VALUE['Value']);
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal':
                            $CUCM[] = array('label'=>'TranscoderResource','total'=>$VALUE['Value']);
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal':
                            $CUCM[] = array('label'=>'VCBConferences','total'=>$VALUE['Value']);
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal':
                            $CUCM[] = array('label'=>'VCBResource','total'=>$VALUE['Value']);
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\MOHTotalMulticastResources':
                            $CUCM[] = array('label'=>'MOHMulticastResources','total'=>$VALUE['Value']);
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources':
                            $CUCM[] = array('label'=>'MOHUnicastResources','total'=>$VALUE['Value']);
                            break;
                    } // End switch
                } // End if
            } // End foreach

            /*
             * Ok, we have our 2 array's (Components and CUCM) now we need
             * to compare and see what needs to be added/updated.
             */
            foreach ($CUCM as $key => $array) {
                $COMPONENT_KEY = false;

                // Loop over our components to determine if the component exists, or we need to add it.
                foreach ($COMPONENTS as $COMPID => $CHILD) {
                    if ($CHILD['label'] === $array['label']) {
                        $COMPONENT_KEY = $COMPID;
                    }
                }

                d_echo(print_r($CUCM,TRUE));

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

                foreach ($CUCM as $k => $v) {
                    if ($array['label'] == $v['label']) {
                        // Yay, we found it...
                        $FOUND = true;
                    }
                }

                if ($FOUND === false) {
                    // The component has not been found. we should delete it.
                    echo "-";
//                    $COMPONENT->deleteComponent($key);
                }
            }

            // Write the Components back to the DB.
            $COMPONENT->setComponentPrefs($device['device_id'],$COMPONENTS);
            echo "\n";
        } // End if $RESULT
    }
}