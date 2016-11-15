<?php
/*
 * LibreNMS module to display CUCM Registered Devices
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Phone Registration Status  Now  Avg  Max\\n'";

$rrd_filename = rrd_name($device['hostname'], array('CUCM', 'RegisteredDevices'));

if (file_exists($rrd_filename)) {
    $rrd_options .= " DEF:REG" . $count . "=" . $rrd_filename . ":phone-total:AVERAGE";
    $rrd_options .= " AREA:REG" . $count . "#" . $config['graph_colours']['mixed'][2] . ":'Registered           '";
    $rrd_options .= " GPRINT:REG" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:REG" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:REG" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:PARTIAL" . $count . "=" . $rrd_filename . ":phone-partial:AVERAGE";
    $rrd_options .= " AREA:PARTIAL" . $count . "#" . $config['graph_colours']['mixed'][4] . ":'Partial              '";
    $rrd_options .= " GPRINT:PARTIAL" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:PARTIAL" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:PARTIAL" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:UNREG" . $count . "=" . $rrd_filename . ":phone-failed:AVERAGE";
    $rrd_options .= " AREA:UNREG" . $count . "#" . $config['graph_colours']['mixed'][1] . ":'UnRegistered         '";
    $rrd_options .= " GPRINT:UNREG" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:UNREG" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:UNREG" . $count . ":MAX:%3.0lf\l ";
}
