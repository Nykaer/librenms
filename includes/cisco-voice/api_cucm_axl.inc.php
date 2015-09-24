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

class api_cucm_axl extends \transport_http
{
	var $sURL			= array();
	var $sAuthHeader	= NULL;
    var $aHTTPHeader    = array();

    public function connect($user,$pass,$hosts)
    {
        // TODO: Error checking on user/pass/host
        foreach ($hosts as $host)
        {
            $this->sURL[] = "https://" .$host. ":8443/axl/";
        }
        $this->aHTTPHeader[] = "Authorization: Basic ".base64_encode($user.":".$pass);
        $this->aHTTPHeader[] = "Content-Type: text/xml";
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
    private function getCUCMData($XMLIN)
    {
        d_echo("XMLIN set to: " .$XMLIN."\n");

        // Loop over the URL array, try each one in order.
        $HTTPSTATUS = FALSE;
        $MSG = "No hosts supplied to connect to";
        foreach ($this->sURL as $URL)
        {
            try
            {
                $RESPONSE = $this->do_post_request($URL,$XMLIN,$this->aHTTPHeader);
                $HTTPSTATUS = TRUE;
                break;
            }
            catch (\HTTPException $e)
            {
                $MSG = $e->getMessage();
                d_echo("HTTP Failed Attempt to: ".$URL." with error: " .$MSG."\n");
            }
        }
        if ($HTTPSTATUS === FALSE)
        {
            d_echo("HTTP Connection failed to all hosts.\n");
            return array(1, "HTTP Error: " .$MSG);
        }

        // If we got this far we have a SOAP result.
        d_echo("Response received: " .$RESPONSE."\n");

        // Remove excess whitespace from the response.
        $RESPONSE = preg_replace('/\s\s/s', '', $RESPONSE);
        d_echo("Removed Excess Whitespace: " .print_r($RESPONSE, TRUE)."\n");

        // Turn blank XML tags into empty XML tags (<emptytag/> into <emptytag></emptytag>.
        $RESPONSE = preg_replace("/<(\w+)\/>/", "<$1>null</$1>", $RESPONSE);
        d_echo("Tag Fixup: " .print_r($RESPONSE, TRUE)."\n");

        // return is a non-error response, do we have one?
        $RESULT = preg_match('(<return>.*?</return>)',$RESPONSE, $EXTRACT);
        if ($RESULT !== 0)
        {
            // Yes, we have a non-error response.
            $OUT = $EXTRACT[0];
            // is the response empty (no results)?
            if ($OUT == "<return>null</return>")
            {
                // Yes.
                d_echo("No data was received in the fault response\n");
                return array(1, "There are no results matching the search query.");
            }
            // No, we have a non-error response with some results.
            d_echo("Successful Response\n");
        } else {
            // No, We dont have a non-error response.
            // Do re have an error response?
            $RESULT = preg_match('(<faultstring>.*?</faultstring>)',$RESPONSE, $EXTRACT);
            if ($RESULT !== 0)
            {
                // Yes, we have a faultstring.
                d_echo("Fault Response\n");
                $XMLOUT = new \SimpleXMLElement($EXTRACT[0]);
                $MSG = $XMLOUT[0];
                d_echo("XML Error: " .$MSG."\n");
                return array(1, "XML Error: " .$MSG);
            } else {
                // No, we dont have a faultstring.
                // No return and no faultstring, I dont know what to do?
                d_echo("The response did not contain a return or a faultstring: ".print_r($RESPONSE, TRUE)."\n");
                return array(1, "An unknown response was returned by the server.");
            }

        }
        // If we got this far, we have a valid non-error response.
        d_echo("Extracted Response: " .$OUT."\n");
        $ARROUT = json_decode(json_encode((array) simplexml_load_string($OUT)),1);
        d_echo("Turned into array: " .print_r($ARROUT, TRUE)."\n");

        // All done, return the successful response.
        return array(0, $ARROUT);
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

        $RESULT = $this->getCUCMData($XML);

        // Check to see if we have an error, if we do, return it.
        if ($RESULT[0] == 1)
        {
            return $RESULT;
        }

        /*
         * If a query only returns a single item CUCM does not put it in an array.
         * This is annoying and makes it hard to process the data in a consistent way.
         * Here we check if the data is an array, and if not we make it one.
         */
        if ($this->is_sequential($RESULT[1]['row']))
        {
            d_echo("NOT Associative Array, make it one: " .print_r($RESULT[1]['row'], TRUE)."\n");
            $RETURN[] = $RESULT[1]['row'];
        } else {
            d_echo("IS Associative Array: " .print_r($RESULT[1]['row'], TRUE)."\n");
            $RETURN = $RESULT[1]['row'];
        }

        // All done, return the successful response.
        return array(0, $RETURN);
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

        return $this->getCUCMData($XML);
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

        return $this->getCUCMData($XML);
    }

	private function is_sequential( array $ARRAY )
	{
		$ISSEQ = TRUE;
		for (reset($ARRAY); is_int(key($ARRAY)); next($ARRAY))
		{
			$ISSEQ = is_null(key($ARRAY));
		}
		return $ISSEQ;
	}

}