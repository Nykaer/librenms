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

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Multicast MOH Resources       Now   Avg   Max\\n'";
$rrd_additions = "";

$COLORS = array( 'EA644A','EC9D48','ECD748','54EC48','48C4EC','DE48EC','7648EC' );

function end_spacer($text,$length) {
    // Add spaces to the end of $text up until $length
    if (strlen($text) < $length) {
        // $text is shorter than $length, pad.
        return str_pad($text, $length);
    }
    elseif (strlen($text) > $length) {
        // $text is already longer than $length, truncate.
        return substr($text, 0, $length);
    }
    else {
        // $text must equal $length, return.
        return $text;
    }
}

foreach ($COMPONENTS as $ID => $ARRAY) {
    if ($ARRAY['label'] == 'MOHMulticastResources') {
        $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-Basic-".$ARRAY['label'].".rrd");
        if (file_exists($rrd_filename)) {

            $rrd_additions .= " DEF:DS1" . $COUNT . "=" . $rrd_filename . ":total:AVERAGE ";
            $rrd_additions .= " AREA:DS1" . $COUNT . "#" . $COLORS[0] . ":'" . end_spacer ('Total', 15) . "'";
            $rrd_additions .= " GPRINT:DS1" . $COUNT . ":LAST:%3.0lf ";
            $rrd_additions .= " GPRINT:DS1" . $COUNT . ":AVERAGE:%3.0lf ";
            $rrd_additions .= " GPRINT:DS1" . $COUNT . ":MAX:%3.0lf\\\l ";

            $rrd_additions .= " DEF:DS2" . $COUNT . "=" . $rrd_filename . ":active:AVERAGE ";
            $rrd_additions .= " AREA:DS2" . $COUNT . "#" . $COLORS[1] . ":'" . end_spacer ('Active', 15) . "'";
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
