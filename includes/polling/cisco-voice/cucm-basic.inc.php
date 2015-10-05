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

    $MODULE = 'CUCM-Basic';

    require_once 'includes/cisco-voice/transport_http.inc.php';
    require_once 'includes/cisco-voice/api_cucm_perfmon.inc.php';

    // Pull from DB.
    $USER = "script";
    $PASS = "script";
    $HOST = "192.168.174.13";

    $API = new api_cucm_perfmon();
    $API->connect($USER, $PASS, array($HOST));

    $COUNTER = array();
    // Basic
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\CallsActive';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\InitializationState';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\SWConferenceActive';

    // Media Resources
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceActive';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MTPResourceActive';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceActive';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesActive';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBResourceActive';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHTotalMulticastResources';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHMulticastResourceActive';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources';
    $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHUnicastResourceActive';


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

            // Extract our counter data from the response.
            if (isset($RESULT['0']['Name'])) {
                $TOTAL = 0;
                if (in_array($RESULT[0]['Name'], $COUNTER)) {
                    // We have found our counter.
                    $TOTAL = $RESULT[0]['Value'];
                }

                $rrd_filename = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ($MODULE.".rrd");
                if (!file_exists ($rrd_filename)) {
                    rrdtool_create ($rrd_filename, " DS:callsactive:GAUGE:600:0:U" . $config['rrd_rra']);
                }
                rrdtool_update ($rrd_filename, "N:" . $TOTAL);
            }

            $graphs['cucm-basic'] = TRUE;
            echo $MODULE.' ';
        }
    }
    unset($rrd_filename, $TOTAL, $COUNTERS, $RESULT, $MODULE, $API);
}