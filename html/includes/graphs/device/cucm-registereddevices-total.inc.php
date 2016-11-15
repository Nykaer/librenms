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
$rrd_options .= " COMMENT:'Registered Devices        Now  Avg  Max\\n'";

$rrd_filename = rrd_name($device['hostname'], array('CUCM', 'RegisteredDevices'));

if (file_exists($rrd_filename)) {
    $rrd_options .= " DEF:PHONE" . $count . "=" . $rrd_filename . ":phone-total:AVERAGE";
    $rrd_options .= " LINE1.25:PHONE" . $count . "#" . $config['graph_colours']['mixed'][2] . ":'Phones              '";
    $rrd_options .= " GPRINT:PHONE" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:PHONE" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:PHONE" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:GW" . $count . "=" . $rrd_filename . ":gw-total:AVERAGE";
    $rrd_options .= " LINE1.25:GW" . $count . "#" . $config['graph_colours']['mixed'][4] . ":'Gateways            '";
    $rrd_options .= " GPRINT:GW" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:GW" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:GW" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:MR" . $count . "=" . $rrd_filename . ":mr-total:AVERAGE";
    $rrd_options .= " LINE1.25:MR" . $count . "#" . $config['graph_colours']['mixed'][1] . ":'Media Resources     '";
    $rrd_options .= " GPRINT:MR" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:MR" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:MR" . $count . ":MAX:%3.0lf\l ";

    $rrd_options .= " DEF:H323" . $count . "=" . $rrd_filename . ":h323-total:AVERAGE";
    $rrd_options .= " LINE1.25:H323" . $count . "#" . $config['graph_colours']['mixed'][3] . ":'H323 Endpoints      '";
    $rrd_options .= " GPRINT:H323" . $count . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:H323" . $count . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:H323" . $count . ":MAX:%3.0lf\l ";
}
