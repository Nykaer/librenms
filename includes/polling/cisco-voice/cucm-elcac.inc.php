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
    $COMPONENT = new LibreNMS\Component();
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>$ctype,'ignore'=>0));

    // We only care about our device id.
    $COMPONENTS = $COMPONENTS[$device['device_id']];

    // Grab the details UCOS requires.
    $USER = get_dev_attrib($device, 'ucosaxl_user');
    $PASS = get_dev_attrib($device, 'ucosaxl_pass');;
    $HOST = get_dev_attrib($device, 'ucosaxl_host');

    $API = new api_cucm_perfmon();
    $API->connect($USER, $PASS, array($HOST));

    // Create our empty arrays.
    $COUNTER = array();

    // Add a counter for each enabled component
    foreach($COMPONENTS as $COMPID => $ARRAY) {
        // Add the counters to be retrieved for each location
        $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\BandwidthMaximum';
        $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\BandwidthAvailable';
        $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\ImmersiveVideoBandwidthMaximum';
        $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\ImmersiveVideoBandwidthAvailable';
        $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\VideoBandwidthMaximum';
        $COUNTER[] = '\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\VideoBandwidthAvailable';
    }

    // Can we add the counters.
    if ($API->addCounter($COUNTER)) {
        d_echo("Counter(s) Added\n");
        $RESULT = $API->collectSessionData();

        if ($RESULT === false) {
            d_echo("No Data was returned.\n");
        }
        else {
            // We have counter data..
            d_echo("We have counter data.\n");

            // Refactor the array so the data is more accessible.
            $STATISTICS = array();
            foreach ($RESULT as $VALUE) {
                $STATISTICS[$VALUE['Name']] = array('Value'=>$VALUE['Value'],'CStatus'=>$VALUE['CStatus']);
            }

            // We should be able to retrieve the counter data now..
            foreach($COMPONENTS as $COMPID => $ARRAY) {
                // If we need to create the RRD, MODULE-Label is the convention.
                $label = $ARRAY['label'];
                $rrd_name = array($ctype, $label);
                unset($fields);

                $fields = array(
                    'totalvoice' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$label.')\BandwidthMaximum'),
                    'availablevoice' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$label.')\BandwidthAvailable'),
                    'totalimmersive' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$label.')\ImmersiveVideoBandwidthMaximum'),
                    'availableimmersive' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$label.')\ImmersiveVideoBandwidthAvailable'),
                    'totalvideo' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$label.')\VideoBandwidthMaximum'),
                    'availablevideo' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$label.')\VideoBandwidthAvailable'),
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
    unset($COUNTERS, $RESULT, $ctype, $API, $COMPONENTS, $COMPONENT);
}