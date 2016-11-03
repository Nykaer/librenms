<?php
/*
 * LibreNMS module to Count how many users there are in an LDAP OU.
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

// config
$ldapuser = get_dev_attrib($device, 'ldap_user');
$ldappass = get_dev_attrib($device, 'ldap_pass');
$ldapport = get_dev_attrib($device, 'ldap_port');
$base = get_dev_attrib($device, 'ldap_search_base');

$module = 'LDAP_UsersPerOU';
echo $module.': ';

if (isset($ldapuser) && isset($ldappass) && isset($base)) {
    $component = new LibreNMS\Component();
    $components = $component->getComponents($device['device_id'], array('type'=>$module));

    // We only care about our device id.
    $components = $components[$device['device_id']];

    // Begin our master array, all other values will be processed into this array.
    $OUs = array();

    $string = 'ldap://'.$device['hostname'].":".$ldapport.'/';
    d_echo("Trying to connect to: ".$string."\n");
    $ldapconn = ldap_connect($string) or d_echo("Could not connect to LDAP server - ".$string.".\n");
    if ($ldapconn) {
        // binding to ldap server
        $ldapbind = ldap_bind($ldapconn, $ldapuser, $ldappass) or d_echo("Error trying to bind: ".ldap_error($ldapconn)."\n");

        // verify binding
        if ($ldapbind) {
            /*
             * We are successfully connected to LDAP.
             * Lets do some stuff.
             */

            // Find all OU's in our search base.
            $OULIST = ldap_list($ldapconn, $base, "ou=*", array("ou"));
            $OU = ldap_get_entries($ldapconn, $OULIST);
            unset($OU['count']);
            foreach ($OU as $k => $v) {
                $res = array();
                $res['UID'] = hash('crc32', $v['dn']);
                $res['label'] = $v['dn'];
                $res['dn'] = $v['ou'][0];
                d_echo("OU Found: ".$v['dn']."\n");

                // Add the OU to the array
                $OUs[] = $res;
            }

            /*
             * Ok, we have our 2 array's (Components and LDAP) now we need
             * to compare and see what needs to be added/updated.
             *
             * Let's loop over the SNMP data to see if we need to ADD or UPDATE any components.
             */
            foreach ($OUs as $key => $array) {
                $component_key = false;

                // Loop over our components to determine if the component exists, or we need to add it.
                foreach ($components as $compid => $child) {
                    if ($child['UID'] === $array['UID']) {
                        $component_key = $compid;
                    }
                }

                if (!$component_key) {
                    // The component doesn't exist, we need to ADD it - ADD.
                    $new_component = $component->createComponent($device['device_id'], $module);
                    $component_key = key($new_component);
                    $components[$component_key] = array_merge($new_component[$component_key], $array);
                    echo "+";
                } else {
                    // The component does exist, merge the details in - UPDATE.
                    $components[$component_key] = array_merge($components[$component_key], $array);
                    echo ".";
                }
            }

            /*
             * Loop over the Component data to see if we need to DELETE any components.
             */
            foreach ($components as $key => $array) {
                // Guilty until proven innocent
                $found = false;

                foreach ($OUs as $k => $v) {
                    if ($array['UID'] == $v['UID']) {
                        // Yay, we found it...
                        $found = true;
                    }
                }

                if ($found === false) {
                    // The component has not been found. we should delete it.
                    echo "-";
                    $component->deleteComponent($key);
                }
            }

            // Write the Components back to the DB.
            $component->setComponentPrefs($device['device_id'], $components);
            echo "\n";
        } else {
            d_echo("LDAP bind failed...\n");
        }
    }
// all done? clean up
    ldap_close($ldapconn);
} else {
    d_echo("LDAP Attributes not set\n");
}
