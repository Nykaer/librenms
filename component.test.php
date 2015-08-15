#!/usr/bin/php -q
<?php

require 'includes/defaults.inc.php';
require 'config.php';
require 'includes/definitions.inc.php';
require 'includes/functions.php';

require 'includes/component.php';


$COMPONENT = new component();

/*
 * Component Testing
 * Lets make sure and modifications we do don't change the API and break things..
 */
$PASS = 0;
$FAIL = 0;
$DETAILS = false;

// For our testing, we will use device id 1.
$DEVICE = 1;

// The type, this will be used for our testing.
$TYPE = 'TESTING';

/*
 * Create Component
 * Create a new component
 */
echo "Create Component:                         ";
$ARRAY = $COMPONENT->createComponent($DEVICE,$TYPE);
if (is_array($ARRAY)) {
    echo "PASS\n";
    $PASS++;
}
else {
    echo "FAIL\n";
    $FAIL++;
}
if ($DETAILS) {
    echo "New, Empty Component: ";
    print_r($ARRAY);
}

/*
 * Get Component Type.
 * Can we retrieve the component type we have just created.
 */
echo "Get Component Type:                       ";
$TEST = $COMPONENT->getComponentType($TYPE);
if ($TEST['name'] == $TYPE) {
    echo "PASS\n";
    $PASS++;
}
else {
    echo "FAIL\n";
    $FAIL++;
}
if ($DETAILS) {
    print_r($TEST);
}
unset($TEST);

/*
 * Set Component
 * Set some AVP's on our component.
 */
echo "Set Component:                            ";
foreach ($ARRAY as $ID => &$AVP) {
    $AVP['label'] = 'TEST LABEL';        // Change the label
    $AVP['status'] = 0;                  // Change status to down
    $AVP['ignore'] = 1;                  // Ignore

    $AVP['TEST_ATTR'] = "TEST_ATTR";     // Create a test attribute
}
if ($COMPONENT->setComponentPrefs($DEVICE,$ARRAY)) {
    echo "PASS\n";
    $PASS++;
}
else {
    echo "FAIL\n";
    $FAIL++;
}
if ($DETAILS) {
    echo "Modified Component: ";
    print_r($ARRAY);
}

/*
 * Get Component
 * Get our component from the DB, ensure it matches the details we have set.
 */
echo "Get Component:                            ";
$NEW = $COMPONENT->getComponents($DEVICE);
if (is_array($NEW)) {
    echo "PASS\n";
    $PASS++;
}
else {
    echo "FAIL\n";
    $FAIL++;
}
if ($DETAILS) {
    echo "Modified Component, from database: ";
    print_r($NEW);
}

echo "Compare Set with Get details:\n";
foreach ($ARRAY as $ID => $AVP) {
    if ($ARRAY[$ID]['label'] == $NEW[$ID]['label']) {
        echo "              Label:                      PASS\n";
        $PASS++;
    }
    else {
        echo "              Label:                      FAIL\n";
        $FAIL++;
    }
    if ($ARRAY[$ID]['status'] == $NEW[$ID]['status']) {
        echo "              Status:                     PASS\n";
        $PASS++;
    }
    else {
        echo "              Status:                     FAIL\n";
        $FAIL++;
    }
    if ($ARRAY[$ID]['ignore'] == $NEW[$ID]['ignore']) {
        echo "              Ignore:                     PASS\n";
        $PASS++;
    }
    else {
        echo "              Ignore:                     FAIL\n";
        $FAIL++;
    }
    if ($ARRAY[$ID]['TEST_ATTR'] == $NEW[$ID]['TEST_ATTR']) {
        echo "              TEST_ATTR:                  PASS\n";
        $PASS++;
    }
    else {
        echo "              TEST_ATTR:                  FAIL\n";
        $FAIL++;
    }
}

/*
 * Delete Component
 * Prove we can delete, and also clean up as we are winding up testing.
 */
echo "Delete Component:                         ";
if ($COMPONENT->deleteComponent($ID)) {
    echo "PASS\n";
    $PASS++;
}
else {
    echo "FAIL\n";
    $FAIL++;
}

echo "Testing Completed:\n";
echo "      Pass:                               ".$PASS."\n";
echo "      Fail:                               ".$FAIL."\n";
?>