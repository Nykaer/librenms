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

include 'pages/device/loadbalancer/ltm-vs-common.inc.php';

global $config;

if ($components[$vars['poolid']]['category'] == 'LTMPool') {
    $array = $components[$vars['vsid']];
    $parent = gzuncompress ($array['UID']);
    ?>
    <div class="row">
        <div class="col-md-12">
            <div class="container-fluid">
                <div class='row'>
                    <div class="panel panel-default" id="connections">
                        <div class="panel-heading">
                            <h3 class="panel-title">Connections</h3>
                        </div>
                        <div class="panel-body">
                            <?php
                            $graph_array = array ();
                            $graph_array['device'] = $device['device_id'];
                            $graph_array['height'] = '100';
                            $graph_array['width'] = '215';
                            $graph_array['legend'] = 'no';
                            $graph_array['to'] = $config['time']['now'];
                            $graph_array['type'] = 'device_bigip_ltm_vs_conns';
                            $graph_array['id'] = $vars['vsid'];
                            require 'includes/print-graphrow.inc.php';
                            ?>
                        </div>
                    </div>

                    <div class="panel panel-default" id="bytes">
                        <div class="panel-heading">
                            <h3 class="panel-title">Bytes</h3>
                        </div>
                        <div class="panel-body">
                            <?php
                            $graph_array = array ();
                            $graph_array['device'] = $device['device_id'];
                            $graph_array['height'] = '100';
                            $graph_array['width'] = '215';
                            $graph_array['legend'] = 'no';
                            $graph_array['to'] = $config['time']['now'];
                            $graph_array['type'] = 'device_bigip_ltm_vs_bytes';
                            $graph_array['id'] = $vars['vsid'];
                            require 'includes/print-graphrow.inc.php';
                            ?>
                        </div>
                    </div>

                    <div class="panel panel-default" id="pkts">
                        <div class="panel-heading">
                            <h3 class="panel-title">Packets</h3>
                        </div>
                        <div class="panel-body">
                            <?php
                            $graph_array = array ();
                            $graph_array['device'] = $device['device_id'];
                            $graph_array['height'] = '100';
                            $graph_array['width'] = '215';
                            $graph_array['legend'] = 'no';
                            $graph_array['to'] = $config['time']['now'];
                            $graph_array['type'] = 'device_bigip_ltm_vs_pkts';
                            $graph_array['id'] = $vars['vsid'];
                            require 'includes/print-graphrow.inc.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
}
