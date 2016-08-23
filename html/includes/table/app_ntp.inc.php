<?php

$component = new LibreNMS\Component();
$options = array();
$options['filter']['ignore'] = array('=',0);
$options['type'] = 'ntp';
$components = $component->getComponents(null, $options);

$count = 0;
// Loop through each device in the component array
foreach ($components as $devid => $comp) {
    $device = device_by_id_cache($devid);

    // Loop through each component
    foreach ($comp as $compid => $array) {
        $display = true;
        if ($_POST['view'] == 'error') {
            // Only display peers with errors
            if ($array['status'] != 2) {
                $display = false;
            }
        }
        if ($array['status'] == 2) {
            $status = 'class="danger"';
        } else {
            $status = '';
        }

        if ($display === true) {
            $device_link = generate_device_link($device, null, array('tab' => 'apps', 'app' => 'ntp'));
            $count++;

            $graph_array = array();
            $graph_array['device'] = $device['device_id'];
            $graph_array['width']  = 80;
            $graph_array['height'] = 20;

            // Which graph type do we want?
            if ($_POST['graph'] == "stratum") {
                $graph_array['type']   = 'device_ntp_stratum';
            } elseif ($_POST['graph'] == "offset") {
                $graph_array['type']   = 'device_ntp_offset';
            } elseif ($_POST['graph'] == "delay") {
                $graph_array['type']   = 'device_ntp_delay';
            } elseif ($_POST['graph'] == "dispersion") {
                $graph_array['type']   = 'device_ntp_dispersion';
            } else {
                // No Graph
                unset($graph_array);
            }

            // Do we want a graph.
            if (is_array($graph_array)) {
                $return_data = true;
                require 'includes/print-minigraph.inc.php';
            } else {
                $minigraph = "&nbsp;";
            }
            $response[] = array(
                'device'    => $device_link,
                'peer'      => $array['peer'],
                'stratum'   => $array['stratum'],
                'graph'     => $minigraph,
                'error'     => $array['error'],
            );

        } // End if display
    } // End foreach component
} // End foreach device

// If there are no results, let the user know.
if ($count == 0) {
    $response = array();
}

$output = array(
    'current'  => $current,
    'rowCount' => $rowCount,
    'rows'     => $response,
    'total'    => $count,
);
echo _json_encode($output);
