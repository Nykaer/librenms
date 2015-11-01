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

if ($device['os_group'] == "cisco") {

    /*
     * Cisco PRI
     * This module graphs the used and total DS0 channels on a Cisco Voice Gateway
     */
    include "cisco-voice/cisco-iospri.inc.php";

    /*
     * Cisco DSP
     * This module graphs the used and total DSP resources on a Cisco Voice Gateway
     */
    include "cisco-voice/cisco-iosdsp.inc.php";

    /*
     * Cisco MTP
     * This module graphs the used and total MTP resources on a Cisco Voice Gateway
     */
    include "cisco-voice/cisco-iosmtp.inc.php";

    /*
     * Cisco XCode
     * This module graphs the used and total Transcoder resources on a Cisco Voice Gateway
     */
    include "cisco-voice/cisco-xcode.inc.php";

    /*
     * Cisco CallManager Active Calls
     * This module graphs the Active Calls on a Cisco CallManager Server
     */
    include "cisco-voice/cucm-callsactive.inc.php";
}

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
     * This module graphs the call counters for H323 Non-GK controlled ICT's on a CallManager Server
     */
    include "cisco-voice/cucm-h323.inc.php";

}