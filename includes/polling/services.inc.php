<?php

/*
 * LibreNMS module to poll Nagios Services
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

foreach (dbFetchRows('SELECT * FROM `devices` AS D, `services` AS S WHERE S.device_id = D.device_id AND D.device_id = ? ORDER by D.device_id DESC', array($device['device_id'])) as $service) {
    $update = array();
    $old_status = $service['service_status'];

    // if we have a script for this check, use it.
    $check_script = $config['install_dir'].'/includes/services/check_'.strtolower($service['service_type']).'.inc.php';
    if (is_file($check_script)) {
        include $check_script;
    }

    // If we do not have a cmd from the check script, build one.
    if (!isset($check_cmd)) {
        $check_cmd = $config['nagios_plugins'] . "/check_" . $service['service_type'] . " -H " . ($service['service_ip'] ? $service['service_ip'] : $service['hostname']);
        $check_cmd .= " " . $service['service_param'];
    }

    // Re-init some values
    unset($status, $msg, $perf);

    // Some debugging
    d_echo("\nNagios Service - ".$service['service_id']."\n");
    d_echo("Request:  ".$check_cmd."\n");
    list($status, $msg, $perf) = service_check($check_cmd);
    d_echo("Response: ".$msg."\n");

    // TODO: Use proper Nagios service status. 0=Ok,1=Warning,2=Critical,Else=Unknown
    // Not now because we dont want to break existing alerting rules.
    if ($status == 0) {
        // Nagios 0 = Libre 1
        $new_status = 1;
    }
    elseif ($status == 1) {
        // Nagios 1 = Libre 2
        $new_status = 2;
    }
    elseif ($status == 2) {
        // Nagios 2 = Libre 0
        $new_status = 0;
    }
    else {
        // Unknown
        $new_status = 2;
    }

    // If we have performance data we will store it.
    if (count($perf) > 0) {
        // Yes, We have perf data.
        $filename = "services-".$service['service_id'].".rrd";
        $rrd_filename = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ($filename);

        // Set the DS in the DB if it is blank.
        $DS = array();
        foreach ($perf as $k => $v) {
            $DS[$k] = $v['uom'];
        }
        d_echo("Service DS: "._json_encode($DS)."\n");
        if ($service['service_ds'] == "") {
            $update['service_ds'] = json_encode($DS);
        }

        // Create the RRD
        if (!file_exists ($rrd_filename)) {
            $rra = "";
            foreach ($perf as $k => $v) {
                if ($v['uom'] == 'c') {
                    // This is a counter, create the DS as such
                    $rra .= " DS:".$k.":COUNTER:600:0:U";
                }
                else {
                    // Not a counter, must be a gauge
                    $rra .= " DS:".$k.":GAUGE:600:0:U";
                }
            }
            rrdtool_create ($rrd_filename, $rra . $config['rrd_rra']);
        }

        // Update RRD
        $rrd = array();
        foreach ($perf as $k => $v) {
            $rrd[$k] = $v['value'];
        }
        rrdtool_update ($rrd_filename, $rrd);
    }

    if ($old_status != $new_status) {
        // Status has changed, update.
        $update['service_changed'] = time();
        $update['service_status'] = $new_status;
        $update['service_message'] = $msg;
    }

    if (count($update) > 0) {
        service_edit($update,$service['service_id']);
    }
} //end foreach
