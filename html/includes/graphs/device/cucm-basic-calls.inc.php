<?php
/*
 * LibreNMS module to display Cisco CUCM Calls Details
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$component = new LibreNMS\Component();
$components = $component->getComponents($device['device_id'], array('type'=>'CUCM-Basic', 'ignore'=>0));

// We only care about our device id.
$components = $components[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'System Calls         Now   Avg   Max\\n'";

foreach ($components as $id => $array) {
    if ($array['label'] == 'Calls') {
        $rrd_filename = rrd_name($device['hostname'], array('CUCM-Basic', $array['label']));
        if (file_exists($rrd_filename)) {
            $rrd_options .= " DEF:DS1" . $count . "=" . $rrd_filename . ":calls:AVERAGE ";
            $rrd_options .= " AREA:DS1" . $count . "#" . $config['graph_colours']['mixed'][2] . ":'Total Calls    '";
            $rrd_options .= " GPRINT:DS1" . $count . ":LAST:%3.0lf ";
            $rrd_options .= " GPRINT:DS1" . $count . ":AVERAGE:%3.0lf ";
            $rrd_options .= " GPRINT:DS1" . $count . ":MAX:%3.0lf\l ";

            $rrd_options .= " DEF:DS2" . $count . "=" . $rrd_filename . ":videocalls:AVERAGE ";
            $rrd_options .= " AREA:DS2" . $count . "#" . $config['graph_colours']['mixed'][4] . ":'Video Calls    '";
            $rrd_options .= " GPRINT:DS2" . $count . ":LAST:%3.0lf ";
            $rrd_options .= " GPRINT:DS2" . $count . ":AVERAGE:%3.0lf ";
            $rrd_options .= " GPRINT:DS2" . $count . ":MAX:%3.0lf\l ";

            $rrd_options .= " DEF:DS3" . $count . "=" . $rrd_filename . ":encryptedcalls:AVERAGE ";
            $rrd_options .= " AREA:DS3" . $count . "#" . $config['graph_colours']['mixed'][1] . ":'Encrypted Calls'";
            $rrd_options .= " GPRINT:DS3" . $count . ":LAST:%3.0lf ";
            $rrd_options .= " GPRINT:DS3" . $count . ":AVERAGE:%3.0lf ";
            $rrd_options .= " GPRINT:DS3" . $count . ":MAX:%3.0lf\l ";
        }
    }
}
