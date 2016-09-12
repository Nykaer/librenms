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
$COMPONENTS = $COMPONENT->getComponents($device['device_id'], array('type'=>'CUCM-Basic', 'ignore'=>0));

// We only care about our device id.
$COMPONENTS = $COMPONENTS[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Audio Conferences         Now   Avg   Max\\n'";

foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = rrd_name($device['hostname'], array('CUCM', 'Basic', $ARRAY['label']));
    if ($ARRAY['label'] == 'HWConferenceResource') {
        if (file_exists($rrd_filename)) {
            $rrd_options .= " DEF:DS1" . $COUNT . "=" . $rrd_filename . ":conferences:AVERAGE ";
            $rrd_options .= " AREA:DS1" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'Total Hardware      '";
            $rrd_options .= " GPRINT:DS1" . $COUNT . ":LAST:%3.0lf ";
            $rrd_options .= " GPRINT:DS1" . $COUNT . ":AVERAGE:%3.0lf ";
            $rrd_options .= " GPRINT:DS1" . $COUNT . ":MAX:%3.0lf\\\l ";
        }
    } elseif ($ARRAY['label'] == 'SWConferenceResource') {
        if (file_exists($rrd_filename)) {
            $rrd_options .= " DEF:DS2" . $COUNT . "=" . $rrd_filename . ":conferences:AVERAGE ";
            $rrd_options .= " AREA:DS2" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'Total Software      '";
            $rrd_options .= " GPRINT:DS2" . $COUNT . ":LAST:%3.0lf ";
            $rrd_options .= " GPRINT:DS2" . $COUNT . ":AVERAGE:%3.0lf ";
            $rrd_options .= " GPRINT:DS2" . $COUNT . ":MAX:%3.0lf\\\l ";
        }
    }
}
