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
    $ctype = 'CUCM-Basic';

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

    // Our Counter Array.
    $COUNTER = array();

    // Add a counter for each enabled component
    foreach($COMPONENTS as $COMPID => $ARRAY) {
        switch ($ARRAY['label']) {
            case 'Calls':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\CallsActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VideoCallsActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\EncryptedCallsActive';
                break;
            case 'InitializationState':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\InitializationState';
                break;
            case 'Replicate_State':
                $COUNTER[] = '\\\\'.$HOST.'\Number of Replicates Created and State of Replication(ReplicateCount)\Replicate_State';
                break;
            case 'AnnunciatorResource':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal';
                break;
            case 'MTPResource':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MTPResourceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal';
                break;
            case 'TranscoderResource':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal';
                break;
            case 'VCBConferences':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal';
                break;
            case 'VCBResource':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBResourceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal';
                break;
            case 'MOHUnicastResources':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHUnicastResourceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources';
                break;
            case 'SWConferenceResource':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\SWConferenceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceTotal';
                break;
            case 'HWConferenceResource':
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\HWConferenceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceActive';
                $COUNTER[] = '\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceTotal';
                break;
            default:
                d_echo("Unknown Component label: ".$ARRAY['label']);
        }
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

                // Enable the graph.
                $graphs[$ctype.'-'.$label] = TRUE;

                switch ($label) {
                    case 'Calls':
                        $fields = array(
                            'calls' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\CallsActive'),
                            'videocalls' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VideoCallsActive'),
                            'encryptedcalls' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\EncryptedCallsActive'),
                        );
                        $rrd_def = array(
                            'DS:calls:GAUGE:600:0:U',
                            'DS:videocalls:GAUGE:600:0:U',
                            'DS:encryptedcalls:GAUGE:600:0:U',
                        );
                        break;
                        case 'InitializationState':
                        unset($graphs[$MODULE.'-'.$label]);    // Disable this graph because there is none.
                        $COMPONENTS[$COMPID]['status'] = 2;             // Guilty Until proven innocent.
                        $COMPONENTS[$COMPID]['state'] = $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\InitializationState');
                        if ($COMPONENTS[$COMPID]['state'] == 100) {
                            $COMPONENTS[$COMPID]['status'] = 0;
                        }
                        break;
                    case 'Replicate_State':
                        unset($graphs[$MODULE.'-'.$label]);    // Disable this graph because there is none.
                        $COMPONENTS[$COMPID]['status'] = 2;             // Guilty Until proven innocent.
                        $COMPONENTS[$COMPID]['state'] = $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Number of Replicates Created and State of Replication(ReplicateCount)\Replicate_State');
                        if ($COMPONENTS[$COMPID]['state'] == 2) {
                            $COMPONENTS[$COMPID]['status'] = 0;
                        }
                        break;
                    case 'AnnunciatorResource':
                        $fields = array(
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'MTPResource':
                        $fields = array(
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MTPResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'TranscoderResource':
                        $fields = array(
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'VCBConferences':
                        $fields = array(
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBConferencesActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'VCBResource':
                        $fields = array(
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'MOHMulticastResources':
                        $fields = array(
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHMulticastResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHTotalMulticastResources'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'MOHUnicastResources':
                        $fields = array(
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHUnicastResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'SWConferenceResource':
                        $fields = array(
                            'conferences' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\SWConferenceActive'),
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:conferences:GAUGE:600:0:U',
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'HWConferenceResource':
                        $fields = array(
                            'conferences' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\HWConferenceActive'),
                            'active' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceActive'),
                            'total' => $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:conferences:GAUGE:600:0:U',
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    default:
                        d_echo("Unknown Component label: ".$label);
                } // End Switch

                // Do we need to do anything with the RRD?
                if (isset($fields)) {
                    $tags = compact('label', 'rrd_name', 'rrd_def');
                    data_update($device, $label, $tags, $fields);
                }
            } // End foreach COMPONENT

            // Write the Components back to the DB, in case something was set to alert.
            $COMPONENT->setComponentPrefs($device['device_id'],$COMPONENTS);

            echo $ctype.' ';
        } // End if RESULTS
    }
    unset($COUNTERS, $RESULT, $ctype, $API, $COMPONENTS, $COMPONENT);
}