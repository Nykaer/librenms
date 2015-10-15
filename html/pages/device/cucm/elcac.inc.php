<?php

require_once "../includes/component.php";
$COMPONENT = new component();
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-ELCAC','ignore'=>0));

// Voice
echo '<div class=graphhead>Voice Calls in Progress</div>';
foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ("CUCM-ELCAC-".$ARRAY['label'].".rrd");
    if (file_exists ($rrd_filename)) {

        $graph_array['device'] = $device['device_id'];
        $graph_array['item'] = $ID;
        $graph_array['type'] = 'device_cucm-elcac-voice';
        echo '<div class=graphhead>'.$ARRAY['label'].'</div>';
        require 'includes/print-graphrow.inc.php';
    }
}

// Video
echo '<div class=graphhead>Video Calls in Progress</div>';
foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ("CUCM-ELCAC-".$ARRAY['label'].".rrd");
    if (file_exists ($rrd_filename)) {

        $graph_array['device'] = $device['device_id'];
        $graph_array['item'] = $ID;
        $graph_array['type'] = 'device_cucm-elcac-video';
        echo '<div class=graphhead>'.$ARRAY['label'].'</div>';
        require 'includes/print-graphrow.inc.php';
    }
}

// Immersive Video
echo '<div class=graphhead>Immersive Video Calls in Progress</div>';
foreach ($COMPONENTS as $ID => $ARRAY) {
    $rrd_filename = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ("CUCM-ELCAC-".$ARRAY['label'].".rrd");
    if (file_exists ($rrd_filename)) {

        $graph_array['device'] = $device['device_id'];
        $graph_array['item'] = $ID;
        $graph_array['type'] = 'device_cucm-elcac-immersive';
        echo '<div class=graphhead>'.$ARRAY['label'].'</div>';
        require 'includes/print-graphrow.inc.php';
    }
}
