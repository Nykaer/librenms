<?php

require_once '../includes/component.php';
$COMPONENT = new component();
$options = array();
$options['type'] = 'CUCM-BASIC';
$options['filter']['ignore'] = array('=',0);
$CUCM_BASIC = $COMPONENT->getComponents($device['device_id'],$options);
$CUCM_BASIC = $CUCM_BASIC[$device['device_id']];

?>

<div class="container-fluid">
    <div class="row"><div class="col-md-12"></div></div>
    <div class="row">
        <div class="col-md-6">
<?php
// Left Pane
include 'pages/device/apps/cucm/widgets/calls.inc.php';
include 'pages/device/apps/cucm/widgets/registereddevices.inc.php';
include 'pages/device/apps/cucm/widgets/moh.inc.php';
?>
        </div>
        <div class="col-md-6">
<?php
// Right Pane
include 'pages/device/apps/cucm/widgets/alerts.inc.php';
include 'pages/device/apps/cucm/widgets/conferences.inc.php';
include 'pages/device/apps/cucm/widgets/mediaresources.inc.php';
?>
        </div>
    </div>
</div>
