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

// Pages
$pages = array(
    'LTM-AllVS' => 'All Virtual Servers',
    'LTM-VS'    => 'Virtual Server',
    'LTM-Pool'  => 'LTM Pool',
);
if (!$vars['view']) {
    $vars['view'] = 'LTM-AllVS';
}

// Graphs
$graphs = array('on' => 'On', 'off' => 'Off');
if (empty($vars['graphs'])) {
    $vars['graphs'] = 'on';
}

$pagetitle[] = $pages[$vars['view']];
include 'pages/device/apps/bigip/'.$vars['view'].'.inc.php';
