<?php
/*
 * LibreNMS module to capture Cisco IOS Dial-Peer Details
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$module = 'Cisco-DialPeer';

$component = new LibreNMS\Component();
$components = $component->getComponents($device['device_id'], array('type'=>$module));

// We only care about our device id.
$components = $components[$device['device_id']];

// Begin our master array, all other values will be processed into this array.
$tblDP = array();

// Let's gather some data..
$tblcvdcMIBObjects = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.63.1');

/*
 * False == no object found - this is not an error, there are no objects configured
 * null  == timeout or something else that caused an error, there may be objects configured but we couldn't access them.
 */
if (is_null($tblcvdcMIBObjects)) {
    // We have to error here or we will end up deleting all our components.
    echo "Error\n";
} else {
    // No Error, lets process things.
    d_echo("Objects Found:\n");

    foreach ($tblcvdcMIBObjects['1.3.6.1.4.1.9.9.166.1.5.1.1.2'] as $spid => $array) {
        foreach ($array as $spobj => $index) {
            $result = array();

            // Produce a unique reproducible index for this entry.
            $result['UID'] = hash('crc32', $spid."-".$spobj);

            // Now that we have a valid identifiers, lets add some more data
            $result['sp-id'] = $spid;
            $result['sp-obj'] = $spobj;

            // Add the Type, Policy-map, Class-map, etc.
            $type = $tblcbQosObjects['1.3.6.1.4.1.9.9.166.1.5.1.1.3'][$spid][$spobj];
            $result['qos-type'] = $type;

            // Add the Parent, this lets us work out our hierarchy for display later.
            $result['parent'] = $tblcbQosObjects['1.3.6.1.4.1.9.9.166.1.5.1.1.4'][$spid][$spobj];
            $result['direction'] = $tblcbQosServicePolicy['1.3.6.1.4.1.9.9.166.1.1.1.1.3'][$spid];
            $result['ifindex'] = $tblcbQosServicePolicy['1.3.6.1.4.1.9.9.166.1.1.1.1.4'][$spid];

            // Gather different data depending on the type.
            switch ($type) {
                case 1:
                    // Policy-map, get data from that table.
                    d_echo("\nIndex: ".$index."\n");
                    d_echo("    UID: ".$result['UID']."\n");
                    d_echo("    SPID.SPOBJ: ".$result['sp-id'].".".$result['sp-obj']."\n");
                    d_echo("    If-Index: ".$result['ifindex']."\n");
                    d_echo("    Type: 1 - Policy-Map\n");
                    $result['label'] = $tblcbQosPolicyMapCfg['1.3.6.1.4.1.9.9.166.1.6.1.1.1'][$index];
                    if ($tblcbQosPolicyMapCfg['1.3.6.1.4.1.9.9.166.1.6.1.1.2'][$index] != "") {
                        $result['label'] .= " - ".$tblcbQosPolicyMapCfg['1.3.6.1.4.1.9.9.166.1.6.1.1.2'][$index];
                    }
                    d_echo("    Label: ".$result['label']."\n");
                    break;
                case 2:
                    // Class-map, get data from that table.
                    d_echo("\nIndex: ".$index."\n");
                    d_echo("    UID: ".$result['UID']."\n");
                    d_echo("    SPID.SPOBJ: ".$result['sp-id'].".".$result['sp-obj']."\n");
                    d_echo("    If-Index: ".$result['ifindex']."\n");
                    d_echo("    Type: 2 - Class-Map\n");
                    $result['label'] = $tblcbQosClassMapCfg['1.3.6.1.4.1.9.9.166.1.7.1.1.1'][$index];
                    if ($tblcbQosClassMapCfg['1.3.6.1.4.1.9.9.166.1.7.1.1.2'][$index] != "") {
                        $result['label'] .= " - ".$tblcbQosClassMapCfg['1.3.6.1.4.1.9.9.166.1.7.1.1.2'][$index];
                    }
                    d_echo("    Label: ".$result['label']."\n");
                    if ($tblcbQosClassMapCfg['1.3.6.1.4.1.9.9.166.1.7.1.1.3'][$index] == 2) {
                        $result['map-type'] = 'Match-All';
                    } elseif ($tblcbQosClassMapCfg['1.3.6.1.4.1.9.9.166.1.7.1.1.3'][$index] == 3) {
                        $result['map-type'] = 'Match-Any';
                    } else {
                        $result['map-type'] = 'None';
                    }

                    // Find a child, this will be a type 3
                    foreach ($tblcbQosObjects['1.3.6.1.4.1.9.9.166.1.5.1.1.4'][$spid] as $id => $value) {
                        if ($value == $result['sp-obj']) {
                            // We have our child, import the match
                            if ($tblcbQosObjects['1.3.6.1.4.1.9.9.166.1.5.1.1.3'][$spid][$id] == 3) {
                                $result['match'] = $result['map-type'].": ".$tblcbQosMatchStmtCfg['1.3.6.1.4.1.9.9.166.1.8.1.1.1'][$tblcbQosObjects['1.3.6.1.4.1.9.9.166.1.5.1.1.2'][$spid][$id]];
                                d_echo("    Match: ".$result['match']."\n");
                            }
                        }
                    }
                    break;
                default:
                    continue 2;
            }

            $tblDP[] = $result;
        }
    }

    /*
     * Ok, we have our 2 array's (Components and SNMP) now we need
     * to compare and see what needs to be added/updated.
     *
     * Let's loop over the SNMP data to see if we need to ADD or UPDATE any components.
     */
    foreach ($tblDP as $key => $array) {
        $component_key = false;

        // Loop over our components to determine if the component exists, or we need to add it.
        foreach ($components as $compid => $child) {
            if ($child['UID'] === $array['UID']) {
                $component_key = $compid;
            }
        }

        if (!$component_key) {
            // The component doesn't exist, we need to ADD it - ADD.
            $new_component = $component->createComponent($device['device_id'], $module);
            $component_key = key($new_component);
            $components[$component_key] = array_merge($new_component[$component_key], $array);
            echo "+";
        } else {
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

        foreach ($tblDP as $k => $v) {
            if ($array['UID'] == $v['UID']) {
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
