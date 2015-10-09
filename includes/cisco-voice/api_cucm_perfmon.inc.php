<?php
/*
 * LibreNMS API to Cisco CallManager components via SOAP/AXL.
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

class api_cucm_perfmon extends \transport_http {
	private $sURL           = array();
    private $options        = array('nosslcheck'=>true);
    private $SID            = NULL;

    // TODO: Common - move to transport_http
    private function is_sequential( array $ARRAY ) {
        $ISSEQ = TRUE;
        for (reset($ARRAY); is_int(key($ARRAY)); next($ARRAY)) {
            $ISSEQ = is_null(key($ARRAY));
        }
        return $ISSEQ;
    }

    /**
     * @param $XMLIN - XML to send in a CUCM SOAP request
     * @return array (True/False, DATA)
     * @throws HTTPException
     * Sends a SOAP request to the CUCM AXL interface.
     */
    private function request($XMLIN) {
        // Loop over the URL array, try each one in order.
        $HTTPSTATUS = FALSE;
        $MSG = "No hosts supplied to connect to";
        foreach ($this->sURL as $URL) {
            try {
                $this->options['method'] = 'POST';
                $this->options['content'] = $XMLIN;
                $HTTPRES = $this->http_request($URL,$this->options);
                $HTTPSTATUS = TRUE;
                break;
            }
            catch (\HTTPException $e) {
                $MSG = $e->getMessage();
                d_echo("HTTP Failed Attempt to: ".$URL." with error: " .$MSG."\n");
            }
        }
        if ($HTTPSTATUS === FALSE) {
            d_echo("HTTP Connection failed to all hosts.\n");
            return array(false, "HTTP Error: " .$MSG);
        }

        if ($HTTPRES['http_code'] == 401) {
            d_echo("401 - Incorrect Credentials Supplied.\n");
            return array(false, "401 - Incorrect Credentials Supplied.");
        }

        // If we got this far we have a SOAP result.
        d_echo("HTTP Response received: " .$HTTPRES['http_code']."\n");
        $RESPONSE = $HTTPRES['content'];

        // Remove excess whitespace from the response.
        $RESPONSE = preg_replace('/\s\s/s', '', $RESPONSE);

        // Remove Namespaces from the response.
        $RESPONSE = preg_replace('/(ns1:|soapenv:)/s', '', $RESPONSE);
        d_echo("Namespaces Removed: " .print_r($RESPONSE, TRUE)."\n");

        // Convert XML to array
        $RESULT = json_decode(json_encode((array) simplexml_load_string($RESPONSE)),1);

        // Do we have an error
        if ($HTTPRES['http_code'] != 200) {
            // Yes, have some kind of fault.
            if (isset($RESULT['Body']['Fault'])) {
                $MSG = $RESULT['Body']['Fault']['faultcode'] . " - " . $RESULT['Body']['Fault']['faultstring'];
            }
            else {
                $MSG = $HTTPRES['content'];
            }
            d_echo("Fault: ".$MSG."\n");
            return array(false, "Fault: " .$MSG);
        }
        else {
            // No fault, must be successful.
            d_echo("Successful Response: ".print_r($RESULT['Body'],true)."\n");
            return array(true, $RESULT['Body']);
        }
    }

    private function getSID() {
        if (is_null($this->SID)) {
            // SID is not set, openSession will get us one.
            $this->SID = $this->openSession();
        }
        // We have a SID, return it.
        return $this->SID;
    }

    private function openSession() {
        // Get a new SID from CUCM Perfmon.
        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="http://schemas.cisco.com/ast/soap">
    <soapenv:Header/>
    <soapenv:Body>
       <soap:perfmonOpenSession/>
    </soapenv:Body>
</soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, return the response.
            return $RESULT[1]['perfmonOpenSessionResponse']['perfmonOpenSessionReturn'];
        }
    }

    public function closeSession() {
        if (is_null($this->SID)) {
            d_echo("There is no active session to close\n");
            return false;
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="http://schemas.cisco.com/ast/soap">
    <soapenv:Header/>
    <soapenv:Body>
       <soap:perfmonCloseSession>
          <soap:SessionHandle>'.$this->getSID().'</soap:SessionHandle>
       </soap:perfmonCloseSession>
    </soapenv:Body>
 </soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, cleanup and return.
            $this->SID = null;
            return true;
        }
    }

    public function connect($user,$pass,$hosts) {
        // TODO: Error checking on user/pass/host
        foreach ($hosts as $host) {
            $this->sURL[] = "https://" .$host. ":8443/perfmonservice2/services/PerfmonService";
        }
        $this->options['headers'][] = "Authorization: Basic ".base64_encode($user.":".$pass);
        $this->options['headers'][] = "Content-Type: text/xml";
        return true;
    }

    public function listCounter($HOST=null) {
        if (is_null($HOST)) {
            $HOST = "";
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="http://schemas.cisco.com/ast/soap">
    <soapenv:Header/>
    <soapenv:Body>
       <soap:perfmonListCounter>
          <soap:Host>'.$HOST.'</soap:Host>
       </soap:perfmonListCounter>
    </soapenv:Body>
</soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, return the result.
            return $RESULT[1]['perfmonListCounterResponse']['perfmonListCounterReturn'];
        }
    }

    public function listInstance($HOST=false,$OBJECT=false) {
        // We cant continue without parameters.
        if ((!$HOST) || (!$OBJECT)) {
            return false;
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="http://schemas.cisco.com/ast/soap">
    <soapenv:Header/>
    <soapenv:Body>
       <soap:perfmonListInstance>
          <soap:Host>'.$HOST.'</soap:Host>
          <soap:Object>'.$OBJECT.'</soap:Object>
       </soap:perfmonListInstance>
    </soapenv:Body>
</soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, return the result.
            return $RESULT[1]['perfmonListInstanceResponse']['perfmonListInstanceReturn'];
        }
    }

    public function addCounter($ARRAY=array()) {
        // Some error checking..
        if (is_null($this->getSID())) {
            d_echo("Could not get a SID\n");
            return false;
        }
        if (count($ARRAY) == 0) {
            d_echo("No Counters were supplied to be added\n");
            return false;
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="http://schemas.cisco.com/ast/soap">
    <soapenv:Header/>
    <soapenv:Body>
       <soap:perfmonAddCounter>
          <soap:SessionHandle>'.$this->getSID().'</soap:SessionHandle>
          <soap:ArrayOfCounter>
';
        foreach ($ARRAY as $COUNTER) {
            $XML .= '             <soap:Counter>
                <soap:Name>'.$COUNTER.'</soap:Name>
             </soap:Counter>'."\n";
        }
        $XML .= '          </soap:ArrayOfCounter>
       </soap:perfmonAddCounter>
    </soapenv:Body>
</soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, return true.
            return true;
        }
    }

    public function removeCounter($ARRAY=array()) {
        // Some error checking..
        if (is_null($this->getSID())) {
            d_echo("Could not get a SID\n");
            return false;
        }
        if (count($ARRAY) == 0) {
            d_echo("No Counters were supplied to be added\n");
            return false;
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="http://schemas.cisco.com/ast/soap">
    <soapenv:Header/>
    <soapenv:Body>
       <soap:perfmonRemoveCounter>
          <soap:SessionHandle>'.$this->getSID().'</soap:SessionHandle>
          <soap:ArrayOfCounter>
';
        foreach ($ARRAY as $COUNTER) {
            $XML .= '             <soap:Counter>
                <soap:Name>'.$COUNTER.'</soap:Name>
             </soap:Counter>'."\n";
        }
        $XML .= '          </soap:ArrayOfCounter>
       </soap:perfmonRemoveCounter>
    </soapenv:Body>
</soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, return true
            return true;
        }
    }

    public function collectSessionData() {
        // Some error checking..
        if (is_null($this->getSID())) {
            d_echo("Could not get a SID\n");
            return false;
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:soap="http://schemas.cisco.com/ast/soap">
   <soapenv:Header/>
   <soapenv:Body>
      <soap:perfmonCollectSessionData>
         <soap:SessionHandle>'.$this->getSID().'</soap:SessionHandle>
      </soap:perfmonCollectSessionData>
   </soapenv:Body>
</soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, return the result.
            $RETURN = array();
            if ($this->is_sequential($RESULT[1]['perfmonCollectSessionDataResponse']['perfmonCollectSessionDataReturn'])) {
                d_echo("NOT Associative Array, make it one: " .print_r($RESULT[1]['perfmonCollectSessionDataResponse']['perfmonCollectSessionDataReturn'], TRUE)."\n");
                $RETURN[] = $RESULT[1]['perfmonCollectSessionDataResponse']['perfmonCollectSessionDataReturn'];
            }
            else {
                d_echo("IS Associative Array: " .print_r($RESULT[1]['perfmonCollectSessionDataResponse']['perfmonCollectSessionDataReturn'], TRUE)."\n");
                $RETURN = $RESULT[1]['perfmonCollectSessionDataResponse']['perfmonCollectSessionDataReturn'];
            }

            return $RETURN;
        }
    }

    public function collectCounterData($HOST=false,$OBJECT=false) {
        // We cant continue without parameters.
        if ((!$HOST) || (!$OBJECT)) {
            return false;
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="http://schemas.cisco.com/ast/soap">
    <soapenv:Header/>
    <soapenv:Body>
       <soap:perfmonCollectCounterData>
          <soap:Host>'.$HOST.'</soap:Host>
          <soap:Object>'.$OBJECT.'</soap:Object>
       </soap:perfmonCollectCounterData>
    </soapenv:Body>
</soapenv:Envelope>
';

        $RESULT = $this->request($XML);
        if ($RESULT[0] === false) {
            // Check to see if we have an error, if we do, return false.
            return false;
        }
        else {
            // No error, return the result.
            return $RESULT[1]['perfmonCollectCounterDataResponse']['perfmonCollectCounterDataReturn'];
        }
    }

    public function addHeader($string) {
        $this->aHTTPHeader[] = $string;
        return true;
    }

    public function getRRDValue($DATA,$COUNTER=null) {
        $RESULT = "U";      // Guilty until proven innocent.

        if (!is_null($COUNTER)) {
            // Yes, we have a counter.
            if (isset($DATA[$COUNTER])) {
                // the counter even exists.
                if (($DATA[$COUNTER]['CStatus'] == 0) || ($DATA[$COUNTER]['CStatus'] == 1)) {
                    // its valid too, we are on a winner...
                    $RESULT = $DATA[$COUNTER]['Value'];
                }
            }
        }
        return $RESULT;
    }

}