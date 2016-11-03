<?php
/*
 * LibreNMS module to display F5 LTM Virtual Server Details
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
$options = array();
$options['filter']['type'] = array('=','bigip');
$components = $component->getComponents($device['device_id'], $options);

// We only care about our device id.
$components = $components[$device['device_id']];

// Is the ID we are looking for a valid LTM VS
if ($components[$vars['id']]['category'] == 'LTMVirtualServer') {
    $label = $components[$vars['id']]['label'];
    $hash = $components[$vars['id']]['hash'];

    $rrd_filename = rrd_name($device['hostname'], array('bigip', 'LTMVirtualServer', $label, $hash));
    if (file_exists($rrd_filename)) {
        $ds_in  = 'bytesin';
        $ds_out = 'bytesout';

        $colour_area_in  = 'AA66AA';
        $colour_line_in  = '330033';
        $colour_area_out = 'FFDD88';
        $colour_line_out = 'FF6600';

        $in_text = 'Bytes in';
        $out_text = 'Bytes out';

        $colour_area_in_max  = 'cc88cc';
        $colour_area_out_max = 'FFefaa';

        $graph_max = 1;
        $unit_text = 'Bytes';

        require 'includes/graphs/generic_duplex.inc.php';
    }
}
