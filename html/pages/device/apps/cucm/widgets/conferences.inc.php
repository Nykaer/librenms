<?php
require_once 'pages/device/apps/cucm/widgets/functions.inc.php';

$labels = array('SWConferenceResource', 'HWConferenceResource');
if (component_exists($CUCM_BASIC,$labels)) {

    // Generic Graph Settings
    $graph_array['height'] = '100';
    $graph_array['width']  = '485';
    $graph_array['to']     = $config['time']['now'];
    $graph_array['device'] = $device['device_id'];
    $graph_array['from']   = $config['time']['day'];
    $graph_array['legend'] = 'no';

    $graph_array['type']   = 'device_cucm-basic-conferences';

    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <strong>Conferences in Progress</strong>
        </div>
        <div class="panel-body">
            <?= generate_widget_part($graph_array,$device); ?>
        </div>
    </div>
<?php
}//end if
