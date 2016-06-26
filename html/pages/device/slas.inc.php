<?php

print_optionbar_start();

echo "<span style='font-weight: bold;'>SLA</span> &#187; ";

$slas = dbFetchRows('SELECT * FROM `slas` WHERE `device_id` = ? AND `deleted` = 0 ORDER BY `sla_nr`', array($device['device_id']));

// Collect types
$sla_types = array('all' => 'All');
foreach ($slas as $sla) {
    // Set a default type, if we know about it, it will be overwritten below.
    $text = 'Unknown';

    $sla_type = $sla['rtt_type'];

    if (!in_array($sla_type, $sla_types)) {
        if (isset($config['sla_type_labels'][$sla_type])) {
            $text = $config['sla_type_labels'][$sla_type];
        }
    }
    else {
        $text = ucfirst($sla_type);
    }

    $sla_types[$sla_type] = $text;
}

asort($sla_types);

$sep = '';
foreach ($sla_types as $sla_type => $text) {
    if (!$vars['view']) {
        $vars['view'] = $sla_type;
    }

    echo $sep;
    if ($vars['view'] == $sla_type) {
        echo "<span class='pagemenu-selected'>";
    }

    echo generate_link($text, $vars, array('view' => $sla_type));
    if ($vars['view'] == $sla_type) {
        echo '</span>';
    }

    $sep = ' | ';
}

unset($sep);
print_optionbar_end();
?>

<table id='table' class='table table-condensed table-responsive table-striped'>
    <thead>
    <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Tag</th>
        <th>Owner</th>
    </tr>
    </thead>
<?php


foreach ($slas as $sla) {
    if ($vars['view'] != 'all' && $vars['view'] != $sla['rtt_type']) {
        continue;
    }

?>
  <tr onclick="window.document.location='<?php echo generate_url($vars, array('tab' => "sla", 'id' => $sla['sla_nr'])); ?>';" style="cursor: pointer;">
    <td><?php echo $sla['sla_nr']; ?></td>
    <td><?php echo $sla_types[$sla['rtt_type']]; ?></td>
    <td><?php echo $sla['tag']; ?></td>
    <td><?php echo $sla['owner']; ?></td>
  </tr>
<?php
}

echo '</table>';
$pagetitle[] = 'SLAs';
