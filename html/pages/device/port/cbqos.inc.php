<?php

$rrdarr = glob($config['rrd_dir'].'/'.$device['hostname'].'/port-'.$port['ifIndex'].'-cbqos-*.rrd');
if (!empty($rrdarr)) {
    require_once 'includes/component.php';
    $COMPONENT = new component();
    $options['filter']['type'] = array('=','Cisco-CBQOS');
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);

    /*
     * Display the structure of the class maps applied to this interface.
     * Perhaps clickable links for each policy-map, not dropdown???
     *
     * $policy = component id for the policy to display
     * $classes = array of component ID's for child classes.
     *
     */



    $iid = $id;
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
