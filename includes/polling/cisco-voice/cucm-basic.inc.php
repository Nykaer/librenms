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

    $MODULE = 'CUCM-Basic';

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
                $RRD = array();
                $RRD['filename'] = $config['rrd_dir'] . "/" . $device['hostname'] . "/" . safename ($MODULE."-".$ARRAY['label'].".rrd");

                // Enable the graph.
                $graphs[$MODULE.'-'.$ARRAY['label']] = TRUE;

                switch ($ARRAY['label']) {
                    case 'Calls':
                        $RRD['create'] = " DS:calls:GAUGE:600:0:U DS:videocalls:GAUGE:600:0:U DS:encryptedcalls:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\CallsActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VideoCallsActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\EncryptedCallsActive');
                        break;
                        case 'InitializationState':
                        unset($RRD);                                    // We don't need an RRD for this one.
                        unset($graphs[$MODULE.'-'.$ARRAY['label']]);    // Disable this graph because there is none.
                        $COMPONENTS[$COMPID]['status'] = 0;             // Guilty Until proven innocent.
                        $COMPONENTS[$COMPID]['state'] = $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\InitializationState');
                        if ($COMPONENTS[$COMPID]['state'] == 100) {
                            $COMPONENTS[$COMPID]['status'] = 1;
                        }
                        break;
                    case 'Replicate_State':
                        unset($RRD);                                    // We don't need an RRD for this one.
                        unset($graphs[$MODULE.'-'.$ARRAY['label']]);    // Disable this graph because there is none.
                        $COMPONENTS[$COMPID]['status'] = 0;             // Guilty Until proven innocent.
                        $COMPONENTS[$COMPID]['state'] = $API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Number of Replicates Created and State of Replication(ReplicateCount)\Replicate_State');
                        if ($COMPONENTS[$COMPID]['state'] == 2) {
                            $COMPONENTS[$COMPID]['status'] = 1;
                        }
                        break;
                    case 'AnnunciatorResource':
                        $RRD['create'] = " DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\AnnunciatorResourceTotal');
                        break;
                    case 'MTPResource':
                        $RRD['create'] = " DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MTPResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MTPResourceTotal');
                        break;
                    case 'TranscoderResource':
                        $RRD['create'] = " DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\TranscoderResourceTotal');
                        break;
                    case 'VCBConferences':
                        $RRD['create'] = " DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBConferencesActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBConferencesTotal');
                        break;
                    case 'VCBResource':
                        $RRD['create'] = " DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\VCBResourceTotal');
                        break;
                    case 'MOHMulticastResources':
                        $RRD['create'] = " DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHMulticastResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHTotalMulticastResources');
                        break;
                    case 'MOHUnicastResources':
                        $RRD['create'] = " DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHUnicastResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\MOHTotalUnicastResources');
                        break;
                    case 'SWConferenceResource':
                        $RRD['create'] = " DS:conferences:GAUGE:600:0:U DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\SWConferenceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\SWConferenceResourceTotal');
                        break;
                    case 'HWConferenceResource':
                        $RRD['create'] = " DS:conferences:GAUGE:600:0:U DS:active:GAUGE:600:0:U DS:total:GAUGE:600:0:U";
                        $RRD['data'] = "N:".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\HWConferenceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceActive');
                        $RRD['data'] .= ":".$API->getRRDValue($STATISTICS,'\\\\'.$HOST.'\Cisco CallManager\HWConferenceResourceTotal');
                        break;
                    default:
                        d_echo("Unknown Component label: ".$ARRAY['label']);
                } // End Switch

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

            // Write the Components back to the DB, in case something was set to alert.
            $COMPONENT->setComponentPrefs($device['device_id'],$COMPONENTS);

            echo $MODULE.' ';
        } // End if RESULTS
    }
    unset($RRD, $COUNTERS, $RESULT, $MODULE, $API, $COMPONENTS, $COMPONENT);
}