<?php

$link_array = array(
    'page'   => 'device',
    'device' => $device['device_id'],
    'tab'    => 'loadbalancer',
    'type'   => $vars['type'],
);

$component = new LibreNMS\Component();
$components = $component->getComponents($device['device_id'], array('filter' => array('type' => array('=', 'bigip'), 'ignore' => array('=', 0))));
$components = $components[$device['device_id']];

if (is_file('pages/device/loadbalancer/'.mres($vars['subtype']).'.inc.php')) {
    include 'pages/device/loadbalancer/'.mres($vars['subtype']).'.inc.php';
} else {
    include 'pages/device/loadbalancer/ltm-vs-all.inc.php';
}//end if
