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
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-Basic','ignore'=>0));

// We only care about our device id.
$COMPONENTS = $COMPONENTS[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_additions = "";

foreach ($COMPONENTS as $ID => $ARRAY) {
    if ($ARRAY['label'] == 'TranscoderResource') {
        $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-Basic-".$ARRAY['label'].".rrd");
        if (file_exists($rrd_filename)) {
            $rrd_additions .= " -l 0 -E ";
            $rrd_additions .= " COMMENT:'Transcoder Resources       Now   Avg   Max\\n'";

            $rrd_additions .= " DEF:DS1" . $COUNT . "=" . $rrd_filename . ":total:AVERAGE ";
            $rrd_additions .= " AREA:DS1" . $COUNT . "#" . $config['graph_colours']['mixed'][2] . ":'Total         '";
            $rrd_additions .= " GPRINT:DS1" . $COUNT . ":LAST:%3.0lf ";
            $rrd_additions .= " GPRINT:DS1" . $COUNT . ":AVERAGE:%3.0lf ";
            $rrd_additions .= " GPRINT:DS1" . $COUNT . ":MAX:%3.0lf\\\l ";

            $rrd_additions .= " DEF:DS2" . $COUNT . "=" . $rrd_filename . ":active:AVERAGE ";
            $rrd_additions .= " AREA:DS2" . $COUNT . "#" . $config['graph_colours']['mixed'][4] . ":'Active         '";
            $rrd_additions .= " GPRINT:DS2" . $COUNT . ":LAST:%3.0lf ";
            $rrd_additions .= " GPRINT:DS2" . $COUNT . ":AVERAGE:%3.0lf ";
            $rrd_additions .= " GPRINT:DS2" . $COUNT . ":MAX:%3.0lf\\\l ";
        }
    }
}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
