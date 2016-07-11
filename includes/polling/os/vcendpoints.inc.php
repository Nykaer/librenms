<?php

// Cisco CodecSoftW: ce8.1.0.b8c0ca3MCU: Cisco TelePresence SX20Date: 2016-04-05S/N: FTT193002F3
if (preg_match('/^Cisco CodecSoftW ([^,]+)MCU: Cisco Telepresence ([^,]+)Date: [^,]+S/N: ([^,]+)$/', $device['sysDescr'], $regexp_result)) {
    $version  = $regexp_result[1];
    $hardware = $regexp_result[2];
    $serial = $regexp_result[3];
}
else {
    $version = '';
    $hardware = '';
    $serial = '';
}
