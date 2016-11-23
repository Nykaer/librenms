<?php
/*
 * LibreNMS module to display F5 LTM Details
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

// Define some error messages
$error_poolaction = array();
$error_poolaction[0] = "Unused";
$error_poolaction[1] = "Reboot";
$error_poolaction[2] = "Restart";
$error_poolaction[3] = "Failover";
$error_poolaction[4] = "Failover and Restart";
$error_poolaction[5] = "Go Active";
$error_poolaction[6] = "None";

$component = new LibreNMS\Component();
$options['filter']['disabled'] = array('=',0);
$options['filter']['ignore'] = array('=',0);
$components = $component->getComponents($device['device_id'], $options);

// We only care about our device id.
$components = $components[$device['device_id']];

// We extracted all the components for this device, now lets only get the LTM ones.
$keep = array();
$types = array('f5-ltm-vs', 'f5-ltm-pool', 'f5-ltm-poolmember');
foreach ($components as $k => $v) {
    foreach ($types as $type) {
        if ($v['type'] == $type) {
            $keep[$k] = $v;
        }
    }
}
$components = $keep;

// Only collect SNMP data if we have enabled components
if (count($components > 0)) {
    // Let's gather the stats..
    $ltmVirtualServStatEntry = snmpwalk_array_num($device, '.1.3.6.1.4.1.3375.2.2.10.2.3.1', 0);
    $ltmPoolMemberStatEntry = snmpwalk_array_num($device, '.1.3.6.1.4.1.3375.2.2.5.4.3.1', 0);
    $sysGlobalHttpStat = snmpwalk_array_num($device, '.1.3.6.1.4.1.3375.2.1.1.2.4', 0);

    // and check the status
    $ltmVsStatusEntry = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.10.13.2.1', 0);
    $ltmPoolMbrStatusEntry = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.5.6.2.1', 0);
    $ltmPoolEntry = snmpwalk_array_num($device, '1.3.6.1.4.1.3375.2.2.5.1.2.1', 0);


    // Lets capture some global http stats
    $category = 'http';
    // Let's make sure the rrd is setup.
    $rrd_name = array('bigip', $category);
    $rrd_def = array(
        'DS:2xx:COUNTER:600:0:U',
        'DS:3xx:COUNTER:600:0:U',
        'DS:4xx:COUNTER:600:0:U',
        'DS:5xx:COUNTER:600:0:U',
        'DS:get:COUNTER:600:0:U',
        'DS:post:COUNTER:600:0:U',
    );

    $fields = array(
        '2xx' => $sysGlobalHttpStat['1.3.6.1.4.1.3375.2.1.1.2.4.3.0'],
        '3xx' => $sysGlobalHttpStat['1.3.6.1.4.1.3375.2.1.1.2.4.4.0'],
        '4xx' => $sysGlobalHttpStat['1.3.6.1.4.1.3375.2.1.1.2.4.5.0'],
        '5xx' => $sysGlobalHttpStat['1.3.6.1.4.1.3375.2.1.1.2.4.6.0'],
        'get' => $sysGlobalHttpStat['1.3.6.1.4.1.3375.2.1.1.2.4.9.0'],
        'post' => $sysGlobalHttpStat['1.3.6.1.4.1.3375.2.1.1.2.4.9.0'],
    );

    // Let's print some debugging info.
    d_echo("\n\nComponent: ".$key."\n");
    d_echo("    Type: ".$category."\n");
    d_echo("    2xx:     1.3.6.1.4.1.3375.2.1.1.2.4.3.0 = ".$fields['2xx']."\n");
    d_echo("    3xx:     1.3.6.1.4.1.3375.2.1.1.2.4.4.0 = ".$fields['3xx']."\n");
    d_echo("    4xx:     1.3.6.1.4.1.3375.2.1.1.2.4.5.0 = ".$fields['4xx']."\n");
    d_echo("    5xx:     1.3.6.1.4.1.3375.2.1.1.2.4.6.0 = ".$fields['5xx']."\n");
    d_echo("    get:     1.3.6.1.4.1.3375.2.1.1.2.4.8.0 = ".$fields['get']."\n");
    d_echo("    post:    1.3.6.1.4.1.3375.2.1.1.2.4.9.0 = ".$fields['post']."\n");

    $tags = compact('rrd_name', 'rrd_def', 'category');
    data_update($device, 'bigip', $tags, $fields);

    // Loop through the components and extract the data.
    foreach ($components as $key => &$array) {
        $type = $array['type'];
//        $UID = gzuncompress($array['UID']);
        $UID = $array['UID'];
        $label = $array['label'];
        $hash = $array['hash'];

        // -----------------------------------------------------
        // Temp, remove this block after first run.
        $category = $array['category'];
        $rrd_filename_old = array($module, $category, $label, $hash);
        $rrd_filename_new = array($type, $label, $hash);
        if (file_exists(rrd_name($device['hostname'], $rrd_filename_old))) {
            rrd_file_rename($device, $rrd_filename_old, $rrd_filename_new);
        }
        // -----------------------------------------------------
        $rrd_name = array($type, $label, $hash);

        if ($type == 'f5-ltm-vs') {
            $rrd_def = array(
                'DS:pktsin:COUNTER:600:0:U',
                'DS:pktsout:COUNTER:600:0:U',
                'DS:bytesin:COUNTER:600:0:U',
                'DS:bytesout:COUNTER:600:0:U',
                'DS:totconns:COUNTER:600:0:U',
            );

            $fields = array(
                'pktsin' => $ltmVirtualServStatEntry['1.3.6.1.4.1.3375.2.2.10.2.3.1.6.'.$UID],
                'pktsout' => $ltmVirtualServStatEntry['1.3.6.1.4.1.3375.2.2.10.2.3.1.8.'.$UID],
                'bytesin' => $ltmVirtualServStatEntry['1.3.6.1.4.1.3375.2.2.10.2.3.1.7.'.$UID],
                'bytesout' => $ltmVirtualServStatEntry['1.3.6.1.4.1.3375.2.2.10.2.3.1.9.'.$UID],
                'totconns' => $ltmVirtualServStatEntry['1.3.6.1.4.1.3375.2.2.10.2.3.1.11.'.$UID],
            );

            // Let's print some debugging info.
            d_echo("\n\nComponent: ".$key."\n");
            d_echo("    Type: ".$type."\n");
            d_echo("    Label: ".$label."\n");
            d_echo("    UID: ".$UID."\n");
            d_echo("    PktsIn:     1.3.6.1.4.1.3375.2.2.10.2.3.1.6.".$UID." = ".$fields['pktsin']."\n");
            d_echo("    PktsOut:    1.3.6.1.4.1.3375.2.2.10.2.3.1.8.".$UID." = ".$fields['pktsout']."\n");
            d_echo("    BytesIn:    1.3.6.1.4.1.3375.2.2.10.2.3.1.7.".$UID." = ".$fields['bytesin']."\n");
            d_echo("    BytesOut:   1.3.6.1.4.1.3375.2.2.10.2.3.1.9.".$UID." = ".$fields['bytesout']."\n");
            d_echo("    TotalConns: 1.3.6.1.4.1.3375.2.2.10.2.3.1.11.".$UID." = ".$fields['totconns']."\n");

            // Let's check the status.
            $array['state'] = $ltmVsStatusEntry['1.3.6.1.4.1.3375.2.2.10.13.2.1.2.'.$UID];
            if ($array['state'] == 2) {
                // Looks like one of the VS Pool members is down.
                $array['status'] = 1;
                $array['error'] = $ltmVsStatusEntry['1.3.6.1.4.1.3375.2.2.10.13.2.1.5.'.$UID];
            } elseif ($array['state'] == 3) {
                // Looks like ALL of the VS Pool members is down.
                $array['status'] = 2;
                $array['error'] = $ltmVsStatusEntry['1.3.6.1.4.1.3375.2.2.10.13.2.1.5.'.$UID];
            } else {
                // All is good.
                $array['status'] = 0;
                $array['error'] = '';
            }
        }
        elseif ($type == 'f5-ltm-pool') {
            $rrd_def = array(
                'DS:minup:GAUGE:600:0:U',
                'DS:currup:GAUGE:600:0:U',
            );

            $array['minup'] = $ltmPoolEntry['1.3.6.1.4.1.3375.2.2.5.1.2.1.4.'.$UID];
            $array['minupstatus'] = $ltmPoolEntry['1.3.6.1.4.1.3375.2.2.5.1.2.1.5.'.$index];
            $array['currentup'] = $ltmPoolEntry['1.3.6.1.4.1.3375.2.2.5.1.2.1.8.'.$UID];
            $array['minupaction'] = $ltmPoolEntry['1.3.6.1.4.1.3375.2.2.5.1.2.1.6.'.$UID];

            $fields = array(
                'minup' => $array['minup'],
                'currup' => $array['currentup'],
            );

            // Let's print some debugging info.
            d_echo("\n\nComponent: ".$key."\n");
            d_echo("    Type: ".$type."\n");
            d_echo("    Label: ".$label."\n");
            d_echo("    UID: ".$UID."\n");
            d_echo("    Minimum Up:   1.3.6.1.4.1.3375.2.2.10.2.3.1.6.".$UID." = ".$fields['minup']."\n");
            d_echo("    Current Up:   1.3.6.1.4.1.3375.2.2.10.2.3.1.8.".$UID." = ".$fields['currup']."\n");

            // If minupstatus = 1, we should care about minup. If we have less pool members than the minimum, we should error.
            if (($array['minupstatus'] == 1) && ($array['currentup'] <= $array['minup'])) {
                // Danger Will Robinson... We dont have enough Pool Members!
                $array['status'] = 2;
                $array['error'] = "Minimum Pool Members not met. Action taken: ".$error_poolaction[$array['minupaction']];
            } else {
                // All is good.
                $array['status'] = 0;
                $array['error'] = '';
            }
        }
        elseif ($type == 'f5-ltm-poolmember') {
            $rrd_def = array(
                'DS:pktsin:COUNTER:600:0:U',
                'DS:pktsout:COUNTER:600:0:U',
                'DS:bytesin:COUNTER:600:0:U',
                'DS:bytesout:COUNTER:600:0:U',
                'DS:totconns:COUNTER:600:0:U',
            );

            $fields = array(
                'pktsin' => $ltmPoolMemberStatEntry['1.3.6.1.4.1.3375.2.2.5.4.3.1.5.'.$UID],
                'pktsout' => $ltmPoolMemberStatEntry['1.3.6.1.4.1.3375.2.2.5.4.3.1.7.'.$UID],
                'bytesin' => $ltmPoolMemberStatEntry['1.3.6.1.4.1.3375.2.2.5.4.3.1.6.'.$UID],
                'bytesout' => $ltmPoolMemberStatEntry['1.3.6.1.4.1.3375.2.2.5.4.3.1.8.'.$UID],
                'totalconns' => $ltmPoolMemberStatEntry['1.3.6.1.4.1.3375.2.2.5.4.3.1.10.'.$UID],
            );

            // Let's print some debugging info.
            d_echo("\n\nComponent: ".$key."\n");
            d_echo("    Type: ".$type."\n");
            d_echo("    Label: ".$label."\n");
            d_echo("    UID: ".$UID."\n");
            d_echo("    PktsIn:     1.3.6.1.4.1.3375.2.2.5.4.3.1.5.".$UID." = ".$fields['pktsin']."\n");
            d_echo("    PktsOut:    1.3.6.1.4.1.3375.2.2.5.4.3.1.7.".$UID." = ".$fields['pktsout']."\n");
            d_echo("    BytesIn:    1.3.6.1.4.1.3375.2.2.5.4.3.1.6.".$UID." = ".$fields['bytesin']."\n");
            d_echo("    BytesOut:   1.3.6.1.4.1.3375.2.2.5.4.3.1.8.".$UID." = ".$fields['bytesout']."\n");
            d_echo("    TotalConns: 1.3.6.1.4.1.3375.2.2.5.4.3.1.8.".$UID." = ".$fields['totalconns']."\n");

            if ($array['state'] == 3) {
                // Warning Alarm, the pool member is down.
                $array['status'] = 1;
                $array['error'] = "Pool Member is Down: ".$ltmPoolMbrStatusEntry['1.3.6.1.4.1.3375.2.2.5.6.2.1.8.'.$UID];;
            } else {
                // All is good.
                $array['status'] = 0;
                $array['error'] = '';
            }
        } else {
            d_echo("Type is unknown: ".$type."\n");
            continue;
        }

        $tags = compact('rrd_name', 'rrd_def', 'type', 'hash', 'label');
        data_update($device, $type, $tags, $fields);
    } // End foreach components

    // Write the Components back to the DB.
    $component->setComponentPrefs($device['device_id'], $components);
} // end if count components

// Clean-up after yourself!
unset($type, $components, $component, $options);
