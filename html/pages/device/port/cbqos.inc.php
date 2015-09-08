<?php

$rrdarr = glob($config['rrd_dir'].'/'.$device['hostname'].'/port-'.$port['ifIndex'].'-cbqos-*.rrd');
if (!empty($rrdarr)) {
    require_once "../includes/component.php";
    $COMPONENT = new component();
    $options['filter']['type'] = array('=','Cisco-CBQOS');
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);

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
    echo "Policy: ".$policy."<br>";
    foreach ($COMPONENTS as $ID => $ARRAY) {
        if ( ($ARRAY['qos-type'] == 2) && ($ARRAY['parent'] == $COMPONENTS[$policy]['sp-obj']) ) {
//            // We have a child class
            array_push($CLASSES,$ID);
            echo $ID." - ".$ARRAY['label']."<br>";
        }
    }

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
