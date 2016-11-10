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

if ($components[$vars['id']]['category'] == 'LTMPool') {
    // Define some error messages
    $error_poolaction = array ();
    $error_poolaction[0] = "Unused";
    $error_poolaction[1] = "Reboot";
    $error_poolaction[2] = "Restart";
    $error_poolaction[3] = "Failover";
    $error_poolaction[4] = "Failover and Restart";
    $error_poolaction[5] = "Go Active";
    $error_poolaction[6] = "None";

    $parent = gzuncompress ($components[$vars['id']]['UID']);
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="container-fluid">
                <div class='row'>
                    <div class="col-md-12">
                        <div class='panel panel-default panel-condensed'>
                            <div class='panel-heading'>
                                <strong>Pool: <?php echo $components[$vars['id']]['label']; ?></strong></div>
                            <table class="table table-hover table-condensed table-striped">
                                <tr>
                                    <?php
                                    if ($components[$vars['id']]['minupstatus'] == 1) {
                                    // We care about min-up
                                    ?>
                                    <td>Minimum Active Servers:</td>
                                    <td><?php echo $components[$vars['id']]['minup']; ?></td>
                                </tr>
                                <tr>
                                    <?php
                                    }
                                    ?>
                                    <td>Current Active Servers:</td>
                                    <td><?php echo $components[$vars['id']]['currentup']; ?></td>
                                </tr>
                                <tr>
                                    <td>Pool Down Action:</td>
                                    <td><?php echo $error_poolaction[$components[$vars['id']]['minupaction']]; ?></td>
                                </tr>
                                <tr>
                                    <td>Pool Monitor:</td>
                                    <td><?php echo $components[$vars['id']]['monitor']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="container-fluid">
                <div class='row'>
                    <div class="col-md-12">
                        <div class="panel panel-default panel-condensed">
                            <div class="panel-heading">
                                <strong>Pool Members</strong>
                            </div>
                            <table class="table table-hover table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>IP : Port</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <?php
                                foreach ($components as $comp) {
                                    if ($comp['category'] != 'LTMPoolMember') {
                                        continue;
                                    }
                                    if (!strstr (gzuncompress ($comp['UID']), $parent)) {
                                        continue;
                                    }

                                    $string = $comp['IP'] . ":" . $comp['port'];
                                    if ($comp['status'] == 2) {
                                        $status = $comp['error'];
                                        $error = 'class="danger"';
                                    } else {
                                        $status = 'Ok';
                                        $error = '';
                                    }
                                    ?>
                                    <tr <?php echo $error; ?>>
                                        <td><?php echo $comp['label']; ?></td>
                                        <td><?php echo $string; ?></td>
                                        <td><?php echo $status; ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    if ($vars['graphs'] == 'on') {
        ?>
        <div class="row">
        <div class="col-md-12">
            <div class="container-fluid">
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
                        $graph_array['type'] = 'device_bigip_ltm_allpm_conns';
                        $graph_array['id'] = $vars['id'];
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

                        $graph_array = array ();
                        $graph_array['device'] = $device['device_id'];
                        $graph_array['height'] = '100';
                        $graph_array['width'] = '215';
                        $graph_array['legend'] = 'no';
                        $graph_array['to'] = $config['time']['now'];
                        $graph_array['type'] = 'device_bigip_ltm_allvs_bytesin';
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

                        $graph_array = array ();
                        $graph_array['device'] = $device['device_id'];
                        $graph_array['height'] = '100';
                        $graph_array['width'] = '215';
                        $graph_array['legend'] = 'no';
                        $graph_array['to'] = $config['time']['now'];
                        $graph_array['type'] = 'device_bigip_ltm_allvs_bytesout';
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

                        $graph_array = array ();
                        $graph_array['device'] = $device['device_id'];
                        $graph_array['height'] = '100';
                        $graph_array['width'] = '215';
                        $graph_array['legend'] = 'no';
                        $graph_array['to'] = $config['time']['now'];
                        $graph_array['type'] = 'device_bigip_ltm_allvs_pktsin';
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

                        $graph_array = array ();
                        $graph_array['device'] = $device['device_id'];
                        $graph_array['height'] = '100';
                        $graph_array['width'] = '215';
                        $graph_array['legend'] = 'no';
                        $graph_array['to'] = $config['time']['now'];
                        $graph_array['type'] = 'device_bigip_ltm_allvs_pktsout';
                        require 'includes/print-graphrow.inc.php';

                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
