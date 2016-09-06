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
$rrd_additions = "";

$count = 0;
foreach ($components as $id => $array) {
    $rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/'.safename("ldap-".$array['UID'].".rrd");

    if (file_exists($rrd_filename)) {
        // Grab a color from the array.
        if (isset($config['graph_colours']['mixed'][$count])) {
            $color = $config['graph_colours']['mixed'][$count];
        } else {
            $color = $config['graph_colours']['oranges'][$count-7];
        }

        $rrd_additions .= " DEF:DS" . $count . "=" . $rrd_filename . ":users:AVERAGE ";
        $rrd_additions .= " LINE1.25:DS" . $count . "#" . $color . ":'" . str_pad(substr($array['dn'], 0, 15), 15) . "' ";
        $rrd_additions .= " GPRINT:DS" . $count . ":LAST:%6.0lf%s ";
        $rrd_additions .= " GPRINT:DS" . $count . ":MIN:%6.0lf%s ";
        $rrd_additions .= " GPRINT:DS" . $count . ":MAX:%6.0lf%s\\\l ";
        $count++;
    }
}

if ($rrd_additions == "") {
    // We didn't add any data points.
} else {
    $rrd_options .= $rrd_additions;
}
