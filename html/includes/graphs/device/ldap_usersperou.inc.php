<?php
/*
 * LibreNMS module to display Cisco Class-Based QoS Details
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$module = 'LDAP_UsersPerOU';

$component = new LibreNMS\Component();
$options = array();
$options['filter']['type'] = array('=',$module);
$options['filter']['disabled'] = array('=',0);
$options['filter']['ignore'] = array('=',0);
$components = $component->getComponents($device['device_id'], $options);

// We only care about our device id.
$components = $components[$device['device_id']];

include "includes/graphs/common.inc.php";
$rrd_options .= " -l 0 -E ";
$rrd_options .= " COMMENT:' Users per OU           Now      Min      Max\\n'";

$colours = array_merge($config['graph_colours']['mixed'], $config['graph_colours']['manycolours'], $config['graph_colours']['manycolours']);
$count = 0;
foreach ($components as $id => $array) {
    $rrd_filename = rrd_name($device['hostname'], array('ldap', 'users', $array['label']));

    if (file_exists($rrd_filename)) {
        // Grab a colour from the array.
        if (isset($colours[$count])) {
            $colour = $colours[$count];
        } else {
            d_echo("\nError: Out of colours. Have: ".(count($colours)-1).", Requesting:".$count);
        }

        $rrd_options .= " DEF:DS" . $count . "=" . $rrd_filename . ":users:AVERAGE ";
        $rrd_options .= " LINE1.25:DS" . $count . "#" . $colour . ":'" . str_pad(substr($array['dn'], 0, 15), 15) . "' ";
        $rrd_options .= " GPRINT:DS" . $count . ":LAST:%6.0lf%s ";
        $rrd_options .= " GPRINT:DS" . $count . ":MIN:%6.0lf%s ";
        $rrd_options .= " GPRINT:DS" . $count . ":MAX:%6.0lf%s\\\l ";
        $count++;
    }
}
