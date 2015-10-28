<?php

require_once '../includes/component.php';
$COMPONENT = new component();
$CUCM_BASIC = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-BASIC','ignore'=>0));
?>

<div class="container-fluid">
    <div class="row"><div class="col-md-12"></div></div>
    <div class="row">
        <div class="col-md-6">
<?php
// Left Pane
include 'pages/device/cucm/widgets/calls.inc.php';
include 'pages/device/cucm/widgets/moh.inc.php';
?>
        </div>
        <div class="col-md-6">
<?php
// Right Pane
include 'pages/device/cucm/widgets/alerts.inc.php';
include 'pages/device/cucm/widgets/conferences.inc.php';
include 'pages/device/cucm/widgets/mediaresources.inc.php';
?>
        </div>
    </div>
</div>
