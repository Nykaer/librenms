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

    $MODULE = 'CUCM-ELCAC';
    echo $MODULE.': ';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_cucm_perfmon.inc.php';
    require_once 'includes/component.php';

    $COMPONENT = new component();
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>$MODULE));

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
    $LOCATIONS = array();

    // Extract all locations.
    $RESULT = $API->listInstance($HOST,'Cisco Locations LBM');
    foreach ($RESULT as $VALUE) {
        // From CUCM 9 bandwidth is configured on links not locations themselves.
        if (preg_match('/->/', $VALUE['Name'])) {
            // Add the Max counters to be retrieved
            $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$VALUE['Name'].')\BandwidthMaximum';
            $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$VALUE['Name'].')\ImmersiveVideoBandwidthMaximum';
            $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$VALUE['Name'].')\VideoBandwidthMaximum';
            $LOCATIONS[] = $VALUE['Name'];
        }
    }
    unset($RESULT);

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

            // Refactor the array so the data is more accessible.
            $STATISTICS = array();
            foreach ($RESULT as $VALUE) {
                $STATISTICS[$VALUE['Name']] = array('Value'=>$VALUE['Value'],'CStatus'=>$VALUE['CStatus']);
            }

            // Lets process each Location.
            foreach ($LOCATIONS as $LOCATION) {
                $BW['voice'] = $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$LOCATION.')\BandwidthMaximum');
                $BW['video'] = $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$LOCATION.')\VideoBandwidthMaximum');
                $BW['immersive'] = $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$LOCATION.')\ImmersiveVideoBandwidthMaximum');
                if (($BW['voice'] > 0) || ($BW['video'] > 0) || ($BW['immersive'] > 0)) {
                    // There is at least one configured bandwidth on this link, we need to collect.
                    $CUCM[] = array('label'=>$LOCATION);
                }
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
}