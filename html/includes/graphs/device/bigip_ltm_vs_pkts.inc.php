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
$options['filter']['type'] = array('=','bigip');
$components = $component->getComponents($device['device_id'], $options);

// We only care about our device id.
$components = $components[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'LTM Virtual Server Packets       Now    Min     Max\\n'";

$count = 0;
foreach ($components as $id => $array) {
    if ($array['category'] == 'LTMVirtualServer') {
        $rrd_filename = rrd_name($device['hostname'], array('cisco', 'otv', $array['endpoint'], 'mac'));

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

            $rrd_options .= " DEF:DS" . $count . "=" . $rrd_filename . ":count:AVERAGE ";
            $rrd_options .= " AREA:DS" . $count . "#" . $color . ":'" . str_pad(substr($components[$id]['endpoint'], 0, 15), 15) . "'" . $stack;
            $rrd_options .= " GPRINT:DS" . $count . ":LAST:%4.0lf%s ";
            $rrd_options .= " GPRINT:DS" . $count .    ":MIN:%4.0lf%s ";
            $rrd_options .= " GPRINT:DS" . $count . ":MAX:%4.0lf%s\\\l ";
            $count++;
        }
    }
}
