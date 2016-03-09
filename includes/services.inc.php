<?php

function service_status($device = null) {
    $sql_query = "SELECT service_status, count(service_status) as count FROM services WHERE";
    $sql_param = array();
    $add = 0;

    if (!is_null($device)) {
        // Add a device filter to the SQL query.
        $sql_query .= " `device_id` = ?";
        $sql_param[] = $device;
        $add++;
    }

    if ($add == 0) {
        // No filters, remove " WHERE" -6
        $sql_query = substr($sql_query, 0, strlen($sql_query)-6);
    }
    $sql_query .= " GROUP BY service_status";
    d_echo("SQL Query: ".$sql_query);

    // $service is not null, get only what we want.
    $result = dbFetchRows($sql_query, $sql_param);

    // Set our defaults to 0
    $service_count = array(0 => 0, 1 => 0, 2 => 0);
    // Rebuild the array in a more convenient method
    foreach ($result as $v) {
        $service_count[$v['service_status']] = $v['count'];
    }

    d_echo("Service Count by Status: ".print_r($service_count,TRUE)."\n");
    return $service_count;
}

function service_add($device, $type, $desc, $ip='localhost', $param = "", $ignore = 0) {

    if (!is_array($device)) {
        $device = device_by_id_cache($device);
    }

    if (empty($ip)) {
        $ip = $device['hostname'];
    }

    $insert = array('device_id' => $device['device_id'], 'service_ip' => $ip, 'service_type' => $type, 'service_changed' => array('UNIX_TIMESTAMP(NOW())'), 'service_desc' => $desc, 'service_param' => $param, 'service_ignore' => $ignore, 'service_status' => 3, 'service_message' => 'Service not yet checked');
    return dbInsert($insert, 'services');
}

function service_get($device = null, $service = null) {
    $sql_query = "SELECT `service_id`,`device_id`,`service_ip`,`service_type`,`service_desc`,`service_param`,`service_ignore`,`service_status`,`service_changed`,`service_message`,`service_disabled`,`service_ds` FROM `services` WHERE";
    $sql_param = array();
    $add = 0;

    d_echo("SQL Query: ".$sql_query);
    if (!is_null($service)) {
        // Add a service filter to the SQL query.
        $sql_query .= " `service_id` = ? AND";
        $sql_param[] = $service;
        $add++;
    }
    if (!is_null($device)) {
        // Add a device filter to the SQL query.
        $sql_query .= " `device_id` = ? AND";
        $sql_param[] = $device;
        $add++;
    }

    if ($add == 0) {
        // No filters, remove " WHERE" -6
        $sql_query = substr($sql_query, 0, strlen($sql_query)-6);
    }
    else {
        // We have filters, remove " AND" -4
        $sql_query = substr($sql_query, 0, strlen($sql_query)-4);
    }
    d_echo("SQL Query: ".$sql_query);

    // $service is not null, get only what we want.
    $services = dbFetchRows($sql_query, $sql_param);
    d_echo("Service Array: ".print_r($services,TRUE)."\n");

    return $services;
}

function service_edit($update=array(), $service=null) {
    if (!is_numeric($service)) {
        return false;
    }

    return dbUpdate($update, 'services', '`service_id`=?', array($service));
}

function service_delete($service=null) {
    if (!is_numeric($service)) {
        return false;
    }

    return dbDelete('services', '`service_id` =  ?', array($service));
}

function service_discover($device, $service) {
    if (! dbFetchCell('SELECT COUNT(service_id) FROM `services` WHERE `service_type`= ? AND `device_id` = ?', array($service, $device['device_id']))) {
        service_add($device, $service, "(Auto discovered) $service");
        log_event('Autodiscovered service: type '.mres($service), $device, 'service');
        echo '+';
    }
    echo "$service ";
}

function service_check($command) {
    // This array is used to test for valid UOM's to be used for graphing.
    // Valid values from: https://nagios-plugins.org/doc/guidelines.html#AEN200
    // Note: This array must be decend from 2char to 1 char so that the search works correctly.
    $valid_uom = array ('us', 'ms', 'KB', 'MB', 'GB', 'TB', 'c', 's', '%', 'B');

    // Make our command safe.
    $command = escapeshellcmd($command);

    // Run the command and return its response.
    exec($command, $response_array, $status);

    // exec returns an array, lets implode it back to a string.
    $response_string = implode("\n", $response_array);

    // Split out the response and the performance data.
    list($response, $perf) = explode("|", $response_string);

    // Split each performance metric
    $perf_arr = explode(' ', $perf);

    // Create an array for our metrics.
    $metrics = array();

    // Loop through the perf string extracting our metric data
    foreach ($perf_arr as $string) {
        // Separate the DS and value: DS=value
        list ($ds,$values) = explode('=', trim($string));

        // Keep the first value, discard the others.
        list($value,,,) = explode(';', trim($values));
        $value = trim($value);

        // Set an empty uom
        $uom = '';

        // is the UOM valid - https://nagios-plugins.org/doc/guidelines.html#AEN200
        foreach ($valid_uom as $v) {
            if ((strlen($value)-strlen($v)) === strpos($value,$v)) {
                // Yes, store and strip it off the value
                $uom = $v;
                $value = substr($value, 0, -strlen($v));
                break;
            }
        }

        if ($ds != "") {
            // We have a DS. Add an entry to the array.
            d_echo("Perf Data - DS: ".$ds.", Value: ".$value.", UOM: ".$uom."\n");
            $metrics[$ds] = array ('value'=>$value, 'uom'=>$uom);
        }
        else {
            // No DS. Don't add an entry to the array.
            d_echo("No DS.\n");
        }
    }

    return array ($status, $response, $metrics);
}
