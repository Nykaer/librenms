<?php

$rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("CUCM-RegisteredDevices.rrd");
if (file_exists ($rrd_filename)) {
    $graph_array['device'] = $device['device_id'];

    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Registered Phones - by Status</div>
        </div>
        <div class="panel-body">
    <?php
    $graph_array['type'] = 'device_cucm-registereddevices-phonebystatus';
    require 'includes/print-graphrow.inc.php';
    ?>
        </div>
    </div>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Registered Phones - by Type</div>
        </div>
        <div class="panel-body">
    <?php
    $graph_array['type'] = 'device_cucm-registereddevices-phonebytype';
    require 'includes/print-graphrow.inc.php';
    ?>
        </div>
    </div>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Registered Gateways</div>
        </div>
        <div class="panel-body">
    <?php
    $graph_array['type'] = 'device_cucm-registereddevices-gw';
    require 'includes/print-graphrow.inc.php';
    ?>
        </div>
    </div>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <div class=graphhead>Registered Media Resources</div>
        </div>
        <div class="panel-body">
            <?php
            $graph_array['type'] = 'device_cucm-registereddevices-mr';
            require 'includes/print-graphrow.inc.php';
            ?>
        </div>
    </div>
<?php
}
