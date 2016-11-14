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
$rrd_options .= " COMMENT:'Registered Media Resources   Now  Avg  Max\\n'";

$rrd_filename = rrd_name($device['hostname'], array('CUCM', 'RegisteredDevices'));

if (file_exists($rrd_filename)) {
    $rrd_options .= " DEF:MOH" . $count . "=" . $rrd_filename . ":mr-moh:AVERAGE";
    $rrd_options .= " LINE1.25:MOH" . $count . "#" . $config['graph_colours']['mixed'][1] . ":'MOH                    '";
    $rrd_options .= " GPRINT:MOH" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:MOH" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:MOH" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:MTP" . $count . "=" . $rrd_filename . ":mr-mtp:AVERAGE";
    $rrd_options .= " LINE1.25:MTP" . $count . "#" . $config['graph_colours']['mixed'][2] . ":'MTP                    '";
    $rrd_options .= " GPRINT:MTP" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:MTP" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:MTP" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:XCODE" . $count . "=" . $rrd_filename . ":mr-xcode:AVERAGE";
    $rrd_options .= " LINE1.25:XCODE" . $count . "#" . $config['graph_colours']['mixed'][3] . ":'XCODE                  '";
    $rrd_options .= " GPRINT:XCODE" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:XCODE" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:XCODE" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:CFB" . $count . "=" . $rrd_filename . ":mr-cfb:AVERAGE";
    $rrd_options .= " LINE1.25:CFB" . $count . "#" . $config['graph_colours']['mixed'][4] . ":'CFB                    '";
    $rrd_options .= " GPRINT:CFB" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:CFB" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:CFB" . $count . ":MAX:%3.0lf\l ";
}
