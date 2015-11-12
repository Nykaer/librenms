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

class api_cucm_axl extends \transport_http {
    private $sURL           = array();
    private $options        = array('nosslcheck'=>true);
//    var $aHTTPHeader    = array();

    public function connect($user,$pass,$hosts)
    {
        // TODO: Error checking on user/pass/host
        foreach ($hosts as $host)
        {
            $this->sURL[] = "https://" .$host. ":8443/axl/";
        }
        $this->options['headers'][] = "Authorization: Basic ".base64_encode($user.":".$pass);
        $this->options['headers'][] = "Content-Type: text/xml";
        return true;
    }

    public function addHeader($string)
    {
        $this->aHTTPHeader[] = $string;
        return true;
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
        $RESPONSE = preg_replace('/(ns:|soapenv:)/s', '', $RESPONSE);
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

    public function runSQLQuery($SQL)
    {
        d_echo("SQL Query: " .$SQL."\n");

        $XML = '<?xml version="1.0" encoding="UTF-8"?>';
        $XML .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">';
        $XML .= '    <SOAP-ENV:Body>';
        $XML .= '        <axlapi:executeSQLQuery xmlns:axlapi="http://www.cisco.com/AXL/API/1.0" xmlns:axl="http://www.cisco.com/AXL/1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" sequence="1" xsi:schemaLocation="http://www.cisco.com/AXL/API/1.0 axlsoap.xsd">';
        $XML .= '            <sql>'.$SQL.'</sql>';
        $XML .= '        </axlapi:executeSQLQuery>';
        $XML .= '	    </SOAP-ENV:Body>';
        $XML .= '</SOAP-ENV:Envelope>';

        $RESULT = $this->request($XML);

        // Check to see if we have an error, if we do, return it.
        if ($RESULT[0] === false)
        {
            return $RESULT;
        }

        /*
         * If a query only returns a single item CUCM does not put it in an array.
         * This is annoying and makes it hard to process the data in a consistent way.
         * Here we check if the data is an array, and if not we make it one.
         */
        $RETURN = $this->make_sequential($RESULT[1]['executeSQLQueryResponse']['return']['row']);

        // All done, return the successful response.
        return array(true, $RETURN);
    }

    public function getEndUser($username)
	{
		$XML = '<?xml version="1.0" encoding="UTF-8"?>';
        $XML .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">';
        $XML .= '    <SOAP-ENV:Body>';
        $XML .= '        <axlapi:getUser xmlns:axlapi="http://www.cisco.com/AXL/API/1.0" xmlns:axl="http://www.cisco.com/AXL/1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" sequence="1" xsi:schemaLocation="http://www.cisco.com/AXL/API/1.0 axlsoap.xsd">';
        $XML .= '            <userid>'.$username.'</userid>';
        $XML .= '        </axlapi:getUser>';
        $XML .= '    </SOAP-ENV:Body>';
        $XML .= '</SOAP-ENV:Envelope>';

        $RESULT = $this->request($XML);

        // Check to see if we have an error, if we do, return it.
        if ($RESULT[0] === false)
        {
            return $RESULT;
        }

        // All done, return the successful response.
        return array(true, $RESULT[1]['getUserResponse']['return']['user']);
	}

    public function setDeviceOwner($device='',$user=null)
    {
        if ($device == null) {
            // No device specified.
            d_echo("No device supplied to set owner on.\n");
            return array(1, "No device supplied.");
        }

        $XML = '<?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.cisco.com/AXL/API/9.1">
               <soapenv:Header/>
               <soapenv:Body>
                  <ns:updatePhone sequence="0">
                     <name>'.$device.'</name>
                     <ownerUserName>'.$user.'</ownerUserName>
                  </ns:updatePhone>
               </soapenv:Body>
            </soapenv:Envelope>';

        $RESULT = $this->request($XML);

        // Check to see if we have an error, if we do, return it.
        if ($RESULT[0] === false)
        {
            return $RESULT;
        }

        /*
         * If a query only returns a single item CUCM does not put it in an array.
         * This is annoying and makes it hard to process the data in a consistent way.
         * Here we check if the data is an array, and if not we make it one.
         */
        $RETURN = $this->make_sequential($RESULT[1]['updatePhoneResponse']['return']);

        // All done, return the successful response.
        return array(true, $RETURN);
    }

}