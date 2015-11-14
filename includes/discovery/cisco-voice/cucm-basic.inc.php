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

    $MODULE = 'CUCM-Basic';
    echo $MODULE.': ';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_cucm_perfmon.inc.php';
    require_once 'includes/component.php';

    $COMPONENT = new component();
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>$MODULE));

    // We only care about our device id.
    $COMPONENTS = $COMPONENTS[$device['device_id']];

    // Grab the details UCOS requires.
    $USER = get_dev_attrib($device, 'ucosaxl_user');
    $PASS = get_dev_attrib($device, 'ucosaxl_pass');;
    $HOST = get_dev_attrib($device, 'ucosaxl_host');

    $API = new api_cucm_perfmon();
    $API->connect($USER, $PASS, array($HOST));

    // Begin the master array, all data will be processed into this array.
    $CUCM = array();

    // Add all the counters we are interested in.
    $COUNTER = array();
    // Basic
    $COUNTER[] = '\\\\'.$HOST.'\Number of Replicates Created and State of Replication(ReplicateCount)\Replicate_State';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\InitializationState';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceTotal';

    // Can we add the counters.
    if ($API->addCounter($COUNTER)) {
        d_echo("Counter(s) Added\n");
        $RESULT = $API->collectSessionData();

        if ($RESULT === false) {
            d_echo("No Data was returned.\n");
            echo "Error\n";
        }
        else {
            // We have counter data..
            d_echo("We have counter data.\n");

            // Extract the counter data from the response.
            foreach ($RESULT as $VALUE) {
                if (($VALUE['Value'] > 0) && (($VALUE['CStatus'] == 0) || ($VALUE['CStatus'] == 1))) {
                    // If we have a non-zero value, add the counter
                    switch ($VALUE['Name']) {
                        case '\\\\'.$HOST.'\Number of Replicates Created and State of Replication(ReplicateCount)\Replicate_State':
                            // Only Add the metric if replication is started.
                            if ($VALUE['Value'] != 0) {
                                $CUCM[] = array('label'=>'Replicate_State');
                            }
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\InitializationState':
                            $STATUS = 0;        // Guilty until proven innocent.
                            if ($VALUE['Value'] == 100) {
                                // 100 is normal, everything else is an error.
                                $STATUS = 1;
                            }
                            $CUCM[] = array('label'=>'InitializationState','status'=>$STATUS);
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal':
                            $CUCM[] = array('label'=>'AnnunciatorResource');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal':
                            $CUCM[] = array('label'=>'MTPResource');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal':
                            $CUCM[] = array('label'=>'TranscoderResource');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal':
                            $CUCM[] = array('label'=>'VCBConferences');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal':
                            $CUCM[] = array('label'=>'VCBResource');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources':
                            $CUCM[] = array('label'=>'MOHUnicastResources');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceTotal':
                            $CUCM[] = array('label'=>'SWConferenceResource');
                            break;
                        case '\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceTotal':
                            $CUCM[] = array('label'=>'HWConferenceResource');
                            break;
                        default:
                            d_echo("Unknown Counter: ".$VALUE['Name']);
                    } // End switch
                } // End if
            } // End foreach

            // Add a component for our active calls
            $CUCM[] = array('label'=>'Calls');

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
                    $COMPONENT->deleteComponent($key);
                }
            }

            // Write the Components back to the DB.
            $COMPONENT->setComponentPrefs($device['device_id'],$COMPONENTS);
            echo "\n";
        } // End if $RESULT

    }
    else {
        // Could not add counters.
        echo "Error\n";
    }
}