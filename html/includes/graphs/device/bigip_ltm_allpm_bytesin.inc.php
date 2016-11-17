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

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:'LTM Pool Members                               Now      Avg      Max\\n'";
$colours = array_merge($config['graph_colours']['mixed'], $config['graph_colours']['manycolours'], $config['graph_colours']['manycolours']);
$count = 0;
d_echo("<pre>");

// We only care about our device id.
$components = $components[$device['device_id']];

// Is the ID we are looking for a valid LTM VS Pool
if ($components[$vars['id']]['category'] == 'LTMPool') {
    $parent = gzuncompress ($components[$vars['id']]['UID']);

    // Find all pool members
    foreach ($components as $compid => $comp) {
        if ($comp['category'] != 'LTMPoolMember') { continue; }
        if (!strstr(gzuncompress($comp['UID']), $parent)) { continue; }

        $label = $comp['label'];
        $hash = $comp['hash'];
        $rrd_filename = rrd_name ($device['hostname'], array ('bigip', 'LTMPoolMember', $label, $hash));
        if (file_exists ($rrd_filename)) {
            d_echo ("\n  Adding PM: " . $label . "\t+ added to the graph");

            // Grab a colour from the array.
            if (isset($colours[$count])) {
                $colour = $colours[$count];
            } else {
                d_echo ("\nError: Out of colours. Have: " . (count ($colours) - 1) . ", Requesting:" . $count);
            }

            $rrd_options .= " DEF:DS" . $count . "=" . $rrd_filename . ":bytesin:AVERAGE ";
            $rrd_options .= " LINE1.25:DS" . $count . "#" . $colour . ":'" . str_pad (substr ($label, 0, 40), 40) . "'";
            $rrd_options .= " GPRINT:DS" . $count . ":LAST:%6.2lf%s ";
            $rrd_options .= " GPRINT:DS" . $count . ":AVERAGE:%6.2lf%s ";
            $rrd_options .= " GPRINT:DS" . $count . ":MAX:%6.2lf%s\l ";
            $count++;
        }
    } // End Foreach
}
d_echo ("</pre>");
