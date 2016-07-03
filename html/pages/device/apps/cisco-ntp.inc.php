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
$components = $component->getComponents($device['device_id'],$options);
$components = $components[$device['device_id']];

global $config;
?>
<div class="panel panel-default" id="peers">
    <div class="panel-heading">
        <h3 class="panel-title">NTP Peers</h3>
    </div>
    <div class="panel list-group">
        <?php
        // Loop over each component.
        foreach ($components as $comp => $peer) {
            if ($peer['status'] == 0) {
                $status = "<span class='green pull-right'>Normal</span>";
                $gli = "";
            }
            else {
                $status = "<span class='pull-right'>".$peer['error']." - <span class='red'>Alert</span></span>";
                $gli = "list-group-item-danger";
            }
?>
            <a class="list-group-item <?php echo $gli?>" data-toggle="collapse" data-target="#<?php echo $peers['UID']?>" data-parent="#peers"><?php echo $peer['label']?> - <?php echo $peer['peer']?> <?php echo $status?></a>
<?php
        }
?>
    </div>
</div>

<div class="panel panel-default" id="stratum">
    <div class="panel-heading">
        <h3 class="panel-title">NTP Stratum</h3>
    </div>
    <div class="panel-body">
        <?php

        $graph_array = array();
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_cisco-ntp-stratum';
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
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_cisco-ntp-offset';
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
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_cisco-ntp-delay';
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
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width']  = '215';
        $graph_array['to']     = $config['time']['now'];
        $graph_array['type']   = 'device_cisco-ntp-dispersion';
        require 'includes/print-graphrow.inc.php';

        ?>
    </div>
</div>
