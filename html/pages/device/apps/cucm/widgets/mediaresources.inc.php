<?php
require_once 'pages/device/apps/cucm/widgets/functions.inc.php';

$labels = array('AnnunciatorResource','MTPResource','TranscoderResource','VCBResource');
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
            <strong>Media Resources</strong>
        </div>
        <div class="panel-body">
    <?php
    foreach ($CUCM_BASIC as $ID => $ARRAY) {
        switch ($ARRAY['label']) {
            case 'AnnunciatorResource':
                echo "            <div>\n";
                $graph_array['type']   = 'device_cucm-basic-annunciatorresource';
                generate_widget_part($graph_array,$device,"Annunciator");
                echo "            </div>\n";
                break;
            case 'MTPResource':
                echo "            <div>\n";
                $graph_array['type']   = 'device_cucm-basic-mtpresource';
                generate_widget_part($graph_array,$device,"MTP");
                echo "            </div>\n";
                break;
            case 'TranscoderResource':
                echo "            <div>\n";
                $graph_array['type']   = 'device_cucm-basic-transcoderresource';
                generate_widget_part($graph_array,$device,"Transcoder");
                echo "            </div>\n";
                break;
            case 'VCBResource':
                echo "            <div>\n";
                $graph_array['type']   = 'device_cucm-basic-vcbresource';
                generate_widget_part($graph_array,$device,"VC Bridge");
                echo "            </div>\n";
                break;
        }
    }
    ?>
        </div>
    </div>
<?php
}//end if
