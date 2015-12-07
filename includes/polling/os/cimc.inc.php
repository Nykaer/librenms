<?php

$hardware = str_replace('"','',snmp_get($device, '.1.3.6.1.4.1.9.9.719.1.9.6.1.6.1', '-Oqv'));
$version = '';
$serial = str_replace('"','',snmp_get($device, '.1.3.6.1.4.1.9.9.719.1.9.6.1.14.1', '-Oqv'));
