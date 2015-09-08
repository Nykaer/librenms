<?php

/*
 * process $classes to get the RRD's and labels for each class-map
 */
require_once "../includes/component.php";
$COMPONENT = new component();
$options['filter']['type'] = array('=','Cisco-CBQOS');
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);

// Determine a policy to show.
if (isset($vars['policy'])) {
    $policy = $vars['policy'];
}
else {
    foreach ($COMPONENTS as $ID => $ARRAY) {
        if ( ($ARRAY['qos-type'] == 1) && ($ARRAY['ifindex'] == $port['ifIndex']) ) {
            // Found the first policy
            $policy = $ID;
            continue;
        }
    }
}

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'Class-Map                            Now   Avg  Max\\n'";
$rrd_additions = "";

$COLOR_PICKER = array(
    '0000EE','3FFF00','FF0000','FFF700','C000FF','FF5500','404000','000000','002e32','a9a9a9','404000','400000','a9b861','87963e','c5d17a','f4e6a9','364940','32374e','221f1f','c8ccc1','d29c0e','564539','5e7783','243746','c5d17a','87963e','4d3a30'
);

// Need to create a spacing function..


$COUNT = 0;
foreach ($COMPONENTS as $ID => $ARRAY) {
    if ( ($ARRAY['qos-type'] == 2) && ($ARRAY['parent'] == $COMPONENTS[$policy]['sp-obj']) ) {
        $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("port-".$ARRAY['ifindex']."-cbqos-".$ARRAY['sp-id']."-".$ARRAY['sp-obj'].".rrd");

        $rrd_additions .= " DEF:".$ID."=" . $rrd_filename . ":postbits:AVERAGE ";
//        $rrd_additions .= " CDEF:".$ID."_kbps=".$ID.",8,* ";
//        $rrd_additions .= " AREA:".$ID."#c099ff ";
        $rrd_additions .= " LINE1.25:".$ID."#".$COLOR_PICKER[$COUNT].":'".$COMPONENTS[$ID]['label']."' ";
        $rrd_additions .= " GPRINT:".$ID.":LAST:%6.2lf%s ";
        $rrd_additions .= " GPRINT:".$ID.":AVERAGE:%6.2lf%s ";
        $rrd_additions .= " GPRINT:".$ID.":MAX:%6.2lf%s\\\l ";
        $COUNT++;
    }
}

if ($rrd_additions == "") {
    // We didn't add any data points.
}
else {
    $rrd_options .= $rrd_additions;
}

$GRAPH_UNIT = "Bits/Sec";

