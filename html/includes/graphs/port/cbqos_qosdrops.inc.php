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
$options['filter']['type'] = array('=','Cisco-CBQOS');
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);

// Determine a policy to show.
if (!isset($vars['policy'])) {
    foreach ($COMPONENTS as $ID => $ARRAY) {
        if ( ($ARRAY['qos-type'] == 1) && ($ARRAY['ifindex'] == $port['ifIndex'])  && ($ARRAY['parent'] == 0) ) {
            // Found the first policy
            $vars['policy'] = $ID;
            continue;
        }
    }
}

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Class-Map              Now      Avg      Max\\n'";
$rrd_additions = "";

$COLORS = array(
    array('EA644A','CC3118'),   // Red
    array('EC9D48','CC7016'),   // Orange
    array('ECD748','C9B215'),   // Yellow
    array('54EC48','24BC14'),   // Green
    array('48C4EC','1598C3'),   // Blue
    array('DE48EC','B415C7'),   // Pink
    array('7648EC','4D18E4'),   // Purple
    array('806517','52410f'),   // Brown
    array('787878','3b3b3b'),   // Grey
);

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
    if ( ($ARRAY['qos-type'] == 2) && ($ARRAY['parent'] == $COMPONENTS[$vars['policy']]['sp-obj']) && ($ARRAY['sp-id'] == $COMPONENTS[$vars['policy']]['sp-id'])) {
        $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("port-".$ARRAY['ifindex']."-cbqos-".$ARRAY['sp-id']."-".$ARRAY['sp-obj'].".rrd");

        // Stack the area on the second and subsequent DS's
        $STACK = "";
        if ($COUNT != 0) {
            $STACK = ":STACK";
        }

        // RRD magic to make it pretty, see: http://oss.oetiker.ch/rrdtool-trac/wiki/OutlinedAreaGraph
        $rrd_additions .= " DEF:DS".$COUNT."=" . $rrd_filename . ":qosdrops:AVERAGE ";
        $rrd_loop = "";
        for ($i=0;$i<=$COUNT;$i++) {
            $rrd_loop .= "DS".$i.",";
        }
        for ($i=0;$i<$COUNT;$i++) {
            $rrd_loop .= "+,";
        }
        $rrd_additions .= " CDEF:LN".$COUNT."=DS".$COUNT.",".$rrd_loop."UNKN,IF ";

        $rrd_additions .= " AREA:DS".$COUNT."#".$COLORS[$COUNT][0].":'".end_spacer($COMPONENTS[$ID]['label'],15)."'".$STACK." ";
        $rrd_additions .= " LINE1:LN".$COUNT."#".$COLORS[$COUNT][1]." ";
        $rrd_additions .= " GPRINT:DS".$COUNT.":LAST:%6.2lf%s ";
        $rrd_additions .= " GPRINT:DS".$COUNT.":AVERAGE:%6.2lf%s ";
        $rrd_additions .= " GPRINT:DS".$COUNT.":MAX:%6.2lf%s\\\l ";

        $COUNT++;
    }
}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}
