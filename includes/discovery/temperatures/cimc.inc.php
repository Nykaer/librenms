<?php

if ($device['os'] == 'cimc') {
    // Let's add some temperature sensors.
    $temp_board = snmpwalk_array_num ($device, '.1.3.6.1.4.1.9.9.719.1.9.44.1');
    $temp_mem = snmpwalk_array_num ($device, '.1.3.6.1.4.1.9.9.719.1.30.12.1');
    $temp_cpu = snmpwalk_array_num ($device, '.1.3.6.1.4.1.9.9.719.1.41');

    /*
     * False == OID not found - this is not an error.
     * null  == timeout or something else that caused an error.
     */
    if (is_null ($temp_board) || is_null ($temp_mem) || is_null ($temp_cpu)) {
        echo "Error\n";
    } else {
        // No Error, lets process things.

        // Board Temperatures
        $indexes = array ();
        foreach ($temp_board['1.3.6.1.4.1.9.9.719.1.9.44.1.2'] as $k => $v) {
            $temp = preg_match ('/\/sys\/(rack-unit-[^,]+)\/board\/temp-stats/', $v, $regexp_result);
            $indexes[$k] = $regexp_result[1];
        }

        foreach ($indexes as $index => $name) {
            // Ambient Temperature
            $oid = '1.3.6.1.4.1.9.9.719.1.9.44.1.4.'.$index;
            $description = $name." - Ambient";
            d_echo($oid." - ".$description." - ".$temp_board[$oid][$index]."\n");
            discover_sensor ($valid['sensor'], 'temperature', $device, $oid, 'ambient', 'cimc', $description, '1', '1', null, null, null, null, $temp_board[$oid][$index]);

            // Front Temperature
            $oid = '1.3.6.1.4.1.9.9.719.1.9.44.1.8.'.$index;
            $description = $name." - Front";
            d_echo($oid." - ".$description." - ".$temp_board[$oid][$index]."\n");
            discover_sensor ($valid['sensor'], 'temperature', $device, $oid, 'front', 'cimc', $description, '1', '1', null, null, null, null, $temp_board[$oid][$index]);

            // Rear Temperature
            $oid = '1.3.6.1.4.1.9.9.719.1.9.44.1.21.'.$index;
            $description = $name." - Rear";
            d_echo($oid." - ".$description." - ".$temp_board[$oid][$index]."\n");
            discover_sensor ($valid['sensor'], 'temperature', $device, $oid, 'rear', 'cimc', $description, '1', '1', null, null, null, null, $temp_board[$oid][$index]);

            // IO Hub Temperature
            $oid = '1.3.6.1.4.1.9.9.719.1.9.44.1.13.'.$index;
            $description = $name." - IO Hub";
            d_echo($oid." - ".$description." - ".$temp_board[$oid][$index]."\n");
            discover_sensor ($valid['sensor'], 'temperature', $device, $oid, 'ioh', 'cimc', $description, '1', '1', null, null, null, null, $temp_board[$oid][$index]);
        }

        // Memory Temperatures
        $indexes = array ();
        foreach ($temp_mem['1.3.6.1.4.1.9.9.719.1.30.12.1.2'] as $k => $v) {
            $temp = preg_match ('/\/sys\/rack-unit-1\/memarray-1\/(mem-[^,]+)\/dimm-env-stats/', $v, $regexp_result);
            $indexes[$k] = $regexp_result[1];
        }

        foreach ($indexes as $index => $name) {
            // DIMM Temperature
            $oid = '1.3.6.1.4.1.9.9.719.1.30.12.1.6.'.$index;
            $description = $name;
            d_echo($oid." - ".$description." - ".$temp_mem[$oid][$index]."\n");
            discover_sensor ($valid['sensor'], 'temperature', $device, $oid, 'mem-'.$index, 'cimc', $description, '1', '1', null, null, null, null, $temp_mem[$oid][$index]);
        }

        // CPU Temperatures
        $indexes = array ();
        foreach ($temp_cpu['1.3.6.1.4.1.9.9.719.1.41.2.1.2'] as $k => $v) {
            $temp = preg_match ('/\/sys\/rack-unit-1\/board\/(cpu-[^,]+)\/env-stats/', $v, $regexp_result);
            $indexes[$k] = $regexp_result[1];
        }

        foreach ($indexes as $index => $name) {
            // CPU Temperature
            $oid = '1.3.6.1.4.1.9.9.719.1.41.2.1.10.'.$index;
            $description = $name;
            d_echo($oid." - ".$description." - ".$temp_cpu[$oid][$index]."\n");
            discover_sensor ($valid['sensor'], 'temperature', $device, $oid, 'cpu-'.$index, 'cimc', $description, '1', '1', null, null, null, null, $temp_cpu[$oid][$index]);
        }
    }
}