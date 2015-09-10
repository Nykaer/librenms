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

function find_child($COMPONENTS,$parent,$level) {
    foreach($COMPONENTS as $ID => $ARRAY) {
        if (($ARRAY['parent'] == $COMPONENTS[$parent]['sp-obj']) && ($ARRAY['sp-id'] == $COMPONENTS[$parent]['sp-id'])) {
            // Yay, we found a child.
            for ($i=0;$i<$level;$i++) {
                echo "-";
            }
            echo "ID: ".$ARRAY['sp-obj'].", Type: ".$ARRAY['qos-type'].", Label: ".$ARRAY['label']."<br>\n";
            find_child($COMPONENTS,$ID,$level+1);
        }
    }
}

$rrdarr = glob($config['rrd_dir'].'/'.$device['hostname'].'/port-'.$port['ifIndex'].'-cbqos-*.rrd');
if (!empty($rrdarr)) {
    require_once "../includes/component.php";
    $COMPONENT = new component();
    $options['filter']['type'] = array('=','Cisco-CBQOS');
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);

    if (isset($vars['policy'])) {
        $graph_array['policy'] = $vars['policy'];
    }
    else {
        foreach ($COMPONENTS as $ID => $ARRAY) {
            if ( ($ARRAY['qos-type'] == 1) && ($ARRAY['ifindex'] == $port['ifIndex']) ) {
                // Found the first policy
                $graph_array['policy'] = $ID;
                continue;
            }
        }
    }

    $INGRESS = array();
    $EGRESS = array();
    /*
     * Display the structure of the class maps applied to this interface.
     * Perhaps clickable links for each policy-map, not dropdown???
     *
     * $policy = component id for the policy to display
     * $classes = array of component ID's for child classes.
     *
     */
    $CLASSES = array();
    echo "Policy: ".$graph_array['policy']." - ".$COMPONENTS[$graph_array['policy']]['label']."<br>";
    find_child($COMPONENTS,$graph_array['policy'],1);
//    foreach ($COMPONENTS as $ID => $ARRAY) {
//        if ( ($ARRAY['qos-type'] == 2) && ($ARRAY['parent'] == $COMPONENTS[$graph_array['policy']]['sp-obj']) ) {
//            // We have a child class
//            array_push($CLASSES,$ID);
//            echo $ID." - ".$ARRAY['label']."<br>";
//        }
//    }

//    $iid = $id;
    echo '<div class=graphhead>Traffic by CBQoS Class</div>';
    $graph_type = 'port_cbqos_traffic';
    include 'includes/print-interface-graphs.inc.php';

    echo '<div class=graphhead>QoS Drops by CBQoS Class</div>';
    $graph_type = 'port_cbqos_bufferdrops';
    include 'includes/print-interface-graphs.inc.php';

    echo '<div class=graphhead>Buffer Drops by CBQoS Class</div>';
    $graph_type = 'port_cbqos_qosdrops';
    include 'includes/print-interface-graphs.inc.php';
}
