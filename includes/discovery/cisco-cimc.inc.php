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

if ($device['os'] == 'cimc') {

    $module = 'Cisco-CIMC';
    echo $module.': ';

    require_once 'includes/component.php';
    $component = new component();
    $components = $component->getComponents($device['device_id'],array('type'=>$module));

    // We only care about our device id.
    $components = $components[$device['device_id']];

    // Begin our master array, all other values will be processed into this array.
    $tblCIMC = array();

    // Let's gather some data..
    $tblUCSObjects = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1', 2);
//    $tblBoard = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.9.6', 2);
//    $tblFans = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.15.12');
//    $tblPSU = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.15.56');
//    $tblMemory = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.30.11');
//    $tblCPU = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.41.9');
//    $tblStorage = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.45.1');
//    $tblDisk = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.45.4');
//    $tblLUN = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.45.8');
//    $tblBAT = snmpwalk_array_num($device, '.1.3.6.1.4.1.9.9.719.1.45.11');

    /*
     * False == no object found - this is not an error, there is no QOS configured
     * null  == timeout or something else that caused an error, the OID's may be present but we couldn't get them.
     */
    if ( is_null($tblUCSObjects) ) {
        // We have to error here or we will end up deleting all our components.
        echo "Error\n";
    }
    else {
        // No Error, lets process things.
        d_echo("CIMC Hardware Found:\n");

        // First, let's extract any faults, we will use them later.
        $faults = array();
        d_echo("\nFaults: ".print_r($tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1'],TRUE)."\n");
        foreach ($tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1'][5] as $fid => $fobj) {
            $fobj = preg_replace('/\/sys|sys/','',$fobj);
            $faults[$fobj] = $tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1'][11][$fid];
        }
        unset ($tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1']);
        d_echo("\nFault Array: ".print_r($faults,TRUE)."\n");

       foreach ($tblUCSObjects as $tbl => $array) {

           switch ($tbl) {
               // Chassis - /sys/rack-unit-1
               case "1.3.6.1.4.1.9.9.719.1.9.35.1":
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $parent = "";      // Chassis has no parent
                       $result['parent'] = $parent;
                       $result['label'] = $parent . "/" . $array[3][$key];
                       $result['serial'] = $array[47][$key];
                       $result['string'] = $array[32][$key] ." - ". ($array[49][$key]/1024) ."G Mem, ". $array[36][$key] ." CPU, ". $array[35][$key] ." core";

                       // Does this entity have an entry in the faults table.
                       if (isset ($faults[$result['label']])) {
                           // Yes, report an error
                           $result['status'] = 0;
                           $result['error'] = $faults[$result['label']];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 1;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("Chassis (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // System Board - /sys/rack-unit-1/board
               case "1.3.6.1.4.1.9.9.719.1.9.6.1":
                   d_echo("System Board (".$tbl."): ".print_r($array[2], true)."\n");
                   break;

               // Power Stats - /sys/rack-unit-1/board/power-stats
               case "1.3.6.1.4.1.9.9.719.1.9.14.1":
                   d_echo("Power Stats (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // Temperature Stats - /sys/rack-unit-1/board/temp-stats
               case "1.3.6.1.4.1.9.9.719.1.9.44.1":
//                   d_echo("Temperature Stats (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // Memory Modules - /sys/rack-unit-1/board/memarray-1/mem-0
               case "1.3.6.1.4.1.9.9.719.1.30.11.1":
//                   d_echo("Memory Modules (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // Memory Stats - /sys/rack-unit-1/memarray-1/mem-1/dimm-env-stats
               case "1.3.6.1.4.1.9.9.719.1.30.12.1":
//                   d_echo("Memory Statistics (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // CPU's - /sys/rack-unit-1/board/cpu-1
               case "1.3.6.1.4.1.9.9.719.1.41.9.1":
                   d_echo("CPU's (".$tbl."): ".print_r($array[2], true)."\n");
                   break;

               // CPU Stats - /sys/rack-unit-1/board/cpu-1/env-stats
               case "1.3.6.1.4.1.9.9.719.1.41.2.1":
//                   d_echo("CPU Stats (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // SAS Storage Module - /sys/rack-unit-1/board/storage-SAS-2
               case "1.3.6.1.4.1.9.9.719.1.45.1.1":
//                   d_echo("SAS Modules (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // SAS Disks - /sys/rack-unit-1/board/storage-SAS-2/disk-1
               case "1.3.6.1.4.1.9.9.719.1.45.4.1":
//                   d_echo("SAS Disks (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // LUN's - /sys/rack-unit-1/board/storage-SAS-2/lun-0
               case "1.3.6.1.4.1.9.9.719.1.45.8.1":
  //                 d_echo("LUN's (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // RAID Battery - /sys/rack-unit-1/board/storage-SAS-2/raid-battery
               case "1.3.6.1.4.1.9.9.719.1.45.11.1":
//                   d_echo("RAID Battery (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // Fan's - /sys/rack-unit-1/fan-module-1-1/fan-1
               case "1.3.6.1.4.1.9.9.719.1.15.12.1":
//                   d_echo("System Fan's (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // PSU's - /sys/rack-unit-1/psu-1
               case "1.3.6.1.4.1.9.9.719.1.15.56.1":
                   $result = array();
                   d_echo("Power Supplies (".$tbl."): ".print_r($array, true)."\n");
                   foreach ($array[3] as $key => $item) {
                       $result['label'] = preg_replace('/\/sys|sys/','',$array[2][$key]);
                       $result['parent'] = preg_replace('/\/'.$item.'/','',$result['label']);
                       $result['serial'] = $array[13][$key];
//                       $result['string'] = $array[32][$key] ." - ". ($array[49][$key]/1024) ."G Mem, ". $array[36][$key] ." CPU, ". $array[35][$key] ." core";

                       // Does this entity have an entry in the faults table.
                       if (isset ($faults[$result['label']])) {
                           // Yes, report an error
                           $result['status'] = 0;
                           $result['error'] = $faults[$result['label']];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 1;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("Power Supplies (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // Unknown Table, ask the user to log an issue so this can be identified.
               default:
//                   d_echo("Cisco-CIMC Error...\n");
//                   d_echo("    Unknown Table: ".$tbl."\n");
//                   d_echo("\n");
                   break;
           }

        }

        /*
         * Ok, we have our 2 array's (Components and SNMP) now we need
         * to compare and see what needs to be added/updated.
         *
         * Let's loop over the SNMP data to see if we need to ADD or UPDATE any components.
         */
        foreach ($tblCBQOS as $key => $array) {
            $component_key = false;

            // Loop over our components to determine if the component exists, or we need to add it.
        foreach ($components as $compid => $child) {
                if ($child['UID'] === $array['UID']) {
                    $component_key = $compid;
                }
            }

            if (!$component_key) {
                // The component doesn't exist, we need to ADD it - ADD.
                $new_component = $component->createComponent($device['device_id'],$module);
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

            foreach ($tblCBQOS as $k => $v) {
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
        $component->setComponentPrefs($device['device_id'],$components);
        echo "\n";

    } // End if not error

}
