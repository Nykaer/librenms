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
    /*
     * UCOS Services
     * This module collects a list of services and their status from UCOS devices.
     */
    include "cisco-voice/ucos-services.inc.php";

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

}