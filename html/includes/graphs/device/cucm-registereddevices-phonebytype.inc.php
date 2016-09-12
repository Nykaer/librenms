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
$rrd_options .= " COMMENT:'Phone Registration        Now  Avg  Max\\n'";
$rrd_options = "";

$rrd_filename = rrd_name($device['hostname'], array('CUCM', 'RegisteredDevices'));

if (file_exists($rrd_filename)) {
    $rrd_options .= " DEF:SIP" . $COUNT . "=" . $rrd_filename . ":phone-sip:AVERAGE";
    $rrd_options .= " AREA:SIP" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'SIP Phones          '";
    $rrd_options .= " GPRINT:SIP" . $COUNT . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:SIP" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:SIP" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_options .= " DEF:SCCP" . $COUNT . "=" . $rrd_filename . ":phone-sccp:AVERAGE";
    $rrd_options .= " AREA:SCCP" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'SCCP Phones         ':STACK";
    $rrd_options .= " GPRINT:SCCP" . $COUNT . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:SCCP" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:SCCP" . $COUNT . ":MAX:%3.0lf\\\l ";
}
