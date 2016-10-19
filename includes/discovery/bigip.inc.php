<?php
/*
 * LibreNMS module to capture Cisco Class-Based QoS Details
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

if ($device['os'] == 'f5') {
    $component = new LibreNMS\Component();
    $components = $component->getComponents($device['device_id'],array('type'=>$module));

    // We only care about our device id.
    $components = $components[$device['device_id']];

    // Begin our master array, all other values will be processed into this array.
    $tblBigIP = array();

    // Let's gather some data..
//    $ltmVirtualServers = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.10', 0);
    $ltmVirtualServEntry = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.10.1.2.1', 0);
    $ltmVsStatusEntry = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.10.13.2.1', 0);

    /*
     * False == no object found - this is not an error, OID doesn't exist.
     * null  == timeout or something else that caused an error, OID may exist but we couldn't get it.
     */
    if ( is_null($ltmVirtualServEntry) || is_null($ltmVsStatusEntry) ) {
        // We have to error here or we will end up deleting all our components.
    }
    else {
        // No Error, lets process things.
        d_echo("Objects Found:\n");

        foreach ($ltmVsStatusEntry as $oid => $value) {
            $result = array();

            // Find all Virtual servers, they will be first in the table.
            if (strpos($oid, '1.3.6.1.4.1.3375.2.2.10.13.2.1.1.') !== false) {
                list($null, $index) = explode ('1.3.6.1.4.1.3375.2.2.10.13.2.1.1.', $oid);
                $result['UID'] = (string)$index;
                $result['category'] = 'LTMVS';
                $result['label'] = $value;

                // component_prefs.value is varchar(255). if the UID is too long, let the user know.
                if (strlen($result['UID']) > 255) {
                    echo "Error: The bigIP UID is longer than 255 characters, please log a github issue to increase component_prefs.value\n";
                }

                // Now that we have our UID we can pull all the other data we need.
                $result['IP'] = hex_to_ip($ltmVirtualServEntry['1.3.6.1.4.1.3375.2.2.10.1.2.1.3.'.$index]);
                $result['port'] = $ltmVirtualServEntry['1.3.6.1.4.1.3375.2.2.10.1.2.1.6.'.$index];

                // 0 = None, 1 = Green, 2 = Yellow, 3 = Red, 4 = Blue
                $result['state'] = $ltmVsStatusEntry['1.3.6.1.4.1.3375.2.2.10.13.2.1.2.'.$index];
                if ($result['state'] == 2) {
                    // Looks like one of the VS Pool members is down.
                    $result['status'] = 1;
                    $result['error'] = $ltmVsStatusEntry['1.3.6.1.4.1.3375.2.2.10.13.2.1.5.'.$index];
                } elseif ($result['state'] == 3) {
                    // Looks like ALL of the VS Pool members is down.
                    $result['status'] = 2;
                    $result['error'] = $ltmVsStatusEntry['1.3.6.1.4.1.3375.2.2.10.13.2.1.5.'.$index];
                } else {
                    // All is good.
                    $result['status'] = 0;
                    $result['error'] = '';
                }
            }

            // Do we have any results
            if (count($result) > 0) {
                // Let's log some debugging
                d_echo("\n\nLTMVS: ".$result['label']."\n");
                d_echo("    IP: ".$result['IP']."\n");
                d_echo("    Port: ".$result['port']."\n");
                d_echo("    UID: ".$result['UID']."\n");
                d_echo("    Status: ".$result['status']."\n");
                d_echo("    Message: ".$result['error']."\n");
                $tblBigIP[] = $result;
            }
        }

        /*
         * Ok, we have our 2 array's (Components and SNMP) now we need
         * to compare and see what needs to be added/updated.
         *
         * Let's loop over the SNMP data to see if we need to ADD or UPDATE any components.
         */
        foreach ($tblBigIP as $key => $array) {
            $component_key = false;

            // Loop over our components to determine if the component exists, or we need to add it.
            foreach ($components as $compid => $child) {
                if (($child['UID'] === $array['UID']) && ($child['category'] === $array['category'])) {
                    $component_key = $compid;
                }
            }

            if (!$component_key) {
                // The component doesn't exist, we need to ADD it - ADD.
                $new_component = $component->createComponent($device['device_id'], $module);
                $component_key = key($new_component);
                $components[$component_key] = array_merge($new_component[$component_key], $array);
                echo "+";
            }
            else {
                // The component does exist, merge the details in - UPDATE.
                $components[$component_key] = array_merge($components[$component_key], $array);
                echo ".";
            }

        }

        /*
         * Loop over the Component data to see if we need to DELETE any components.
         */
        foreach ($components as $key => $array) {
            // Guilty until proven innocent
            $found = false;

            foreach ($tblBigIP as $k => $v) {
                if (($array['UID'] == $v['UID']) && ($array['category'] == $v['category'])) {
                    // Yay, we found it...
                    $found = true;
                }
            }

            if ($found === false) {
                // The component has not been found. we should delete it.
                echo "-";
                $component->deleteComponent($key);
            }
        }

        // Write the Components back to the DB.
        $component->setComponentPrefs($device['device_id'], $components);
        echo "\n";
    } // End if not error
}
