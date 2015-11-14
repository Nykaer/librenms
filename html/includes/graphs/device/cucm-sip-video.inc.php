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
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-SIP','ignore'=>0));

// We only care about our device id.
$COMPONENTS = $COMPONENTS[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Video Calls           Now      Avg      Max\\n'";
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

$COUNT = 0;
foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-SIP-".$ARRAY['label'].".rrd");

    if (file_exists($rrd_filename)) {
        // Stack the area on the second and subsequent DS's
        $STACK = "";
        if ($COUNT != 0) {
            $STACK = ":STACK ";
        }

        // Grab a color from the array.
        if ( isset($COLORS[$COUNT]) ) {
            $COLOR = $COLORS[$COUNT];
        }
        else {
            $COLOR = $COLORS[$COUNT-7];
        }

        $rrd_additions .= " DEF:DS" . $COUNT . "=" . $rrd_filename . ":callsvideo:AVERAGE ";
        $rrd_additions .= " CDEF:MOD" . $COUNT . "=DS" . $COUNT . ",8,* ";
        $rrd_additions .= " AREA:MOD" . $COUNT . "#" . $COLOR . ":'" . end_spacer ($COMPONENTS[$ID]['label'], 15) . "'" . $STACK;
        $rrd_additions .= " GPRINT:MOD" . $COUNT . ":LAST:%6.2lf%s ";
        $rrd_additions .= " GPRINT:MOD" . $COUNT . ":AVERAGE:%6.2lf%s ";
        $rrd_additions .= " GPRINT:MOD" . $COUNT . ":MAX:%6.2lf%s\\\l ";

        $COUNT++;
    }
}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
