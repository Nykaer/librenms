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

// Get the config
$ldapuser = get_dev_attrib($device, 'ldap_user');
$ldappass = get_dev_attrib($device, 'ldap_pass');
$ldapport = get_dev_attrib($device, 'ldap_port');
$base = get_dev_attrib($device, 'ldap_search_base');

$module = 'LDAP_UsersPerOU';
echo $module.': ';

if (isset($ldapuser) && isset($ldappass) && isset($ldapport) && isset($base)) {
    $component = new LibreNMS\Component();
    $options['filter']['type'] = array('=',$module);
    $options['filter']['disabled'] = array('=',0);
    $options['filter']['ignore'] = array('=',0);
    $components = $component->getComponents($device['device_id'], $options);

    // We only care about our device id.
    $components = $components[$device['device_id']];

    // Only collect SNMP data if we have enabled components
    if (count($components > 0)) {
        // Let's gather the stats..
        $string = 'ldap://'.$device['hostname'].":".$ldapport.'/';
        $ldapconn = ldap_connect($string) or d_echo("Could not connect to LDAP server - ".$string.".\n");
        if ($ldapconn) {
            // binding to ldap server
            $ldapbind = ldap_bind($ldapconn, $ldapuser, $ldappass) or d_echo("Error trying to bind: " . ldap_error($ldapconn) . "\n");

            // verify binding
            if ($ldapbind) {
                /*
                 * We are successfully connected to LDAP.
                 * Lets do some stuff.
                 */

                // Loop through the components and extract the data.
                foreach ($components as $key => &$array) {
                    // extract all records of type CN
                    $ou = $array['label'];
                    $CNSEARCH = ldap_search($ldapconn, $ou, "(cn=*)") or d_echo("Error in search query: ".ldap_error($ldapconn)."\n");

                    // Add the count to the array.
                    $count = ldap_count_entries($ldapconn, $CNSEARCH);

                    $fields = array(
                        'users' => $count,
                    );

                    // Let's make sure the rrd is setup for this class.
                    $rrd_name = array('ldap', 'users', $ou);
                    $rrd_def = 'DS:users:GAUGE:600:0:U';
                    $tags = compact('uid', 'rrd_name', 'rrd_def');
                    data_update($device, 'ldap', $tags, $fields);
                    $graphs['ldap_usersperou'] = true;
                    
                    // Let's print some debugging info.
                    d_echo("\n\nComponent: " . $key . "\n");
                    d_echo("    OU:     " . $ou . "\n");
                    d_echo("    Count:  " . $count . "\n");
                }
            } else {
                d_echo("LDAP bind failed...\n");
            }
        }

        // all done? clean up
        ldap_close($ldapconn);

        echo $module." ";

        // Clean-up after yourself!
        unset($type, $components, $component, $options, $module);
    } // end if count components
} else {
    d_echo("LDAP Attributes not set\n");
}
