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

require_once "../includes/component.php";
$COMPONENT = new component();
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-ELCAC','ignore'=>0));

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
$rrd_options .= " COMMENT:'Voice Bandwidth (kbps)   Now  Avg  Max\\n'";
$rrd_additions = "";

if (isset($vars['item'])) {
    $ID = $vars['item'];
    // Have we found a valid location to display?
    $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-ELCAC-".$COMPONENTS[$ID]['label'].".rrd");

    if (file_exists($rrd_filename)) {
        $rrd_additions .= " DEF:TOT" . $COUNT . "=" . $rrd_filename . ":totalvoice:AVERAGE";
        $rrd_additions .= " DEF:AVA" . $COUNT . "=" . $rrd_filename . ":availablevoice:AVERAGE";
        $rrd_additions .= " CDEF:ACT" . $COUNT . "=TOT" . $COUNT . ",AVA,- ";

        $rrd_additions .= " AREA:TOT" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'Total               '";
        $rrd_additions .= " GPRINT:TOT" . $COUNT . ":LAST:%3.0lf";
        $rrd_additions .= " GPRINT:TOT" . $COUNT . ":AVERAGE:%3.0lf";
        $rrd_additions .= " GPRINT:TOT" . $COUNT . ":MAX:%3.0lf\\\l ";

        $rrd_additions .= " AREA:ACT" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'Used                '";
        $rrd_additions .= " GPRINT:ACT" . $COUNT . ":LAST:%3.0lf";
        $rrd_additions .= " GPRINT:ACT" . $COUNT . ":AVERAGE:%3.0lf";
        $rrd_additions .= " GPRINT:ACT" . $COUNT . ":MAX:%3.0lf\\\l";
    }
}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
