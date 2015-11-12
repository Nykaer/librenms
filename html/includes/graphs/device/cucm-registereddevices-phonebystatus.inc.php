<?php
/*
 * LibreNMS module to display CUCM Registered Devices
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Phone Registration Status  Now  Avg  Max\\n'";
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

$rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-RegisteredDevices.rrd");

if (file_exists($rrd_filename)) {
    $rrd_additions .= " DEF:REG" . $COUNT . "=" . $rrd_filename . ":phone-total:AVERAGE";
    $rrd_additions .= " AREA:REG" . $COUNT . "#" . $COLORS[0] . ":'" . end_spacer ('Registered', 20) . "'";
    $rrd_additions .= " GPRINT:REG" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:REG" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:REG" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:PARTIAL" . $COUNT . "=" . $rrd_filename . ":phone-partial:AVERAGE";
    $rrd_additions .= " AREA:PARTIAL" . $COUNT . "#" . $COLORS[1] . ":'" . end_spacer ('Partial', 20) . "'";
    $rrd_additions .= " GPRINT:PARTIAL" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:PARTIAL" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:PARTIAL" . $COUNT . ":MAX:%3.0lf\\\l ";

    $rrd_additions .= " DEF:UNREG" . $COUNT . "=" . $rrd_filename . ":phone-failed:AVERAGE";
    $rrd_additions .= " AREA:UNREG" . $COUNT . "#" . $COLORS[0] . ":'" . end_spacer ('UnRegistered', 20) . "'";
    $rrd_additions .= " GPRINT:UNREG" . $COUNT . ":LAST:%3.0lf";
    $rrd_additions .= " GPRINT:UNREG" . $COUNT . ":AVERAGE:%3.0lf";
    $rrd_additions .= " GPRINT:UNREG" . $COUNT . ":MAX:%3.0lf\\\l ";

}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
