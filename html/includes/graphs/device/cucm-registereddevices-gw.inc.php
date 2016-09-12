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
$rrd_options .= " COMMENT:'Registered Gateways   Now  Avg  Max\\n'";

$rrd_filename = rrd_name($device['hostname'], array('CUCM', 'RegisteredDevices'));

if (file_exists($rrd_filename)) {
    $rrd_options .= " DEF:FXS" . $COUNT . "=" . $rrd_filename . ":gw-fxs:AVERAGE";
    $rrd_options .= " LINE1.25:FXS" . $COUNT . "#" . $config['graph_colours']['mixed'][1] . ":'MGCP FXS        '";
    $rrd_options .= " GPRINT:FXS" . $COUNT . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:FXS" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:FXS" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_options .= " DEF:FXO" . $COUNT . "=" . $rrd_filename . ":gw-fxo:AVERAGE";
    $rrd_options .= " LINE1.25:FXO" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'MGCP FXO        '";
    $rrd_options .= " GPRINT:FXO" . $COUNT . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:FXO" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:FXO" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_options .= " DEF:T1CAS" . $COUNT . "=" . $rrd_filename . ":gw-t1cas:AVERAGE";
    $rrd_options .= " LINE1.25:T1CAS" . $COUNT . "#" . $config['graph_colours']['mixed'][3] . ":'MGCP T1 CAS     '";
    $rrd_options .= " GPRINT:T1CAS" . $COUNT . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:T1CAS" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:T1CAS" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_options .= " DEF:PRI" . $COUNT . "=" . $rrd_filename . ":gw-pri:AVERAGE";
    $rrd_options .= " LINE1.25:PRI" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'MGCP PRI        '";
    $rrd_options .= " GPRINT:PRI" . $COUNT . ":LAST:%3.0lf";
    $rrd_options .= " GPRINT:PRI" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_options .= " GPRINT:PRI" . $COUNT . ":MAX:%3.0lf\\\l ";
}
