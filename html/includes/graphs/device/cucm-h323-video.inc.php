<?php
/*
 * LibreNMS module to display Cisco CUCM H323 Video Details
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
$components = $component->getComponents($device['device_id'], array('type'=>'CUCM-H323', 'ignore'=>0));

// We only care about our device id.
$components = $components[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Video Calls                     Now      Avg      Max\\n'";

$count = 0;
foreach ($components as $id => $array) {
    $rrd_filename = rrd_name($device['hostname'], array('CUCM', 'H323', $array['label']));
    if (file_exists($rrd_filename)) {
        // Stack the area on the second and subsequent DS's
        $stack = "";
        if ($count != 0) {
            $stack = ":STACK ";
        }

        // Grab a color from the array.
        if (isset($config['graph_colours']['mixed'][$count])) {
            $color = $config['graph_colours']['mixed'][$count];
        } else {
            $color = $config['graph_colours']['oranges'][$count-7];
        }

        $rrd_options .= " DEF:DS" . $count . "=" . $rrd_filename . ":callsvideo:AVERAGE ";
        $rrd_options .= " CDEF:MOD" . $count . "=DS" . $count . ",8,* ";
        $rrd_options .= " AREA:MOD" . $count . "#" . $color . ":'" . str_pad(substr($components[$id]['label'], 0, 25), 25) . "'" . $stack;
        $rrd_options .= " GPRINT:MOD" . $count . ":LAST:%6.2lf%s ";
        $rrd_options .= " GPRINT:MOD" . $count . ":AVERAGE:%6.2lf%s ";
        $rrd_options .= " GPRINT:MOD" . $count . ":MAX:%6.2lf%s\l ";

        $count++;
    }
}
