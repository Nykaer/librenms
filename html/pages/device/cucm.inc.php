<?php

require_once "../includes/component.php";

$COMPONENT = new component();
$CUCM_ELCAC = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-ELCAC','ignore'=>0));
$CUCM_SIP = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-SIP','ignore'=>0));
$CUCM_H323 = $COMPONENT->getComponents($device['device_id'],array('type'=>'CUCM-H323','ignore'=>0));

unset($datas);
$datas[] = 'overview';
if (count($CUCM_ELCAC) > 0) {
    $datas[] = 'elcac';
}
if ((count($CUCM_SIP) > 0) || (count($CUCM_H323) > 0)) {
    $datas[] = 'trunk';
}

$type_text['overview']      = 'Overview';
$type_text['trunk']           = 'Trunks';
$type_text['elcac']         = 'Locations';

$link_array = array(
    'page'   => 'device',
    'device' => $device['device_id'],
    'tab'    => 'cucm',
);

print_optionbar_start();

echo "<span style='font-weight: bold;'>Call Manager</span> &#187; ";

if (!$vars['metric']) {
    $vars['metric'] = 'overview';
}

unset($sep);
foreach ($datas as $type) {
    echo $sep;

    if ($vars['metric'] == $type) {
        echo '<span class="pagemenu-selected">';
    }

    echo generate_link($type_text[$type], $link_array, array('metric' => $type));
    if ($vars['metric'] == $type) {
        echo '</span>';
    }

    $sep = ' | ';
}

print_optionbar_end();

if (is_file('pages/device/cucm/'.mres($vars['metric']).'.inc.php')) {
    include 'pages/device/cucm/'.mres($vars['metric']).'.inc.php';
}
else {
    echo "<div class='col-md-12'>Error: The desired metric (".mres($vars['metric']).") does not exist.</div>";
//    echo "<div class='col-md-12'>No Call Manager Components Exists. Please ensure a discovery has been completed.</div>";
}

$pagetitle[] = 'Call Manager';
