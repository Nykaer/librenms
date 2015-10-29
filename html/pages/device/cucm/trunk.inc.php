<?php

require_once "../includes/component.php";
$COMPONENT = new component();
$options['filter']['ignore'] = array('=',0);
$graph_array['device'] = $device['device_id'];
$options['type'] = 'CUCM-SIP';
$SIP = $COMPONENT->getComponents($device['device_id'],$options);

if (count($SIP) > 0) {
    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>SIP: All Calls</div>
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
            <div class=graphhead>SIP: Video Calls</div>
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

$options['type'] = 'CUCM-H323';
$H323 = $COMPONENT->getComponents($device['device_id'],$options);

if (count($H323) > 0) {
    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>H323: All Calls</div>
        </div>
        <div class="panel-body">
            <?php
            $graph_array['type'] = 'device_cucm-h323-all';
            require 'includes/print-graphrow.inc.php';
            ?>
        </div>
    </div>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>H323: Video Calls</div>
        </div>
        <div class="panel-body">
            <?php
            $graph_array['type'] = 'device_cucm-h323-video';
            require 'includes/print-graphrow.inc.php';
            ?>
        </div>
    </div>
<?php
}
