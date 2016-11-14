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

    // Our Counter Array.
    $counter = array();

    // Add a counter for each enabled component
    foreach($components as $compid => $array) {
        switch ($array['label']) {
            case 'Calls':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\CallsActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\VideoCallsActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\EncryptedCallsActive';
                break;
            case 'InitializationState':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\InitializationState';
                break;
            case 'Replicate_State':
                $counter[] = '\\\\'.$host.'\Number of Replicates Created and State of Replication(ReplicateCount)\Replicate_State';
                break;
            case 'AnnunciatorResource':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\AnnunciatorResourceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\AnnunciatorResourceTotal';
                break;
            case 'MTPResource':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\MTPResourceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\MTPResourceTotal';
                break;
            case 'TranscoderResource':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\TranscoderResourceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\TranscoderResourceTotal';
                break;
            case 'VCBConferences':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\VCBConferencesActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\VCBConferencesTotal';
                break;
            case 'VCBResource':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\VCBResourceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\VCBResourceTotal';
                break;
            case 'MOHUnicastResources':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\MOHUnicastResourceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\MOHTotalUnicastResources';
                break;
            case 'SWConferenceResource':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\SWConferenceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\SWConferenceResourceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\SWConferenceResourceTotal';
                break;
            case 'HWConferenceResource':
                $counter[] = '\\\\'.$host.'\Cisco CallManager\HWConferenceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\HWConferenceResourceActive';
                $counter[] = '\\\\'.$host.'\Cisco CallManager\HWConferenceResourceTotal';
                break;
            default:
                d_echo("Unknown Component label: ".$array['label']);
        }
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

                // Enable the graph.
                $graphs[$ctype.'-'.$label] = TRUE;

                switch ($label) {
                    case 'Calls':
                        $fields = array(
                            'calls' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\CallsActive'),
                            'videocalls' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\VideoCallsActive'),
                            'encryptedcalls' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\EncryptedCallsActive'),
                        );
                        $rrd_def = array(
                            'DS:calls:GAUGE:600:0:U',
                            'DS:videocalls:GAUGE:600:0:U',
                            'DS:encryptedcalls:GAUGE:600:0:U',
                        );
                        break;
                        case 'InitializationState':
                        unset($graphs[$ctype.'-'.$label]);    // Disable this graph because there is none.
                        $components[$compid]['status'] = 2;             // Guilty Until proven innocent.
                        $components[$compid]['state'] = $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\InitializationState');
                        if ($components[$compid]['state'] == 100) {
                            $components[$compid]['status'] = 0;
                        }
                        break;
                    case 'Replicate_State':
                        unset($graphs[$ctype.'-'.$label]);    // Disable this graph because there is none.
                        $components[$compid]['status'] = 2;             // Guilty Until proven innocent.
                        $components[$compid]['state'] = $api->getRRDValue($statistics,'\\\\'.$host.'\Number of Replicates Created and State of Replication(ReplicateCount)\Replicate_State');
                        if ($components[$compid]['state'] == 2) {
                            $components[$compid]['status'] = 0;
                        }
                        break;
                    case 'AnnunciatorResource':
                        $fields = array(
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\AnnunciatorResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\AnnunciatorResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'MTPResource':
                        $fields = array(
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\MTPResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\MTPResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'TranscoderResource':
                        $fields = array(
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\TranscoderResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\TranscoderResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'VCBConferences':
                        $fields = array(
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\VCBConferencesActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\VCBConferencesTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'VCBResource':
                        $fields = array(
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\VCBResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\VCBResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'MOHMulticastResources':
                        $fields = array(
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\MOHMulticastResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\MOHTotalMulticastResources'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'MOHUnicastResources':
                        $fields = array(
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\MOHUnicastResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\MOHTotalUnicastResources'),
                        );
                        $rrd_def = array(
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'SWConferenceResource':
                        $fields = array(
                            'conferences' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\SWConferenceActive'),
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\SWConferenceResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\SWConferenceResourceTotal'),
                        );
                        $rrd_def = array(
                            'DS:conferences:GAUGE:600:0:U',
                            'DS:active:GAUGE:600:0:U',
                            'DS:total:GAUGE:600:0:U',
                        );
                        break;
                    case 'HWConferenceResource':
                        $fields = array(
                            'conferences' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\HWConferenceActive'),
                            'active' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\HWConferenceResourceActive'),
                            'total' => $api->getRRDValue($statistics,'\\\\'.$host.'\Cisco CallManager\HWConferenceResourceTotal'),
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
            $component->setComponentPrefs($device['device_id'],$components);

            echo $ctype.' ';
        } // End if RESULTS
    }
    unset($counter, $result, $ctype, $api, $components, $component);
}