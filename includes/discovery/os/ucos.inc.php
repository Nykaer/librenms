<?php
/*
 * Cisco Unified Communications Operating System - UCOS
 */

if (!$os) {
    if (strstr($sysObjectId, '.1.3.6.1.4.1.9.1.1348')) {
        $os = "ucos";
    }
}