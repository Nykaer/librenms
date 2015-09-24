<?php
/*
 * LibreNMS module to interface to external HTTP(s) services.
 *
 * Copyright (c) 2015 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

class transport_http
{
    function do_post_request($url, $data, $optional_headers = null)
    {
        $params = array(
            'http'  => array(
                'method'    => 'POST',
                'content'   => $data,
                'timeout'   => 5000,
            ),
            'ssl'   => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            )
        );

        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            throw new HTTPException("Unable to establish http connection");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new HTTPException("Problem reading data from host");
        }
        return $response;
    }

    function do_get_request($url, $optional_headers = null)
    {
        $params = array(
            'http'  => array(
                'method'    => 'POST',
                'content'   => $data,
                'timeout'   => 5000,
            ),
            'ssl'   => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            )
        );

        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'r', false, $ctx);
        if (!$fp) {
            throw new HTTPException("Problem with $url, $php_errormsg");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new HTTPException("Problem reading data from $url, $php_errormsg");
        }
        return $response;
    }

}

class HTTPException extends \Exception {}
