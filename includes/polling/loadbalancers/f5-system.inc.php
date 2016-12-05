<?php
/*
 * LibreNMS module to display F5 System Details
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

// Let's gather the stats..
$sysGlobalHttpStat = snmpwalk_array_num($device, '.1.3.6.1.4.1.3375.2.1.1.2.4', 0);

// Lets capture some global http stats
$category = 'http';
// Let's make sure the rrd is setup.
$rrd_name = array('f5-system', $category);
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

// -----------------------------------------------------
// Temp, remove this block after first run.
$rrd_filename_old = array('bigip', $category);
$rrd_filename_new = array('f5-system', $category);
if (file_exists(rrd_name($device['hostname'], $rrd_filename_old))) {
    rrd_file_rename($device, $rrd_filename_old, $rrd_filename_new);
}
// -----------------------------------------------------

$tags = compact('rrd_name', 'rrd_def', 'category');
data_update($device, 'f5-system', $tags, $fields);
