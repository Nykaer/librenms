<?php

$hardware = str_replace('"','',snmp_get($device, '.1.3.6.1.4.1.9.9.719.1.9.6.1.6.1', '-Oqv'));
$serial = str_replace('"','',snmp_get($device, '.1.3.6.1.4.1.9.9.719.1.9.6.1.14.1', '-Oqv'));

// Cisco Integrated Management Controller(CIMC) [], Firmware Version 1.5(1b) Copyright (c) 2008-2012, Cisco Systems, Inc.
if (preg_match('/Firmware Version ([^,]+) Copyright/', $device['sysDescr'], $regexp_result)) {
    $version  = $regexp_result[1];
}
else {
    $version = '';
}