<?php
/*
 * LibreNMS module to Graph Cisco Voice components.
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

if ($device['os_group'] == "ucos") {
    require_once 'includes/component.php';
    $COMPONENT = new component();

    /*
     * UCOS Services
     * This module collects a list of services and their status from UCOS devices.
     */
    include "cisco-voice/ucos-services.inc.php";

    // Do we have any UCOS components, if so we should insert ourselves into the application table.
    $options['filter']['type'] = array('LIKE','UCOS-');
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);
    if (count($COMPONENTS[$device['device_id']]) > 0) {
        if (dbFetchCell('SELECT COUNT(*) FROM `applications` WHERE `device_id` = ? AND `app_type` = ?', array($device['device_id'], 'UCOS')) == '0') {
            dbInsert(array('device_id' => $device['device_id'], 'app_type' => 'UCOS'), 'applications');
        }
    } else {
        dbDelete('applications', '`device_id` = ? AND `app_type` = ?', array($device['device_id'], 'UCOS'));
    }

    /*
     * CallManger Basic Resources
     * This module graphs some basic resources in a CallManager Server
     */
    include "cisco-voice/cucm-basic.inc.php";

    /*
     * CallManger Enhanced Location Call Admission Control
     * This module graphs the bandwidth to each location in a CallManager Server
     */
    include "cisco-voice/cucm-elcac.inc.php";

    /*
     * CallManger SIP
     * This module graphs the call counters for SIP trunks on a CallManager Server
     */
    include "cisco-voice/cucm-sip.inc.php";

    /*
     * CallManger H323
     * This module graphs the call counters for Non-GK controlled ICT's on a CallManager Server
     */
    include "cisco-voice/cucm-h323.inc.php";

    // Do we have any CUCM components, if so we should insert ourselves into the application table.
    $options['filter']['type'] = array('LIKE','CUCM-');
    $COMPONENTS = $COMPONENT->getComponents($device['device_id'],$options);
    if (count($COMPONENTS[$device['device_id']]) > 0) {
        if (dbFetchCell('SELECT COUNT(*) FROM `applications` WHERE `device_id` = ? AND `app_type` = ?', array($device['device_id'], 'CUCM')) == '0') {
            dbInsert(array('device_id' => $device['device_id'], 'app_type' => 'CUCM'), 'applications');
        }
    } else {
        dbDelete('applications', '`device_id` = ? AND `app_type` = ?', array($device['device_id'], 'CUCM'));
    }

}