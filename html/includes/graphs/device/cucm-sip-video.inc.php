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

$component = new LibreNMS\Component();
$components = $component->getComponents($device['device_id'], array('type'=>'CUCM-SIP', 'ignore'=>0));

// We only care about our device id.
$components = $components[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Video Calls                    Now      Avg      Max\\n'";

$colours = array_merge($config['graph_colours']['mixed'], $config['graph_colours']['manycolours'], $config['graph_colours']['manycolours']);
$count = 0;
foreach ($components as $id => $array) {
    $rrd_filename = rrd_name($device['hostname'], array('CUCM-SIP', $array['label']));

    if (file_exists($rrd_filename)) {
        // Stack the area on the second and subsequent DS's
        $stack = "";
        if ($count != 0) {
            $stack = ":STACK ";
        }

        // Grab a colour from the array.
        if (isset($colours[$count])) {
            $colour = $colours[$count];
        } else {
            d_echo("\nError: Out of colours. Have: ".(count($colours)-1).", Requesting:".$count);
        }

        $rrd_options .= " DEF:DS" . $count . "=" . $rrd_filename . ":callsvideo:AVERAGE ";
        $rrd_options .= " CDEF:MOD" . $count . "=DS" . $count . ",8,* ";
        $rrd_options .= " AREA:MOD" . $count . "#" . $colour . ":'" . str_pad(substr($array['label'], 0, 25), 25) . "'" . $stack;
        $rrd_options .= " GPRINT:MOD" . $count . ":LAST:%6.2lf%s ";
        $rrd_options .= " GPRINT:MOD" . $count . ":AVERAGE:%6.2lf%s ";
        $rrd_options .= " GPRINT:MOD" . $count . ":MAX:%6.2lf%s\l ";

        $count++;
    }
}
