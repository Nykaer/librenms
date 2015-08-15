<?php
/*
 * LibreNMS module to Interface with the Component System
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

class component {
    /*
     * These fields are used in the component table. They are returned in the array
     * so that they can be modified but they can not be set as user attributes. We
     * also set their default values.
     */
    private $reserved = array(
        'type'      => '',
        'label'     => '',
        'status'    => 1,
        'ignore'    => 0,
        'disabled'  => 0,
    );

    public function getComponentType($TYPE=null) {
        if (is_null($TYPE)) {
            $SQL = "SELECT DISTINCT `type` as `name` FROM `component` ORDER BY `name`";
            $row = dbFetchRow($SQL, array());
        }
        else {
            $SQL = "SELECT DISTINCT `type` as `name` FROM `component` WHERE `type` = ? ORDER BY `name`";
            $row = dbFetchRow($SQL, array($TYPE));
        }

        if (!isset($row)) {
            // We didn't find any component types
            return false;
        }
        else {
            // We found some..
            return $row;
        }
    }

    public function getComponentCount($device_id,$options=array()) {
        // Our base SQL Query, with no options.
        $SQL = "SELECT COUNT(`id`) FROM `component` WHERE `device_id` = ?";
        $PARAM = array($device_id);

        // Type is shorthand for filter type = $type.
        if (isset($options['type'])) {
            $options['filter']['type'] = array('=', $options['type']);
        }

        // filter   field => array(operator,value)
        //          Filters results based on the field, operator and value
        if (isset($options['filter'])) {
            $SQL .= " AND ( ";
            foreach ($options['filter'] as $field => $array) {
                if ($array[0] == 'LIKE') {
                    $SQL .= "`".$field."` LIKE '%?%' AND ";
                }
                else {
                    // Equals operator is the default
                    $SQL .= "`".$field."` = ? AND ";
                }
                array_push($PARAM,$value);
            }
            // Strip the last " AND " before closing the bracket.
            $SQL = substr($SQL,0,-5)." )";
        }

        // Get our results using our built SQL.
        $RESULT = dbFetchCell($SQL, $PARAM);
        if (empty($RESULT)) {
            return 0;
        }
        else {
            return $RESULT;
        }
    }

    public function getComponents($device_id,$options=array()) {
        // Define our results array, this will be set even if no rows are returned.
        $RESULT = array();

        // Our base SQL Query, with no options.
        $SQL = "SELECT `id`,`type`,`label`,`status`,`disabled`,`ignore` FROM `component` WHERE `device_id` = ?";
        $PARAM = array($device_id);

        // Type is shorthand for filter type = $type.
        if (isset($options['type'])) {
            $options['filter']['type'] = array('=', $options['type']);
        }

        // filter   field => array(operator,value)
        //          Filters results based on the field, operator and value
        if (isset($options['filter'])) {
            $SQL .= " AND ( ";
            foreach ($options['filter'] as $field => $array) {
                if ($array[0] == 'LIKE') {
                    $SQL .= "`".$field."` LIKE ? AND ";
                    $array[1] = "%".$array[1]."%";
                }
                else {
                    // Equals operator is the default
                    $SQL .= "`".$field."` = ? AND ";
                }
                array_push($PARAM,$array[1]);
            }
            // Strip the last " AND " before closing the bracket.
            $SQL = substr($SQL,0,-5)." )";
        }

        // sort     column direction
        //          Add SQL sorting to the results
        if (isset($options['sort'])) {
            $SQL .= " ORDER BY ".$options['sort'];
        }

        // limit    array(start,count)
        //          Adds a SQL limit to the rows returned
        if (isset($options['limit'])) {
            $SQL .= " LIMIT ".$options['limit'][0].",".$options['limit'][1];
        }

        // Get our component records using our built SQL.
        $COMPONENTS = dbFetchRows($SQL, $PARAM);

        // if we have no components we need to return nothing
        if (count($COMPONENTS) == 0) {
            return $RESULT;
        }

        // Build the SQL to grab the AVP's we are after.
        $SQL = "SELECT `component`,`attribute`,`value` FROM `component_prefs` WHERE ";
        $PARAM = array ();
        foreach ($COMPONENTS as $COMPONENT) {
            $SQL .= "`component`= ? OR ";
            array_push($PARAM,$COMPONENT['id']);
        }
        // Strip the last "OR ".
        $SQL = substr($SQL,0,-3);

        // Grab the data.
        $PREFERENCES = dbFetchRows($SQL, $PARAM);

        // Add each preference to the array.
        foreach ($PREFERENCES as $AVP) {
            $RESULT[$AVP['component']][$AVP['attribute']] = $AVP['value'];
        }

        // Populate our reserved fields into the Array, these cant be added as user attributes.
        foreach ($COMPONENTS as $COMPONENT) {
            foreach ($this->reserved as $k => $v) {
                $RESULT[$COMPONENT['id']][$k] = $COMPONENT[$k];
            }

            // Sort each component array so the attributes are in order.
            ksort($RESULT[$COMPONENT['id']]);
        }

        return $RESULT;
    }

    public function createComponent ($device_id,$TYPE) {
        // Prepare our default values to be inserted.
        $DATA = $this->reserved;

        // Add the device_id and type
        $DATA['device_id']  = $device_id;
        $DATA['type']       = $TYPE;

        // Insert a new component into the database.
        $id = dbInsert($DATA, 'component');

        // Create a default component array based on what was inserted.
        $ARRAY[$id] = $DATA;
        unset ($ARRAY[$id]['device_id']);     // This doesn't belong here.
        return $ARRAY;
    }

    public function deleteComponent ($id) {
        // Delete a component from the database.
        return dbDelete('component', "`id` = ?",array($id));
    }

    public function setComponentPrefs ($device_id,$ARRAY) {
        // Compare the arrays. Update/Insert where necessary.

        $OLD = $this->getComponents($device_id);
        // Loop over each component.
        foreach ($ARRAY as $COMPONENT => $AVP) {

            // Make sure the component already exists.
            if (!isset($OLD[$COMPONENT])) {
                // Error. Component doesn't exist in the database.
                continue;
            }

            // Ignore type, we cant change that.
            unset($AVP['type'],$OLD[$COMPONENT]['type']);

            // Process our reserved components first.
            $UPDATE = array();
            foreach ($this->reserved as $k => $v) {
                // does the reserved field exist, if not skip.
                if (isset($AVP[$k])) {

                    // Has the value changed?
                    if ($AVP[$k] != $OLD[$COMPONENT][$k]) {
                        // The value has been modified, add it to our update array.
                        $UPDATE[$k] = $AVP[$k];
                    }

                    // Unset the reserved field. We don't want to insert it below.
                    unset($AVP[$k],$OLD[$COMPONENT][$k]);
                }
            }

            // Has anything changed, do we need to update?
            if (count($UPDATE) > 0) {
                // We have data to update
                dbUpdate($UPDATE, 'component', '`id` = ?', array($COMPONENT));

                // Log the update to the Eventlog.
                $MSG = "Component ".$COMPONENT." has been modified: ";
                foreach ($UPDATE as $k => $v) {
                    $MSG .= $k." => ".$v.",";
                }
                $MSG = substr($MSG,0,-1);
                log_event($MSG,$device_id,'component',$COMPONENT);
            }

            // Process our AVP Adds and Updates
            foreach ($AVP as $ATTR => $VALUE) {
                // We have our AVP, lets see if we need to do anything with it.

                if (!isset($OLD[$COMPONENT][$ATTR])) {
                    // We have a newly added attribute, need to insert into the DB
                    $DATA = array('component'=>$COMPONENT, 'attribute'=>$ATTR, 'value'=>$VALUE);
                    $id = dbInsert($DATA, 'component_prefs');

                    // Log the addition to the Eventlog.
                    log_event("Component: ".$AVP[$COMPONENT]['type']."(".$COMPONENT."). Attribute: ".$ATTR.", was added with value: ".$VALUE,$device_id,'component',$COMPONENT);
                }
                elseif ($OLD[$COMPONENT][$ATTR] != $VALUE) {
                    // Attribute exists but the value is different, need to update
                    $DATA = array('value'=>$VALUE);
                    dbUpdate($DATA, 'component_prefs', '`component` = ? AND `attribute` = ?', array($COMPONENT, $ATTR));

                    // Add the modification to the Eventlog.
                    log_event("Component: ".$AVP[$COMPONENT]['type']."(".$COMPONENT."). Attribute: ".$ATTR.", was modified from: ".$OLD[$COMPONENT][$ATTR].", to: ".$VALUE,$device_id,'component',$COMPONENT);
                }

            } // End Foreach COMPONENT

            // Process our Deletes.
            $DELETE = array_diff_key($OLD[$COMPONENT], $AVP);
            foreach ($DELETE as $KEY => $VALUE) {
                // As the Attribute has been removed from the array, we should remove it from the database.
                dbDelete('component_prefs', "`component` = ? AND `attribute` = ?",array($COMPONENT,$KEY));

                // Log the addition to the Eventlog.
                log_event("Component: ".$AVP[$COMPONENT]['type']."(".$COMPONENT."). Attribute: ".$ATTR.", was deleted.",$COMPONENT);
            }

        }

        return true;
    }

}