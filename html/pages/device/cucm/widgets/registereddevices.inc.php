<?php

$rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-RegisteredDevices.rrd");
if (file_exists ($rrd_filename)) {

    $graph_array['height'] = '100';
    $graph_array['width']  = '485';
    $graph_array['to']     = $config['time']['now'];
    $graph_array['device'] = $device['device_id'];
    $graph_array['type'] = 'device_cucm-registereddevices-total';
    $graph_array['from']   = $config['time']['day'];
    $graph_array['legend'] = 'no';
    $graph = generate_lazy_graph_tag($graph_array);

    $link_array         = $graph_array;
    $link_array['page'] = 'graphs';
    unset($link_array['height'], $link_array['width']);
    $link = generate_url($link_array);

    $graph_array['width'] = '210';
    $overlib_content      = generate_overlib_content($graph_array, $device['hostname'].' - Registered Devices');

    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <strong>Registered Devices</strong>
        </div>
        <div class="panel-body">
            <?= overlib_link($link, $graph, $overlib_content, null); ?>
        </div>
    </div>
    <?php
}//end if
