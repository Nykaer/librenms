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
$rrd_additions = "";

$rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-RegisteredDevices.rrd");

if (file_exists($rrd_filename)) {
    $rrd_additions .= " DEF:MOH" . $COUNT . "=" . $rrd_filename . ":mr-moh:AVERAGE";
    $rrd_additions .= " LINE1.25:MOH" . $COUNT . "#" . $config['graph_colours']['mixed'][1] . ":'MOH                    '";
    $rrd_additions .= " GPRINT:MOH" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:MOH" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:MOH" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:MTP" . $COUNT . "=" . $rrd_filename . ":mr-mtp:AVERAGE";
    $rrd_additions .= " LINE1.25:MTP" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'MTP                    '";
    $rrd_additions .= " GPRINT:MTP" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:MTP" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:MTP" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:XCODE" . $COUNT . "=" . $rrd_filename . ":mr-xcode:AVERAGE";
    $rrd_additions .= " LINE1.25:XCODE" . $COUNT . "#" . $config['graph_colours']['mixed'][3] . ":'XCODE                  '";
    $rrd_additions .= " GPRINT:XCODE" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:XCODE" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:XCODE" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:CFB" . $COUNT . "=" . $rrd_filename . ":mr-cfb:AVERAGE";
    $rrd_additions .= " LINE1.25:CFB" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'CFB                    '";
    $rrd_additions .= " GPRINT:CFB" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:CFB" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:CFB" . $COUNT . ":MAX:%3.0lf\\\l ";
}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
