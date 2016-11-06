<?php
/*
 * LibreNMS module to Display data from F5 BigIP LTM Devices
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$component = new LibreNMS\Component();
$components = $component->getComponents($device['device_id'], array('filter' => array('type' => array('=', 'bigip'), 'ignore' => array('=', 0))));
$components = $components[$device['device_id']];

global $config;
?>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        $(".clickable-row").click(function() {
            window.document.location = $(this).data("href");
        });
    });
</script>
<table id='table' class='table table-condensed table-responsive table-striped'>
    <thead>
    <tr>
        <th>Name</th>
        <th>IP : Port</th>
        <th>Pool</th>
        <th>Status</th>
    </tr>
    </thead>
    <?php
    foreach ($components as $comp) {
        if ($comp['category'] != 'LTMVirtualServer') { continue; }
        $string = $comp['IP'].":".$comp['port'];
        if ($comp['status'] == 2) {
            $status = $comp['error'];
            $error = 'class="danger"';
        } else {
            $status = 'Ok';
            $error = '';
        }

        // Find the ID for our pool
        foreach ($components as $k => $v) {
            if ($v['category'] != 'LTMPool') { continue; }
            if ($v['label'] == $comp['pool']) {
                $id = $k;
            }
            $link = generate_url($vars, array('view' => 'LTM-Pool', 'id' => $id));
        }
        ?>
        <tr class='clickable-row' data-href='<?php echo $link; ?>' <?php echo $error; ?>>
            <td><?php echo $comp['label']; ?></td>
            <td><?php echo $string; ?></td>
            <td><?php echo $comp['pool']; ?></td>
            <td><?php echo $status; ?></td>
        </tr>
        <?php
    }
    ?>
</table>

<div class="panel panel-default" id="connections">
    <div class="panel-heading">
        <h3 class="panel-title">Connections</h3>
    </div>
    <div class="panel-body">
        <?php

        $graph_array = array();
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_bigip_ltm_allvs_conns';
        require 'includes/print-graphrow.inc.php';

        ?>
    </div>
</div>

<div class="panel panel-default" id="bytesin">
    <div class="panel-heading">
        <h3 class="panel-title">Bytes In</h3>
    </div>
    <div class="panel-body">
        <?php

        $graph_array = array();
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_bigip_ltm_allvs_bytesin';
        require 'includes/print-graphrow.inc.php';

        ?>
    </div>
</div>

<div class="panel panel-default" id="bytesout">
    <div class="panel-heading">
        <h3 class="panel-title">Bytes Out</h3>
    </div>
    <div class="panel-body">
        <?php

        $graph_array = array();
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_bigip_ltm_allvs_bytesout';
        require 'includes/print-graphrow.inc.php';

        ?>
    </div>
</div>

<div class="panel panel-default" id="pktsin">
    <div class="panel-heading">
        <h3 class="panel-title">Packets In</h3>
    </div>
    <div class="panel-body">
        <?php

        $graph_array = array();
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_bigip_ltm_allvs_pktsin';
        require 'includes/print-graphrow.inc.php';

        ?>
    </div>
</div>

<div class="panel panel-default" id="pktsout">
    <div class="panel-heading">
        <h3 class="panel-title">Packets Out</h3>
    </div>
    <div class="panel-body">
        <?php

        $graph_array = array();
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_bigip_ltm_allvs_pktsout';
        require 'includes/print-graphrow.inc.php';

        ?>
    </div>
</div>
