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
 *  Size of Disks (BOM: 4x500gb) and LUN
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
        // Unset the faults and stats array so it isn't reported as an error later.
        unset ($tblUCSObjects['1.3.6.1.4.1.9.9.719.1.1.1.1'],$tblUCSObjects['1.3.6.1.4.1.9.9.719.1.9.14.1'],$tblUCSObjects['1.3.6.1.4.1.9.9.719.1.9.44.1'],$tblUCSObjects['1.3.6.1.4.1.9.9.719.1.30.12.1'],$tblUCSObjects['1.3.6.1.4.1.9.9.719.1.41.2.1']);

       foreach ($tblUCSObjects as $tbl => $array) {

           switch ($tbl) {
               // Chassis - /sys/rack-unit-1
               case "1.3.6.1.4.1.9.9.719.1.9.35.1":
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'chassis';
                       $result['id'] = $array[27][$key];
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[47][$key];
                       $result['string'] = $array[32][$key] ." - ". ($array[49][$key]/1024) ."G Mem, ". $array[36][$key] ." CPU, ". $array[35][$key] ." core";
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.9.35.1.43.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'board';
                       $result['id'] = $array[5][$key];
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[14][$key];
                       $result['string'] = $array[6][$key];
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.9.6.1.9.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       // If there is no memory module present, continue.
                       if ($array[17][$key] != 10) {
                           continue;
                       }

                       $result['hwtype'] = 'memory';
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[19][$key];
                       $result['string'] = $array[11][$key] ." - ". ($array[6][$key]/1024) ."G, ". $array[27][$key] ." Bit, ". $array[7][$key] ." Mhz, ". $array[21][$key] ." MT/s";
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.30.11.1.14.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'cpu';
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[15][$key];
                       $result['string'] = $array[8][$key] ." - ". $array[5][$key] ." Cores, ". $array[20][$key] ." Threads";
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.41.9.1.10.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'sas-controller';
                       $result['id'] = substr($array[3][$key],12);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[14][$key];
                       $result['string'] = $array[5][$key] ." - Rev: ". $array[13][$key] .", ". $array[9][$key] .", RAID Types: ". $array[19][$key];
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.45.1.1.7.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'sas-disk';
                       $result['id'] = substr($array[3][$key],5);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[12][$key];
//                       $result['string'] = $array[14][$key] ." ". $array[7][$key] .", Rev: ". $array[11][$key] .", ". round(($array[13][$key]*1.25e-10)/1024,2) ." GB";
                       $result['string'] = $array[14][$key] ." ". $array[7][$key] .", Rev: ". $array[11][$key] .", ". round($array[13][$key]/1024,2) ." GB";
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.45.4.1.9.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'lun';
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = 'N/A';
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.45.8.1.9.'.$key;

                       // A 1 PB Disk??? This must be an old firmware that reports in bytes.
                       if (($array[13][$key]/1000) > 1000000 ) {
                           $result['string'] = $array[3][$key] ." - ". round(($array[13][$key]*1.25e-10)/1024,2) ." GB";
                       }
                       else {
                           $result['string'] = $array[3][$key] ." - ". round($array[13][$key]/1000,2) ." GB";
                       }
                       $result['string'] = $array[3][$key] ." - ". $array[13][$key] ." ??";

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'raid-battery';
                       $result['id'] = $array[3][$key];
                       $result['label'] = $array[2][$key];
                       $result['serial'] = 'N/A';
                       $result['string'] = $array[3][$key] ." - ". $array[7][$key];
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.45.11.1.9.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'fan';
                       $result['id'] = $array[8][$key] ."-". substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = 'N/A';
                       $result['string'] = $array[7][$key];
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.15.12.1.10.'.$key;

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
                   $result = array();
                   foreach ($array[3] as $key => $item) {
                       $result['hwtype'] = 'psu';
                       $result['id'] = substr($array[3][$key],4);
                       $result['label'] = $array[2][$key];
                       $result['serial'] = $array[13][$key];
                       $result['string'] = $array[6][$key] ." - Rev: ". $array[12][$key];
                       $result['statusoid'] = '1.3.6.1.4.1.9.9.719.1.15.56.1.8.'.$key;

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
                if ($child['label'] === $array['label']) {
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
                if ($array['label'] == $v['label']) {
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
