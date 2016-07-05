<?php
/*
 * LibreNMS module to capture statistics from the CISCO-NTP-MIB
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

require_once "../includes/component.php";
$component = new component();
$options = array();
$options['filter']['ignore'] = array('=',0);
$options['type'] = 'Cisco-NTP';
$components = $component->getComponents(null,$options);
?>
<table id='table' class='table table-condensed table-responsive table-striped'>
    <thead>
    <tr>
        <th>Device</th>
        <th>Peer</th>
        <th>Stratum</th>
        <th>Error</th>
    </tr>
    </thead>
<?php
    foreach ($components as $devid => $comp) {
        $device = device_by_id_cache($devid);
        foreach ($comp as $compid => $array) {
            if ($array['error'] == '') {
                $array['error'] = '-';
                $status = 'danger';
            }
?>
    <tr>
        <td><?php echo $device['hostname']; ?></td>
        <td><?php echo $array['peer']; ?></td>
        <td><?php echo $array['stratum']; ?></td>
        <td><?php echo $array['error']; ?></td>
    </tr>
<?php
        }
    }
?>
</table>

<div class="panel panel-default" id="stratum">
    <div class="panel-heading">
        <h3 class="panel-title">NTP Stratum</h3>
    </div>
    <div class="panel-body">
<?php

        $graph_array = array();
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
//        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'cisco-ntp_stratum';
        require 'includes/print-graphrow.inc.php';

?>
    </div>
</div>

<div class="panel panel-default" id="offset">
    <div class="panel-heading">
        <h3 class="panel-title">Offset</h3>
    </div>
    <div class="panel-body">
<?php

        $graph_array = array();
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'cisco-ntp_offset';
        require 'includes/print-graphrow.inc.php';

?>
    </div>
</div>

<div class="panel panel-default" id="delay">
    <div class="panel-heading">
        <h3 class="panel-title">Delay</h3>
    </div>
    <div class="panel-body">
<?php

        $graph_array = array();
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'cisco-ntp_delay';
        require 'includes/print-graphrow.inc.php';

?>
    </div>
</div>

<div class="panel panel-default" id="dispersion">
    <div class="panel-heading">
        <h3 class="panel-title">Dispersion</h3>
    </div>
    <div class="panel-body">
<?php

        $graph_array = array();
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'cisco-ntp_dispersion';
        require 'includes/print-graphrow.inc.php';

?>
    </div>
</div>
