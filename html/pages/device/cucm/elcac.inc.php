<?php

require_once "../includes/component.php";
$COMPONENT = new component();
$options['type'] = 'CUCM-ELCAC';
$options['filter']['ignore'] = array('=',0);
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);

if (count($COMPONENTS) > 0) {
    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Voice</div>
        </div>
        <div class="panel-body">
    <?php
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
    ?>
        </div>
    </div>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Video</div>
        </div>
        <div class="panel-body">
    <?php
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
    ?>
        </div>
    </div>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Immersive Video</div>
        </div>
        <div class="panel-body">
            <?php
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
    ?>
        </div>
    </div>
<?php
}
