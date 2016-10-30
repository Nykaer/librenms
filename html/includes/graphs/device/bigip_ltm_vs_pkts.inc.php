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
    $UID = gzuncompress($components[$vars['id']]['UID']);

    $rrd_filename = rrd_name($device['hostname'], array('bigip', 'LTMVirtualServer', $label, $UID));
    if (file_exists($rrd_filename)) {
//        $ds_in  = 'pktsin';
//        $ds_out = 'pktsout';
//
//        $colour_area_in  = 'AA66AA';
//        $colour_line_in  = '330033';
//        $colour_area_out = 'FFDD88';
//        $colour_line_out = 'FF6600';
//
//        $in_text = 'Packets in';
//        $out_text = 'Packets out';
//
//        $colour_area_in_max  = 'cc88cc';
//        $colour_area_out_max = 'FFefaa';
//
//        $graph_max = 1;
//        $unit_text = 'Packets';
//
//        require 'includes/graphs/generic_duplex.inc.php';

        $rrd_filename = "/home/adaniels/www/librenms/rrd/127.0.0.9/temp.rrd";
        include "includes/graphs/common.inc.php";
        $rrd_options .= " -l 0 -E ";
        $rrd_options .= " COMMENT:'                            Cur   Min  Max\\n'";
        $rrd_options .= " DEF:in=" . $rrd_filename . ":bytesin:AVERAGE ";
        $rrd_options .= " AREA:in#c099ff ";
        $rrd_options .= " LINE1.25:in#0000ee:'PRI Channels total      ' ";
        $rrd_options .= " GPRINT:in:LAST:%3.0lf ";
        $rrd_options .= " GPRINT:in:MIN:%3.0lf ";
        $rrd_options .= " GPRINT:in:MAX:%3.0lf\\\l ";

        $rrd_options .= " DEF:out=" . $rrd_filename . ":totconns:AVERAGE ";
        $rrd_options .= " AREA:out#aaff99 ";
        $rrd_options .= " LINE1.25:out#00ee00:'PRI Channels in use     ' ";
        $rrd_options .= " GPRINT:out:LAST:%3.0lf ";
        $rrd_options .= " GPRINT:out:MIN:%3.0lf ";
        $rrd_options .= " GPRINT:out:MAX:%3.0lf\\\l ";
    }
}
