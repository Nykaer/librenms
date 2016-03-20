<?php
/*
 * LibreNMS module to Interface with the Device Configuration System
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

class device_config {
    // A cache of retrieved data, so we don't unnecessarily go to the DB.
    private $cache = array();

    private function getConfigGlobal() {
        // Let's see if the config for this device is in our cache.
        if (isset($this->cache['global']))
        {
            // Yes, it is..
            $config = $this->cache['global'];
        } else {
            // No, Lets go get it.
            $SQL = "SELECT `attribute`,`value`,`desc`,`display` FROM device_config_global";
            $result = dbFetchRows($SQL, array());

            // Build the global config array.
            $config = array();
            foreach ($result as $v) {
                $config[$v['attribute']] = array('value'=>$v['value'], 'desc'=>$v['desc'], 'display'=>$v['display'], 'source'=>'global');
            }

            // Write our global config to the cache
            $this->cache['global'] = $config;
        }

        // Return the config
        return $config;
    }

    private function getConfigGroup($group) {
        // Let's see if the config for this device is in our cache.
        if (isset($this->cache['group'][$group]))
        {
            // Yes, it is..
            $config = $this->cache['group'][$group];
        } else {
            // No, Lets go get it.
            $SQL = "SELECT `attribute`,`value` FROM device_config_group WHERE `group` = ?";
            $result = dbFetchRows($SQL, array($group));

            // Build the group config array.
            $config = array();
            foreach ($result as $v) {
                $config[$v['attribute']] = $v['value'];
            }

            // Write our group config to the cache
            $this->cache['group'][$group] = $config;
        }

        // Return the config
        return $config;
    }

    private function getConfigLocal($device) {
        // Let's see if the config for this device is in our cache.
        if (isset($this->cache['device'][$device]))
        {
            // Yes, it is..
            $config = $this->cache['device'][$device];
        } else {
            // No, Lets go get it.
            $SQL = "SELECT `attribute`,`value` FROM device_config_local WHERE `device` = ?";
            $result = dbFetchRows($SQL, array($device));

            // Build the device config array.
            $config = array();
            foreach ($result as $v) {
                $config[$v['attribute']] = $v['value'];
            }

            // Write our group config to the cache
            $this->cache['device'][$device] = $config;
        }

        // Return the config
        return $config;
    }

    public function getConfigFull($device) {
        /*
         * getConfigFull.
         * Get a full version of the config array
         * This is used when you need all the info including where the
         * variable came from and how to display it in an edit form.
         *
         * This is built from:
         * 1. Global Config, overwritten by
         * 2. Group Config, overwritten by
         * 3. Device Config.
         */

        // 1. Global Config
        $config = $this->getConfigGlobal();

        // 2. Group Config
        $groups = getGroupsFromDevice($device);
        foreach($groups as $group) {
            // Get the config for this group
            $groupconfig = $this->getConfigGroup($group);
            foreach ($groupconfig as $k => $v) {
                // Overwrite each group attribute in the master array.
                $config[$k]['value'] = $v;
                $config[$k]['source'] = 'group';
            }
        }

        // 3. Device Config
        $deviceconfig = $this->getConfigLocal($device);
        foreach ($deviceconfig as $k => $v) {
            // Overwrite each group attribute in the master array.
            $config[$k]['value'] = $v;
            $config[$k]['source'] = 'device';
        }

        // return the final config
        return $config;
    }

    public function getConfig($device) {
        /*
         * getConfig.
         * Get a stripped down version of the config array
         * This is used when you only need the data.
         */

        $configfull = $this->getConfigFull($device);

        $config = array();
        foreach ($configfull as $k => $v) {
            $config[$k] = $v['value'];
        }

        // return the final config
        return $config;
    }

}
