<?php
/*
 * LibreNMS module to display Cisco CUCM ELCAC Voice Details
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
$components = $component->getComponents($device['device_id'], array('type'=>'CUCM-ELCAC', 'ignore'=>0));

// We only care about our device id.
$components = $components[$device['device_id']];

// Determine a location to show.
if (!isset($vars['item'])) {
    foreach ($components as $id => $array) {
        $vars['item'] = $id;
        continue;
    }
}

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Voice Bandwidth (kbps)   Now  Avg  Max\\n'";

if (isset($vars['item'])) {
    $id = $vars['item'];
    // Have we found a valid location to display?
    $rrd_filename = rrd_name($device['hostname'], array('CUCM-ELCAC', $components[$id]['label']));
    if (file_exists($rrd_filename)) {
        $rrd_options .= " DEF:TOT" . $count . "=" . $rrd_filename . ":totalvoice:AVERAGE";
        $rrd_options .= " DEF:AVA" . $count . "=" . $rrd_filename . ":availablevoice:AVERAGE";
        $rrd_options .= " CDEF:ACT" . $count . "=TOT" . $count . ",AVA,- ";

        $rrd_options .= " AREA:TOT" . $count . "#" . $config['graph_colours']['mixed'][2] . ":'Total               '";
        $rrd_options .= " GPRINT:TOT" . $count . ":LAST:%3.0lf";
        $rrd_options .= " GPRINT:TOT" . $count . ":AVERAGE:%3.0lf";
        $rrd_options .= " GPRINT:TOT" . $count . ":MAX:%3.0lf\l ";

        $rrd_options .= " AREA:ACT" . $count . "#" . $config['graph_colours']['mixed'][4] . ":'Used                '";
        $rrd_options .= " GPRINT:ACT" . $count . ":LAST:%3.0lf";
        $rrd_options .= " GPRINT:ACT" . $count . ":AVERAGE:%3.0lf";
        $rrd_options .= " GPRINT:ACT" . $count . ":MAX:%3.0lf\l";
    }
}
