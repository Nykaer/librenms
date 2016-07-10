<?php
/*
 * LibreNMS module to hardware details from Cisco Integrated Management Controllers (CIMC)
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

/*
 * TODO:
 *  Check: Operability vs OperState
 *  Attach faults to chassis ???
 *  Size of Disks (BOM: 4x500gb) and LUN
 *  Presence = 10 - does this indicate populated?
 *  Does the ID field provide any use - map to stats - no
 *  Memory stats to not map to components
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

        // First, let's extract any active faults, we will use them later.
        $faults = array();
        foreach ($tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1'][5] as $fid => $fobj) {
            $fobj = preg_replace('/^sys/','/sys',$fobj);
            $faults[$fobj] = $tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1'][3][$fid] ." - ". $tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1'][11][$fid];
        }
        // Unset the faults array so it isn't reported as an error later.
        unset ($tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1']);

       foreach ($tblUCSObjects as $tbl => $array) {

           switch ($tbl) {
               // Chassis - /sys/rack-unit-1
               case "1.3.6.1.4.1.9.9.719.1.9.35.1":
//                   d_echo("Chassis (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'chassis';
                       $result['id'] = $array[27][$key];
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[47][$key];
                       $result['string'] = $array[32][$key] ." - ". ($array[49][$key]/1024) ."G Mem, ". $array[36][$key] ." CPU, ". $array[35][$key] ." core";

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[43][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[43][$key]."\n";
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // See if there are any errors on this chassis.
                       foreach ($faults as $key => $value) {
                           if (strstr($key,$result['label'])) {
                               // The fault is on this chassis.
                               $result['status'] = 2;
                               $result['error'] .= $value."\n";
                           }
                       }

                       // Add the result to the array.
                       d_echo("Chassis (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // System Board - /sys/rack-unit-1/board
               case "1.3.6.1.4.1.9.9.719.1.9.6.1":
//                   d_echo("System Board (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'board';
                       $result['id'] = $array[5][$key];
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[14][$key];
                       $result['string'] = $array[6][$key];

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[9][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[9][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("System Board (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // Memory Modules - /sys/rack-unit-1/board/memarray-1/mem-0
               case "1.3.6.1.4.1.9.9.719.1.30.11.1":
//                   d_echo("Memory Modules (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       // If there is no memory module present, continue.
                       if ($array[17][$key] != 10) {
                           continue;
                       }

                       $result['type'] = 'memory';
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[19][$key];
                       $result['string'] = $array[11][$key] ." - ". ($array[6][$key]/1024) ."G, ". $array[27][$key] ." Bit, ". $array[7][$key] ." Mhz, ". $array[21][$key] ." MT/s";

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[14][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[14][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("Memory (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // CPU's - /sys/rack-unit-1/board/cpu-1
               case "1.3.6.1.4.1.9.9.719.1.41.9.1":
//                   d_echo("CPU's (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'cpu';
                       // There is an ID in 7 - 0 and 1
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[15][$key];
                       $result['string'] = $array[8][$key] ." - ". $array[5][$key] ." Cores, ". $array[20][$key] ." Threads";

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[10][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[10][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("CPU (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // SAS Storage Module - /sys/rack-unit-1/board/storage-SAS-2
               case "1.3.6.1.4.1.9.9.719.1.45.1.1":
//                   d_echo("SAS Modules (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'sas-controller';
                       // There is an ID in 4 - 1
                       $result['id'] = substr($array[3][$key],12);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[14][$key];
                       $result['string'] = $array[5][$key] ." - Rev: ". $array[13][$key] .", ". $array[9][$key] .", RAID Types: ". $array[19][$key];

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[7][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[7][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("SAS Module (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // SAS Disks - /sys/rack-unit-1/board/storage-SAS-2/disk-1
               case "1.3.6.1.4.1.9.9.719.1.45.4.1":
//                   d_echo("SAS Disks (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'sas-disk';
                       // There is an ID in 6 - 1,2,3,4
                       $result['id'] = substr($array[3][$key],5);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[12][$key];
                       $result['string'] = $array[14][$key] ." ". $array[7][$key] .", Rev: ". $array[11][$key] .", ". round(($array[13][$key]*1.25e-10)/1024,2) ." GB";

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[9][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[9][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("SAS Disk (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // LUN's - /sys/rack-unit-1/board/storage-SAS-2/lun-0
               case "1.3.6.1.4.1.9.9.719.1.45.8.1":
//                   d_echo("LUN's (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'lun';
                       // There is an ID in 6 - 0
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = 'N/A';
                       $result['string'] = $array[3][$key] ." - ". round(($array[13][$key]*1.25e-10)/1024,2) ." GB";

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[9][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[9][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("LUN (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // RAID Battery - /sys/rack-unit-1/board/storage-SAS-2/raid-battery
               case "1.3.6.1.4.1.9.9.719.1.45.11.1":
//                   d_echo("RAID Battery (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'raid-battery';
                       // There is an ID in 6 - 1
                       $result['id'] = $array[3][$key];
                       $result['label'] = $array[2][$key];
                       $result['serial'] = 'N/A';
                       $result['string'] = $array[3][$key] ." - ". $array[7][$key];

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[9][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[9][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("RAID Battery (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // Fan's - /sys/rack-unit-1/fan-module-1-1/fan-1
               case "1.3.6.1.4.1.9.9.719.1.15.12.1":
//                   d_echo("System Fan's (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'fan';
                       // There is an ID in 5 - all 1's
                       $result['id'] = $array[8][$key] ."-". substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = 'N/A';
                       $result['string'] = $array[7][$key];

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[10][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[10][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("Fan (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // PSU's - /sys/rack-unit-1/psu-1
               case "1.3.6.1.4.1.9.9.719.1.15.56.1":
//                   d_echo("System PSU's (".$tbl."): ".print_r($array, true)."\n");
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['type'] = 'psu';
                       // There is an ID in 5 - 1
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[13][$key];
                       $result['string'] = $array[6][$key] ." - Rev: ". $array[12][$key];

                       // What is the Operability, 1 is good, everything else is bad.
                       if ($array[8][$key] != 1) {
                           // Yes, report an error
                           $result['status'] = 2;
                           $result['error'] = "Error Operability Code: ".$array[8][$key];
                       }
                       else {
                           // No, unset any errors that may exist.
                           $result['status'] = 0;
                           $result['error'] = '';
                       }

                       // Add the result to the array.
                       d_echo("PSU (".$tbl."): ".print_r($result, true)."\n");
                       $tblCIMC[] = $result;
                   }
                   break;

               // Power Stats - /sys/rack-unit-1/board/power-stats
               case "1.3.6.1.4.1.9.9.719.1.9.14.1":
//                   d_echo("Power Stats (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // Temperature Stats - /sys/rack-unit-1/board/temp-stats
               case "1.3.6.1.4.1.9.9.719.1.9.44.1":
//                   d_echo("Temperature Stats (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // Memory Stats - /sys/rack-unit-1/memarray-1/mem-1/dimm-env-stats
               case "1.3.6.1.4.1.9.9.719.1.30.12.1":
//                   d_echo("Memory Statistics (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // CPU Stats - /sys/rack-unit-1/board/cpu-1/env-stats
               case "1.3.6.1.4.1.9.9.719.1.41.2.1":
//                   d_echo("CPU Stats (".$tbl."): ".print_r($array, true)."\n");
                   break;

               // Unknown Table, ask the user to log an issue so this can be identified.
               default:
                   d_echo("Cisco-CIMC Error...\n");
                   d_echo("    Unknown Table: ".$tbl."\n");
                   d_echo("\n");
                   break;
           }

        }

        /*
         * Ok, we have our 2 array's (Components and SNMP) now we need
         * to compare and see what needs to be added/updated.
         *
         * Let's loop over the SNMP data to see if we need to ADD or UPDATE any components.
         */
        foreach ($tblCIMC as $key => $array) {
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

            foreach ($tblCIMC as $k => $v) {
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
