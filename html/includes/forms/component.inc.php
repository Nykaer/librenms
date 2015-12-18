<?php

if (is_admin() === false) {
    $response = array(
        'status'  => 'error',
        'message' => 'Need to be admin',
    );
    echo _json_encode($response);
    exit;
}

$status     = 'error';
$message    = 'Error with config';

// enable/disable components on devices.
$device_id    = intval($_POST['device']);

require_once "../includes/component.php";
$OBJCOMP = new component();

// Go get the component array.
$COMPONENTS = $OBJCOMP->getComponents($device_id);

// Track how many updates we are making.
$UPDATE = array();

foreach ($COMPONENTS[$device_id] as $ID => $AVP) {
    // Is the component disabled?
    if (isset($_POST['dis_'.$ID])) {
        // Yes it is, was it disabled before?
        if ($COMPONENTS[$device_id][$ID]['disabled'] == 0) {
            // No it wasn't, best we disable it then..
            $COMPONENTS[$device_id][$ID]['disabled'] = 1;

// We only care about our device id.
$COMPONENTS = $COMPONENTS[$device_id];

// Track how many updates we are making.
$UPDATE = array();

foreach ($COMPONENTS as $ID => $AVP) {
    // Is the component disabled?
    if (isset($_POST['dis_'.$ID])) {
        // Yes it is, was it disabled before?
        if ($COMPONENTS[$ID]['disabled'] == 0) {
            // No it wasn't, best we disable it then..
            $COMPONENTS[$ID]['disabled'] = 1;
            $UPDATE[$ID] = true;
        }
    }
    else {
        // No its not, was it disabled before?
        if ($COMPONENTS[$ID]['disabled'] == 1) {
            // Yes it was, best we enable it then..
            $COMPONENTS[$ID]['disabled'] = 0;
            $UPDATE[$ID] = true;
        }
    }

    // Is the component ignored?
    if (isset($_POST['ign_'.$ID])) {
        // Yes it is, was it ignored before?
        if ($COMPONENTS[$ID]['ignore'] == 0) {
            // No it wasn't, best we ignore it then..
            $COMPONENTS[$ID]['ignore'] = 1;
            $UPDATE[$ID] = true;
        }
    }
    else {
        // No its not, was it ignored before?
        if ($COMPONENTS[$ID]['ignore'] == 1) {
            // Yes it was, best we un-ignore it then..
            $COMPONENTS[$ID]['ignore'] = 0;
            $UPDATE[$ID] = true;
        }
    }
}

if (count($UPDATE) > 0) {
    // Update our edited components.
    $STATUS = $OBJCOMP->setComponentPrefs($device_id,$COMPONENTS);

    $message    = count($UPDATE).' Device records updated.';
    $status     = 'ok';
}
else {
    $message    = 'Record unchanged. No update necessary.';
    $status     = 'ok';
}

$response = array(
    'status'    => $status,
    'message'   => $message,
);
echo _json_encode($response);