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
$rrd_additions = "";

$rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-RegisteredDevices.rrd");

if (file_exists($rrd_filename)) {
    $rrd_additions .= " DEF:REG" . $COUNT . "=" . $rrd_filename . ":phone-total:AVERAGE";
    $rrd_additions .= " AREA:REG" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'Registered           '";
    $rrd_additions .= " GPRINT:REG" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:REG" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:REG" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:PARTIAL" . $COUNT . "=" . $rrd_filename . ":phone-partial:AVERAGE";
    $rrd_additions .= " AREA:PARTIAL" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'Partial              '";
    $rrd_additions .= " GPRINT:PARTIAL" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:PARTIAL" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:PARTIAL" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:UNREG" . $COUNT . "=" . $rrd_filename . ":phone-failed:AVERAGE";
    $rrd_additions .= " AREA:UNREG" . $COUNT . "#" . $config['graph_colours']['mixed'][1] . ":'UnRegistered         '";
    $rrd_additions .= " GPRINT:UNREG" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:UNREG" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:UNREG" . $COUNT . ":MAX:%3.0lf\\\l ";

}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
