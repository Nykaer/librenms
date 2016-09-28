<?php
/*
 * LibreNMS module to Graph Cisco Voice components.
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

if ($device['os'] == "ios") {
    /*
     * Cisco Dial-Peer
     * This module processes IOS dial-peers on an IOS Cisco Voice Gateway
     */
    include "cisco-voice/cisco-dialpeer.inc.php";

}
