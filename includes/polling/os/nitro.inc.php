<?php

if ($device['sysObjectID'] == 'enterprises.23128.1000.1.1') {
    $features = 'ETM???';
}
elseif ($device['sysObjectID'] == 'enterprises.23128.1000.3.1') {
    $features = 'ERC???';
}
elseif ($device['sysObjectID'] == 'enterprises.23128.1000.7.1') {
    $features = 'ELM';
}
elseif ($device['sysObjectID'] == 'enterprises.23128.1000.11.1') {
    $features = 'ACE';
}
else {
    $features = 'Unknown';
}

// McAfee ACE 9.5.0
if (preg_match('/^McAfee [A-Z]{3} ([^,]+)$/', $device['sysDescr'], $regexp_result)) {
    $version  = $regexp_result[1];
}