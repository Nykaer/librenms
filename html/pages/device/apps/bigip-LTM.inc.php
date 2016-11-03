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

print_optionbar_start();

// Pages
$pages = array(
    'LTM-VS'    => 'LTM Virtual Servers',
    'LTM-Pool'  => 'LTM Pool',
);

// Graphs
$graphs = array('on' => 'On', 'off' => 'Off');

echo "<span style='font-weight: bold;'>F5 BigIP</span> &#187; ";

// Pages, on the left.
$sep = '';
foreach ($pages as $page => $text) {
    if (!$vars['view']) {
        $vars['view'] = $page;
    }

    echo $sep;
    if ($vars['view'] == $page) {
        echo "<span class='pagemenu-selected'>";
    }

    echo generate_link($text, $vars, array('view' => $page));
    if ($vars['view'] == $page) {
        echo '</span>';
    }

    $sep = ' | ';
}
unset($sep);

// Graphs, on the right
echo '<div class="pull-right">';
echo "<span style='font-weight: bold;'>Graphs</span> &#187; ";
$sep = '';
foreach ($graphs as $option => $text) {
    if (empty($vars['graphs'])) {
        $vars['graphs'] = $option;
    }
    echo $sep;
    if ($vars['graphs'] == $option) {
        echo "<span class='pagemenu-selected'>";
    }

    echo generate_link($text, $vars, array('graphs' => $option));
    if ($vars['graphs'] == $option) {
        echo '</span>';
    }
    $sep = ' | ';
}
unset($sep);
echo '</div>';

print_optionbar_end();
$pagetitle[] = $pages[$vars['view']];

include 'pages/device/apps/bigip/'.$vars['view'].'.inc.php';
