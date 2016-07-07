<?php

if ($device['sysObjectID'] == 'enterprises.9.1.2139') {
    $hardware = 'Hardware Appliance';
}
elseif ($device['sysObjectID'] == 'enterprises.9.1.1426') {
    $hardware = 'Virtual Machine';
}
else {
    $hardware = 'Unknown';
}

