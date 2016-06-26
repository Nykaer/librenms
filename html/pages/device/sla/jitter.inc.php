    <div class="panel-heading">
        <h3 class="panel-title">Average Latency One Way</h3>
    </div>
    <div class="panel-body">
<?php
        $graph_array = array();
        $graph_array['device']  = $device['device_id'];
        $graph_array['height']  = '100';
        $graph_array['width']   = '215';
        $graph_array['to']      = $config['time']['now'];
        $graph_array['type']    = 'device_sla-jitter-latency';
        $graph_array['id']      = $vars['id'];
        require 'includes/print-graphrow.inc.php';
?>
    </div>

    <div class="panel-heading">
        <h3 class="panel-title">Average Jitter</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = array();
        $graph_array['device']  = $device['device_id'];
        $graph_array['height']  = '100';
        $graph_array['width']   = '215';
        $graph_array['to']      = $config['time']['now'];
        $graph_array['type']    = 'device_sla-jitter';
        $graph_array['id']      = $vars['id'];
        require 'includes/print-graphrow.inc.php';
        ?>
    </div>

    <div class="panel-heading">
        <h3 class="panel-title">Packet Loss</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = array();
        $graph_array['device']  = $device['device_id'];
        $graph_array['height']  = '100';
        $graph_array['width']   = '215';
        $graph_array['to']      = $config['time']['now'];
        $graph_array['type']    = 'device_sla-jitter-loss';
        $graph_array['id']      = $vars['id'];
        require 'includes/print-graphrow.inc.php';
        ?>
    </div>

    <div class="panel-heading">
        <h3 class="panel-title">Lost Packets (Out Of Sequence, Tail Drop, Late Arrival)</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = array();
        $graph_array['device']  = $device['device_id'];
        $graph_array['height']  = '100';
        $graph_array['width']   = '215';
        $graph_array['to']      = $config['time']['now'];
        $graph_array['type']    = 'device_sla-jitter-lost';
        $graph_array['id']      = $vars['id'];
        require 'includes/print-graphrow.inc.php';
        ?>
    </div>

    <div class="panel-heading">
        <h3 class="panel-title">Mean Opinion Score</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = array();
        $graph_array['device']  = $device['device_id'];
        $graph_array['height']  = '100';
        $graph_array['width']   = '215';
        $graph_array['to']      = $config['time']['now'];
        $graph_array['type']    = 'device_sla-jitter-mos';
        $graph_array['id']      = $vars['id'];
        require 'includes/print-graphrow.inc.php';
        ?>
    </div>

    <div class="panel-heading">
        <h3 class="panel-title">Impairment / Calculated Planning Impairment Factor</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = array();
        $graph_array['device']  = $device['device_id'];
        $graph_array['height']  = '100';
        $graph_array['width']   = '215';
        $graph_array['to']      = $config['time']['now'];
        $graph_array['type']    = 'device_sla-jitter-icpif';
        $graph_array['id']      = $vars['id'];
        require 'includes/print-graphrow.inc.php';
        ?>
    </div>
