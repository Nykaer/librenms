<?php
require_once 'pages/device/cucm/widgets/functions.inc.php';

$labels = array('MOHMulticastResources','MOHUnicastResources');
if (component_exists($CUCM_BASIC,$labels)) {

    // Generic Graph Settings
    $graph_array['height'] = '100';
    $graph_array['width']  = '485';
    $graph_array['to']     = $config['time']['now'];
    $graph_array['device'] = $device['device_id'];
    $graph_array['from']   = $config['time']['day'];
    $graph_array['legend'] = 'no';

    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <strong>Music On Hold</strong>
        </div>
        <div class="panel-body">
<?php

    foreach ($CUCM_BASIC as $ID => $ARRAY) {
        switch ($ARRAY['label']) {
            case 'MOHMulticastResources':
                echo "            <div>\n";
                $graph_array['type']   = 'device_cucm-basic-mohmulticastresources';
                generate_widget_part($graph_array,$device,"Multicast");
                echo "            </div>\n";
                break;
            case 'MOHUnicastResources':
                echo "            <div>\n";
                $graph_array['type']   = 'device_cucm-basic-mohunicastresources';
                generate_widget_part($graph_array,$device,"Unicast");
                echo "            </div>\n";
                break;
        }
    }
    ?>
        </div>
    </div>
<?php
}//end if
