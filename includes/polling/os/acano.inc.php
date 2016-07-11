<?php

if (preg_match('/Acano ([^,]+)/', $device['sysDescr'], $regexp_result)) {
    $version  = $regexp_result[1];
}
else {
    $version = '';
}
