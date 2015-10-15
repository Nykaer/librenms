<?php

require_once "../includes/component.php";
$COMPONENT = new component();
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-SIP','ignore'=>0));

// All
foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ("CUCM-SIP-".$ARRAY['label'].".rrd");
    if (file_exists ($rrd_filename)) {

        $graph_array['device'] = $device['device_id'];
        $graph_array['item'] = $ID;
        $graph_array['type'] = 'device_cucm-sip-all';
        echo '<div class=graphhead>All Calls</div>';
        require 'includes/print-graphrow.inc.php';
    }
}

// Video
foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ("CUCM-SIP-".$ARRAY['label'].".rrd");
    if (file_exists ($rrd_filename)) {

        $graph_array['device'] = $device['device_id'];
        $graph_array['item'] = $ID;
        $graph_array['type'] = 'device_cucm-sip-video';
        echo '<div class=graphhead>Video Calls</div>';
        require 'includes/print-graphrow.inc.php';
    }
}