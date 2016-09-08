<?php
/*
 * LibreNMS module to display Cisco Class-Based QoS Details
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$COMPONENT = new LibreNMS\Component();
$COMPONENTS = $COMPONENT->getComponents($device['device_id'], array('type'=>'CUCM-ELCAC', 'ignore'=>0));

// We only care about our device id.
$COMPONENTS = $COMPONENTS[$device['device_id']];

// Determine a location to show.
if (!isset($vars['item'])) {
    foreach ($COMPONENTS as $ID => $ARRAY) {
        $vars['item'] = $ID;
        continue;
    }
}

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Video Bandwidth (kbps)   Now  Avg  Max\\n'";

if (isset($vars['item'])) {
    $ID = $vars['item'];
    // Have we found a valid location to display?
    $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-ELCAC-".$COMPONENTS[$ID]['label'].".rrd");

    if (file_exists($rrd_filename)) {
        $rrd_options .= " DEF:TOT" . $COUNT . "=" . $rrd_filename . ":totalvideo:AVERAGE";
        $rrd_options .= " DEF:AVA" . $COUNT . "=" . $rrd_filename . ":availablevideo:AVERAGE";
        $rrd_options .= " CDEF:ACT" . $COUNT . "=TOT" . $COUNT . ",AVA,- ";

        $rrd_options .= " AREA:TOT" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'Total               '";
        $rrd_options .= " GPRINT:TOT" . $COUNT . ":LAST:%3.0lf";
        $rrd_options .= " GPRINT:TOT" . $COUNT . ":AVERAGE:%3.0lf";
        $rrd_options .= " GPRINT:TOT" . $COUNT . ":MAX:%3.0lf\\\l ";

        $rrd_options .= " AREA:ACT" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'Used                '";
        $rrd_options .= " GPRINT:ACT" . $COUNT . ":LAST:%3.0lf";
        $rrd_options .= " GPRINT:ACT" . $COUNT . ":AVERAGE:%3.0lf";
        $rrd_options .= " GPRINT:ACT" . $COUNT . ":MAX:%3.0lf\\\l";
    }
}
