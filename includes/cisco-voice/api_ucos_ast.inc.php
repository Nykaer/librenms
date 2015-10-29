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

class api_ucos_ast extends \transport_http {
    private $sURL           = "";
    private $sHOST          = "";
    private $options        = array('nosslcheck'=>true);

    public function connect($user,$pass,$host)
    {
        // TODO: Error checking on user/pass/host
        $this->sHOST = $host;
        $this->options['headers'][] = "Authorization: Basic ".base64_encode($user.":".$pass);
        $this->options['headers'][] = "Content-Type: text/xml";
        return true;
    }

    /**
     * @param $DATA - DATA to send
     * @return array (True/False, DATA)
     * @throws HTTPException
     * Sends a SOAP request to the UCOS ast interface.
     */
    private function request($DATA) {
        // Loop over the URL array, try each one in order.
        $HTTPSTATUS = FALSE;
        $MSG = "No hosts supplied to connect to";
        $URL = $this->sURL;
        try {
            $this->options['method'] = 'POST';
            $this->options['content'] = $DATA;
            $HTTPRES = $this->http_request($URL,$this->options);
            $HTTPSTATUS = TRUE;
        }
        catch (\HTTPException $e) {
            $MSG = $e->getMessage();
            d_echo("HTTP Failed Attempt to: ".$URL." with error: " .$MSG."\n");
        }
        if ($HTTPSTATUS === FALSE) {
            d_echo("HTTP Connection failed to host.\n");
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

        // We don't know the contents of the response
        // Lets just hand it back and let the calling function deal with it.
        return array($HTTPRES['http_code'], $RESULT);
    }

    public function getProduct()
    {
        $this->sURL = "https://".$this->sHOST.":8443/ast/ASTisapi.dll?GetProductDeployed";
        d_echo("URL Set to: " .$this->sURL."\n");

        $RESULT = $this->request(null);

        if ($RESULT[0] == 200) {
            // HTTP - 200 OK
            return $RESULT[1]["@attributes"];
        }
        else {
            return false;
        }
    }

}