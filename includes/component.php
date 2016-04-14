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
        'error'     => '',
    );

    private $counter_rrd = array(
        'counter'   => 'COUNTER',
        'gauge'     => 'GAUGE',
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

    public function getComponents($device_id=null,$options=array()) {
        // Define our results array, this will be set even if no rows are returned.
        $RESULT = array();
        $PARAM = array();

        // Our base SQL Query, with no options.
        $SQL = "SELECT `C`.`id`,`C`.`device_id`,`C`.`type`,`C`.`label`,`C`.`status`,`C`.`disabled`,`C`.`ignore`,`C`.`error`,`CP`.`attribute`,`CP`.`value` FROM `component` as `C` LEFT JOIN `component_prefs` as `CP` on `C`.`id`=`CP`.`component` WHERE ";

        // Device_id is shorthand for filter C.device_id = $device_id.
        if (!is_null($device_id)) {
            $options['filter']['device_id'] = array('=', $device_id);
        }

        // Type is shorthand for filter type = $type.
        if (isset($options['type'])) {
            $options['filter']['type'] = array('=', $options['type']);
        }

        // filter   field => array(operator,value)
        //          Filters results based on the field, operator and value
        $COUNT = 0;
        if (isset($options['filter'])) {
            $COUNT++;
            $validFields = array('device_id','type','id','label','status','disabled','ignore','error');
            $SQL .= " ( ";
            foreach ($options['filter'] as $field => $array) {
                // Only add valid fields to the query
                if (in_array($field,$validFields)) {
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
            }
            // Strip the last " AND " before closing the bracket.
            $SQL = substr($SQL,0,-5)." )";
        }

        if ($COUNT == 0) {
            // Strip the " WHERE " that we didn't use.
            $SQL = substr($SQL,0,-7);
        }

        // sort     column direction
        //          Add SQL sorting to the results
        if (isset($options['sort'])) {
            $SQL .= " ORDER BY ".$options['sort'];
        }

        // Get our component records using our built SQL.
        $COMPONENTS = dbFetchRows($SQL, $PARAM);

        // if we have no components we need to return nothing
        if (count($COMPONENTS) == 0) {
            return $RESULT;
        }

        // Add the AVP's to the array.
        foreach ($COMPONENTS as $COMPONENT) {
            if ($COMPONENT['attribute'] != "") {
                // if this component has attributes, set them in the array.
                $RESULT[$COMPONENT['device_id']][$COMPONENT['id']][$COMPONENT['attribute']] = $COMPONENT['value'];
            }
        }

        // Populate our reserved fields into the Array, these cant be used as user attributes.
        foreach ($COMPONENTS as $COMPONENT) {
            foreach ($this->reserved as $k => $v) {
                $RESULT[$COMPONENT['device_id']][$COMPONENT['id']][$k] = $COMPONENT[$k];
            }

            // Sort each component array so the attributes are in order.
            ksort($RESULT[$RESULT[$COMPONENT['device_id']][$COMPONENT['id']]]);
            ksort($RESULT[$RESULT[$COMPONENT['device_id']]]);
        }

        // limit    array(start,count)
        if (isset($options['limit'])) {
            $TEMP = array();
            $COUNT = 0;
            // k = device_id, v = array of components for that device_id
            foreach ($RESULT as $k => $v) {
                // k1 = component id, v1 = component array
                foreach ($v as $k1 => $v1) {
                    if ( ($COUNT >= $options['limit'][0]) && ($COUNT < $options['limit'][0]+$options['limit'][1])) {
                        $TEMP[$k][$k1] = $v1;
                    }
                    // We are counting components.
                    $COUNT++;
                }
            }
            $RESULT = $TEMP;
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
        $ARRAY = array();
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
            if (!isset($OLD[$device_id][$COMPONENT])) {
                // Error. Component doesn't exist in the database.
                continue;
            }

            // Ignore type, we cant change that.
            unset($AVP['type'],$OLD[$device_id][$COMPONENT]['type']);

            // Process our reserved components first.
            $UPDATE = array();
            foreach ($this->reserved as $k => $v) {
                // does the reserved field exist, if not skip.
                if (isset($AVP[$k])) {

                    // Has the value changed?
                    if ($AVP[$k] != $OLD[$device_id][$COMPONENT][$k]) {
                        // The value has been modified, add it to our update array.
                        $UPDATE[$k] = $AVP[$k];
                    }

                    // Unset the reserved field. We don't want to insert it below.
                    unset($AVP[$k],$OLD[$device_id][$COMPONENT][$k]);
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

                if (!isset($OLD[$device_id][$COMPONENT][$ATTR])) {
                    // We have a newly added attribute, need to insert into the DB
                    $DATA = array('component'=>$COMPONENT, 'attribute'=>$ATTR, 'value'=>$VALUE);
                    dbInsert($DATA, 'component_prefs');

                    // Log the addition to the Eventlog.
                    log_event ("Component: " . $AVP[$COMPONENT]['type'] . "(" . $COMPONENT . "). Attribute: " . $ATTR . ", was added with value: " . $VALUE, $device_id, 'component', $COMPONENT);
                }
                elseif ($OLD[$device_id][$COMPONENT][$ATTR] != $VALUE) {
                    // Attribute exists but the value is different, need to update
                    $DATA = array('value'=>$VALUE);
                    dbUpdate($DATA, 'component_prefs', '`component` = ? AND `attribute` = ?', array($COMPONENT, $ATTR));

                    // Add the modification to the Eventlog.
                    log_event("Component: ".$AVP[$COMPONENT]['type']."(".$COMPONENT."). Attribute: ".$ATTR.", was modified from: ".$OLD[$COMPONENT][$ATTR].", to: ".$VALUE,$device_id,'component',$COMPONENT);
                }

            } // End Foreach COMPONENT

            // Process our Deletes.
            $DELETE = array_diff_key($OLD[$device_id][$COMPONENT], $AVP);
            foreach ($DELETE as $KEY => $VALUE) {
                // As the Attribute has been removed from the array, we should remove it from the database.
                dbDelete('component_prefs', "`component` = ? AND `attribute` = ?",array($COMPONENT,$KEY));

                // Log the addition to the Eventlog.
                log_event ("Component: " . $AVP[$COMPONENT]['type'] . "(" . $COMPONENT . "). Attribute: " . $KEY . ", was deleted.", $COMPONENT);
            }

        }

        return true;
    }

    // Write one or more statistics to the database for a component.
    public function setStatistic($device,$component,&$statistics) {
        global $config;

        /*
         * $component = id of component
         * $statistics = array(
         *      DS_NAME = array(
         *          type => gauge/counter/etc
         *          rrd => 'filename.rrd'
         *          value => statistic to write
         *      ),
         * );
         */

        // Turn the device ID into a hostname
        $hostname = gethostbyid($device);
        if ($hostname == "") {
            // No hostname was returned.
            d_echo("The device (".$device.") supplied is invalid.\n");
            return false;
        }

        /*
         *
         */
        if (is_array($statistics)) {
            if (count($statistics) == 0) {
                // No Statistics have been supplied
                d_echo("The Statistics array contains no items.\n");
                return false;
            }
            $rrd = array();
            foreach ($statistics as $name => $v) {
                // How about we sanity check the supplied data
                if (!isset($v['type'])) {
                    d_echo("Type is not set for the DS: ".$name.".\n");
                    return false;
                }
                if (!isset($v['rrd'])) {
                    d_echo("RRD is not set for the DS:".$name.".\n");
                    return false;
                }
                if (!isset($v['value'])) {
                    d_echo("Value is not set for the DS:".$name.".\n");
                    return false;
                }

                // Error checking done, let's build an RRD array.
                // Produces: array('filename.rrd'=>array(dsname=>type));
                $rrd[$v['rrd']][$name] = $v['type'];
            }
        }
        else {
            // Statistics is not an array.
            d_echo("Statistics is not an array.\n");
            return false;
        }

        // Let's print some debugging info.
        d_echo("\n\nComponent: ".$component."\n");
        d_echo("    Host:    ".$hostname."\n");

        foreach($rrd as $filename => $array) {
            $rrd_filename = $config['rrd_dir'] . "/" . $hostname . "/" . safename ($filename);

            // Here we build 2 vars, 1 in case we need to create the RRD, and 2 to write the data.
            $rrd_create = "";
            $rrd_data = array();
            foreach($array as $ds => $type) {
                // Make sure the counter type is correct.
                if (isset($this->counter_rrd[$type])) {
                    $type = $this->counter_rrd[$type];
                }
                else {
                    d_echo("Error: RRD Counter Type (".$type.") is not known.\n");
                    return false;
                }
                $rrd_create .= " DS:".$ds.":".$type.":600:0:U";
                $rrd_data[$ds] = $statistics[$ds]['value'];
                d_echo("    DS: ".$ds.", Value: ".$rrd_data[$ds].", RRD: ".$filename);
            }

            // Does this RRD's exist, or do we need to create it.
            if (!file_exists ($rrd_filename)) {
                // RRD doesn't exist, we need to create it.
                rrdtool_create ($rrd_filename, $rrd_create . $config['rrd_rra']);
                d_echo("RRD: ".$rrd_filename." has been created\n");
            }

            // Ok, now that the file exists, let's write some data.
            rrdtool_update ($rrd_filename, $rrd_data);

            // Fetch the last update
            $curr = rrdtool_lastupdate($rrd_filename);

            // Loop through each DS again, this time to retrieve averages from the RRD.
            foreach($array as $ds => $type) {
                $rrd_options  = '--start end-1d --step 60 DEF:raw='.$rrd_filename.':'.$ds.':LAST ';
                $rrd_options .= 'CDEF:15m=raw,900,TRENDNAN XPORT:15m ';
                $rrd_options .= 'CDEF:1h=raw,3600,TRENDNAN XPORT:1h ';
                $rrd_options .= 'CDEF:1d=raw,86400,TRENDNAN XPORT:1d ';
                $rrd_options .= 'XPORT:raw ';
                $json = rrdtool_xport1($rrd_options);

                if ($json === false) {
                    // bad JSON.
                    return false;
                }

                // Add the latest result to the array.
                $statistics[$ds]['stats']['curr'] = $curr[$ds];

                foreach($json['data'] as $v) {
                    // Is 15 Min populated.
                    if(!is_null($v[0])) {
                        $statistics[$ds]['stats']['15m'] = $v[0];
                    }
                    // Is 1 Hour populated.
                    if(!is_null($v[1])) {
                        $statistics[$ds]['stats']['1h'] = $v[1];
                    }
                    // Is 24 Hour populated.
                    if(!is_null($v[2])) {
                        $statistics[$ds]['stats']['1d'] = $v[2];
                    }
                    // Is Current populated.
                    if(!is_null($v[3])) {
                        $statistics[$ds]['stats']['rrdcurr'] = $v[3];
                    }
                }
            }

        }

        // We should write these statistics to the database.
        foreach($statistics as $ds => $v) {
            // extract current from the last write
            $current = $this->getStatistic($component,$ds,$v['rrd']);

            $row = array();
            $row['last'] = $current[0]['current'];
            $row['current'] = $v['stats']['curr'];
            $row['15min'] = $v['stats']['15m'];
            $row['1hour'] = $v['stats']['1h'];
            $row['1day'] = $v['stats']['1d'];
            $row['updated'] = time();

            $result = dbUpdate($row, 'component_datasource', '`component` = ? AND `ds` = ? AND `rrd` = ?', array($component,$ds,$v['rrd']));
            if ($result == 0) {
                // No record to update, let's insert.
                $row['rrd'] = $v['rrd'];
                $row['last'] = 0;
                $row['component'] = $component;
                $row['ds'] = $ds;
                dbInsert($row, 'component_datasource');
            }
        }

        // Run the component threshold rules engine.

        return true;
    }

    public function getStatistic($component=null, $ds=null, $rrd=null, $filter_sql=null) {
        $SQL = "SELECT `current`,`last`,`15min`,`1hour`,`1day` FROM `component_datasource` WHERE ";
        $filter = 0;
        $param = array();

        if (!is_null($filter_sql)) {
            // If SQL is supplied it overrides filters.
            $SQL .= $filter_sql;
        }
        else {
            // Do we need a component filter?
            if (!is_null($component)) {
                $SQL .= "`component` = ? AND ";
                $param[] = $component;
                $filter++;
            }

            // Do we need a ds filter?
            if (!is_null($ds)) {
                $SQL .= "`ds` = ? AND ";
                $param[] = $ds;
                $filter++;
            }

            // Do we need a ds filter?
            if (!is_null($rrd)) {
                $SQL .= "`rrd` = ? AND ";
                $param[] = $rrd;
                $filter++;
            }

            if ($filter == 0) {
                // No filters, remove " WHERE "
                $SQL = substr($SQL, 0, strlen($SQL)-7);
            }
            else {
                // Filters, remove " AND "
                $SQL = substr($SQL, 0, strlen($SQL)-5);
            }
        }
        $result = dbFetchRows($SQL, $param);
        return $result;
    }

    private function processThreshold() {

    }

    public function addThreshold() {

    }

    public function delThreshold() {

    }
}