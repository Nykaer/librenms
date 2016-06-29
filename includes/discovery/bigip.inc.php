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

    $module = 'F5-Big-IP';

    require_once 'includes/component.php';
    $component = new component();
    $components = $component->getComponents($device['device_id'],array('type'=>$module));

    // We only care about our device id.
    $components = $components[$device['device_id']];


    // Begin our master array, all other values will be processed into this array.
    $tblBigIP = array();

    // Let's gather some data..
    $ltmVirtualServers = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.10', 0);
//    $ltmVirtualServProfileEntry = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.10.5.2.1', 0);

    /*
     * False == no object found - this is not an error, OID doesn't exist.
     * null  == timeout or something else that caused an error, OID may exist but we couldn't get it.
     */
    if ( is_null($ltmVirtualServers) || is_null($ltmVirtualServers) ) {
        // We have to error here or we will end up deleting all our components.
        echo "Error\n";
    }
    else {
        // No Error, lets process things.
        d_echo("Objects Found:\n");

        foreach ($ltmVirtualServers as $oid => $value) {
            $result = array();

            // Find all Virtual servers, they will be first in the table.
            if (strpos($oid, '1.3.6.1.4.1.3375.2.2.10.2.3.1.1.') !== false) {
                list($null, $index) = explode ('1.3.6.1.4.1.3375.2.2.10.2.3.1.1.', $oid);
                $result['UID'] = $index;
                $result['type'] = 'LTMVS';
                $result['label'] = $value;
            }


/*
            // Find all LTMVS Profiles.
            if (strpos($oid, '1.3.6.1.4.1.3375.2.2.10.5.2.1.1.') !== false) {
                list($null,$index) = explode('1.3.6.1.4.1.3375.2.2.10.5.2.1.1.', $oid);
                $result['UID'] = $index;
                $result['type'] = 'LTMVSProfile';
                $result['label'] = $value;

                foreach ($tblBigIP as $bigip) {
                    // We only care about LTMVS objects.
                    if (($bigip['type'] == 'LTMVS') && (strpos($result['UID'], $bigip['UID']) !== false)) {
                        $result['parent'] = $bigip['UID'];
                    }
                }
            }
*/

            // Do we have any results
            if (count($result) > 0) {
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
                if (($child['UID'] === $array['UID']) && ($child['type'] === $array['type'])) {
                    $component_key = $compid;
                }
            }

            if (!$component_key) {
                // The component doesn't exist, we need to ADD it - ADD.
//                $new_component = $component->createComponent($device['device_id'],$module);
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
                if ( ($array['UID'] == $v['UID']) && ($array['type'] == $v['type'])) {
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
//        $component->setComponentPrefs($device['device_id'],$components);
        echo "\n";

d_echo($tblBigIP);

    } // End if not error

}
