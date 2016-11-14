<?php
/*
 * LibreNMS module to Graph basic resources from a Cisco CallManager Server
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

if ($device['os'] == "cucm") {
    $ctype = 'CUCM-ELCAC';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_cucm_perfmon.inc.php';
    $component = new LibreNMS\Component();
    $components = $component->getComponents($device['device_id'],array('type'=>$ctype,'ignore'=>0));

    // We only care about our device id.
    $components = $components[$device['device_id']];

    // Grab the details UCOS requires.
    $user = get_dev_attrib($device, 'ucosaxl_user');
    $pass = get_dev_attrib($device, 'ucosaxl_pass');;
    $host = get_dev_attrib($device, 'ucosaxl_host');

    $api = new api_cucm_perfmon();
    $api->connect($user, $pass, array($host));

    // Create our empty arrays.
    $counter = array();

    // Add a counter for each enabled component
    foreach($components as $compid => $array) {
        // Add the counters to be retrieved for each location
        $counter[] = '\\\\'.$host.'\Cisco Locations LBM('.$array['label'].')\BandwidthMaximum';
        $counter[] = '\\\\'.$host.'\Cisco Locations LBM('.$array['label'].')\BandwidthAvailable';
        $counter[] = '\\\\'.$host.'\Cisco Locations LBM('.$array['label'].')\ImmersiveVideoBandwidthMaximum';
        $counter[] = '\\\\'.$host.'\Cisco Locations LBM('.$array['label'].')\ImmersiveVideoBandwidthAvailable';
        $counter[] = '\\\\'.$host.'\Cisco Locations LBM('.$array['label'].')\VideoBandwidthMaximum';
        $counter[] = '\\\\'.$host.'\Cisco Locations LBM('.$array['label'].')\VideoBandwidthAvailable';
    }

    // Can we add the counters.
    if ($api->addCounter($counter)) {
        d_echo("Counter(s) Added\n");
        $result = $api->collectSessionData();

        if ($result === false) {
            d_echo("No Data was returned.\n");
        }
        else {
            // We have counter data..
            d_echo("We have counter data.\n");

            // Refactor the array so the data is more accessible.
            $statistics = array();
            foreach ($result as $value) {
                $statistics[$value['Name']] = array('Value'=>$value['Value'],'CStatus'=>$value['CStatus']);
            }

            // We should be able to retrieve the counter data now..
            foreach($components as $compid => $array) {
                // If we need to create the RRD, MODULE-Label is the convention.
                $label = $array['label'];
                $rrd_name = array($ctype, $label);
                unset($fields);

                $fields = array(
                    'totalvoice' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco Locations LBM('.$label.')\BandwidthMaximum'),
                    'availablevoice' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco Locations LBM('.$label.')\BandwidthAvailable'),
                    'totalimmersive' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco Locations LBM('.$label.')\ImmersiveVideoBandwidthMaximum'),
                    'availableimmersive' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco Locations LBM('.$label.')\ImmersiveVideoBandwidthAvailable'),
                    'totalvideo' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco Locations LBM('.$label.')\VideoBandwidthMaximum'),
                    'availablevideo' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco Locations LBM('.$label.')\VideoBandwidthAvailable'),
                );
                $rrd_def = array(
                    'DS:totalvoice:GAUGE:600:0:U',
                    'DS:availablevoice:GAUGE:600:0:U',
                    'DS:totalimmersive:GAUGE:600:0:U',
                    'DS:availableimmersive:GAUGE:600:0:U',
                    'DS:totalvideo:GAUGE:600:0:U',
                    'DS:availablevideo:GAUGE:600:0:U',
                );

                $tags = compact('label', 'rrd_name', 'rrd_def');
                data_update($device, $label, $tags, $fields);
            } // End foreach COMPONENT

            // Enable the graph.
            $graphs[$ctype."-voice"] = TRUE;
            $graphs[$ctype."-video"] = TRUE;
            $graphs[$ctype."-immersive"] = TRUE;

            echo $ctype.' ';
        } // End if RESULTS
    }
    unset($counter, $result, $ctype, $api, $components, $component);
}