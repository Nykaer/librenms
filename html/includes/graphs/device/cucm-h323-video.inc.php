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
$COMPONENTS = $COMPONENT->getComponents($device['device_id'], array('type'=>'CUCM-H323', 'ignore'=>0));

// We only care about our device id.
$COMPONENTS = $COMPONENTS[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Video Calls                     Now      Avg      Max\\n'";

$COUNT = 0;
foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-H323-".$ARRAY['label'].".rrd");

    if (file_exists($rrd_filename)) {
        // Stack the area on the second and subsequent DS's
        $STACK = "";
        if ($COUNT != 0) {
            $STACK = ":STACK ";
        }

        // Grab a color from the array.
        if ( isset($config['graph_colours']['mixed'][$COUNT]) ) {
            $COLOR = $config['graph_colours']['mixed'][$COUNT];
        } else {
            $COLOR = $config['graph_colours']['oranges'][$COUNT-7];
        }

        $rrd_options .= " DEF:DS" . $COUNT . "=" . $rrd_filename . ":callsvideo:AVERAGE ";
        $rrd_options .= " CDEF:MOD" . $COUNT . "=DS" . $COUNT . ",8,* ";
        $rrd_options .= " AREA:MOD" . $COUNT . "#" . $COLOR . ":'" . str_pad(substr($COMPONENTS[$ID]['label'], 0, 25), 25) . "'" . $STACK;
        $rrd_options .= " GPRINT:MOD" . $COUNT . ":LAST:%6.2lf%s ";
        $rrd_options .= " GPRINT:MOD" . $COUNT . ":AVERAGE:%6.2lf%s ";
        $rrd_options .= " GPRINT:MOD" . $COUNT . ":MAX:%6.2lf%s\\\l ";

        $COUNT++;
    }
}
