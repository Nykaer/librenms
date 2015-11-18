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
$rrd_additions = "";

$rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-RegisteredDevices.rrd");

if (file_exists($rrd_filename)) {
    $rrd_additions .= " DEF:PHONE" . $COUNT . "=" . $rrd_filename . ":phone-total:AVERAGE";
    $rrd_additions .= " LINE1.25:PHONE" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'Phones              '";
    $rrd_additions .= " GPRINT:PHONE" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:PHONE" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:PHONE" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:GW" . $COUNT . "=" . $rrd_filename . ":gw-total:AVERAGE";
    $rrd_additions .= " LINE1.25:GW" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'Gateways            '";
    $rrd_additions .= " GPRINT:GW" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:GW" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:GW" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:MR" . $COUNT . "=" . $rrd_filename . ":mr-total:AVERAGE";
    $rrd_additions .= " LINE1.25:MR" . $COUNT . "#" . $config['graph_colours']['mixed'][1] . ":'Media Resources     '";
    $rrd_additions .= " GPRINT:MR" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:MR" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:MR" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:H323" . $COUNT . "=" . $rrd_filename . ":h323-total:AVERAGE";
    $rrd_additions .= " LINE1.25:H323" . $COUNT . "#" . $config['graph_colours']['mixed'][3] . ":'H323 Endpoints      '";
    $rrd_additions .= " GPRINT:H323" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:H323" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:H323" . $COUNT . ":MAX:%3.0lf\\\l ";

}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
