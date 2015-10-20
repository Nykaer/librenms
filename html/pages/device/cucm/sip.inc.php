<?php

require_once "../includes/component.php";
$COMPONENT = new component();
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-SIP','ignore'=>0));

if (count($COMPONENTS) > 0) {
    $graph_array['device'] = $device['device_id'];

    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>All Calls</div>
        </div>
        <div class="panel-body">
    <?php
    $graph_array['type'] = 'device_cucm-sip-all';
    require 'includes/print-graphrow.inc.php';
    ?>
        </div>
    </div>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Video Calls</div>
        </div>
        <div class="panel-body">
    <?php
    $graph_array['type'] = 'device_cucm-sip-video';
    require 'includes/print-graphrow.inc.php';
    ?>
        </div>
    </div>
<?php
}
