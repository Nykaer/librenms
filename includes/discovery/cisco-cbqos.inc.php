<?php

if ($device['os_group'] == 'cisco') {

    /**
     * Indexed_SNMP - returns an array in the format array[OID][Index] = value from a string input of snmpwalk results.
     * @param $string
     * @return array
     */
    function indexed_snmp($string) {
        $array = array();
        // Let's turn the result into something we can work with.
        foreach (explode("\n", $string) as $line) {
            if ($line[0] == '.') {
                // strip the leading . if it exists.
                $line = substr($line,1);
            }
            list($key, $value) = explode(' ', $line, 2);

            $prop_id = explode('.', $key);

            // Grab the last value as our index.
            $index = $prop_id[count($prop_id)-1];

            // Pop the index off when we re-build our key.
            array_pop($prop_id);
            $key = implode('.',$prop_id);

            $array[$key][$index] = trim($value);
        }
        return $array;
    }
    /**
     * Dual_Indexed_SNMP - returns an array in the format array[OID][Index] = value from a string input of snmpwalk results.
     * @param $string
     * @return array
     */
    function dual_indexed_snmp($string) {
        $array = array();
        // Let's turn the result into something we can work with.
        foreach (explode("\n", $string) as $line) {
            if ($line[0] == '.') {
                // strip the leading . if it exists.
                $line = substr($line,1);
            }
            list($key, $value) = explode(' ', $line, 2);

            $prop_id = explode('.', $key);

            // Grab the last values as our indexes.
            $index1 = $prop_id[count($prop_id)-2];
            $index2 = $prop_id[count($prop_id)-1];

            // Pop the index off when we re-build our key.
            array_pop($prop_id);
            array_pop($prop_id);
            $key = implode('.',$prop_id);

            $array[$key][$index1][$index2] = trim($value);
        }
        return $array;
    }

    echo 'Class-Based QOS : ';

    require 'includes/component.php';
    $COMPONENT = new component();
    $COMPONENTS = $COMPONENT->getComponents($device['id'],array('type'=>'Cisco-CBQOS'));

//    $tblcbQosServicePolicy = indexed_snmp(snmp_walk($device, '.1.3.6.1.4.1.9.9.166.1.1', '-Osqn'));
    // Begin our master array, all other values will be processed into this array.
    $tblCBQOS = array();

    $tblcbQosObjects = dual_indexed_snmp(snmp_walk($device, '.1.3.6.1.4.1.9.9.166.1.5', '-Osqn'));

    $tblcbQosPolicyMapCfg = dual_indexed_snmp(snmp_walk($device, '.1.3.6.1.4.1.9.9.166.1.6', '-Osqn'));
    $tblcbQosClassMapCfg = dual_indexed_snmp(snmp_walk($device, '.1.3.6.1.4.1.9.9.166.1.7', '-Osqn'));
    $tblcbQosMatchStmtCfg = dual_indexed_snmp(snmp_walk($device, '.1.3.6.1.4.1.9.9.166.1.8', '-Osqn'));
    $tblcbQosQueueingCfg = dual_indexed_snmp(snmp_walk($device, '.1.3.6.1.4.1.9.9.166.1.9', '-Osqn'));

    // populate our indexes into the destination array.
//    foreach ($tblcbQosServicePolicy['1.3.6.1.4.1.9.9.166.1.1.1.1.2'] as $key => $value) {
//        $tblCBQOS[$key] = array();
//    }

    // now we have our indexes, lets start building the rest of our data.
/*    foreach ($tblCBQOS as $key => $value) {
        $tblCBQOS[$key]['ifindex'] = $tblcbQosServicePolicy['1.3.6.1.4.1.9.9.166.1.1.1.1.4'][$key];
        if ($tblcbQosServicePolicy['1.3.6.1.4.1.9.9.166.1.1.1.1.3'][$key] == 1) {
            $tblCBQOS[$key]['direction'] = 'in';
        }
        else {
            $tblCBQOS[$key]['direction'] = 'out';
        }
    }
*/

    print_r($tblCBQOS);

    echo "\n";
}
