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

if ($device['os'] == "ucos") {

    $MODULE = 'CUCM-ELCAC';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_cucm_perfmon.inc.php';
    require_once 'includes/component.php';

    $COMPONENT = new component();
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('type'=>$MODULE,'ignore'=>0));

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
                $RRD = array();
                $RRD['filename'] = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ($MODULE."-".$ARRAY['label'].".rrd");

                $RRD['create'] = " DS:totalvoice:GAUGE:600:0:U DS:availablevoice:GAUGE:600:0:U DS:totalimmersive:GAUGE:600:0:U DS:availableimmersive:GAUGE:600:0:U DS:totalvideo:GAUGE:600:0:U DS:availablevideo:GAUGE:600:0:U";
                $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\BandwidthMaximum');
                $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\BandwidthAvailable');
                $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\ImmersiveVideoBandwidthMaximum');
                $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\ImmersiveVideoBandwidthAvailable');
                $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\VideoBandwidthMaximum');
                $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco Locations LBM('.$ARRAY['label'].')\VideoBandwidthAvailable');

                // Do we need to do anything with the RRD?
                if (isset($RRD)) {
                    // Create the RRD if it doesn't exist.
                    if (!file_exists ($RRD['filename'])) {
                        rrdtool_create ($RRD['filename'], $RRD['create'] . $config['rrd_rra']);
                    }

                    // Add the data to the RRD if it exists.
                    if (isset($RRD['data'])) {
                        rrdtool_update ($RRD['filename'], $RRD['data']);
                    }
                }
            } // End foreach COMPONENT

            // Enable the graph.
            $graphs[$MODULE."-voice"] = TRUE;
            $graphs[$MODULE."-video"] = TRUE;
            $graphs[$MODULE."-immersive"] = TRUE;

            echo $MODULE.' ';
        } // End if RESULTS
    }
    unset($RRD, $COUNTERS, $RESULT, $MODULE, $API, $COMPONENTS, $COMPONENT);
}