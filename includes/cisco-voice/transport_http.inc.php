<?php
/**
 * Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *	* Redistributions of source code must retain the above copyright
 *	  notice, this list of conditions and the following disclaimer.
 *
 *	* Redistributions in binary form must reproduce the above
 *	  copyright notice, this list of conditions and the following
 *	  disclaimer in the documentation and/or other materials provided
 *	  with the distribution.
 *
 *	* Neither the names of David R. Nadeau or NadeauSoftware.com, nor
 *	  the names of its contributors may be used to endorse or promote
 *	  products derived from this software without specific prior
 *	  written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
 * OF SUCH DAMAGE.
 */

/*
 * This is a BSD License approved by the Open Source Initiative (OSI).
 * See:  http://www.opensource.org/licenses/bsd-license.php
 */


/**
 * Get a file on the web.  The file may be an (X)HTML page, an image, etc.
 * Return an associative array containing the page header, contents, and
 * HTTP status code.
 *
 * Values in the returned array include:
 *
 * 	"header"	the HTTP response header
 * 	"http_code"	the last error/status code
 * 	"content"	the page content (text, image, etc.)
 *
 * On success, "http_code" is 200 and "content" has the web page.
 *
 * On an error with the URL, such as a redirect limit, or timeout, null
 * is returned.
 *
 * On an error with the web site, such as a missing page, no permissions,
 * or no service, "http_code" will be the HTTP error code, and "content"
 * will be missing.
 *
 * Parameters:
 * 	url		the URL of the page to get
 *
 * Return values:
 * 	the page text or the HTTP error code.
 *
 * See also:
 * 	http://nadeausoftware.com/articles/2007/07/php_tip_how_get_web_page_using_fopen_wrappers
 *
 * Lightly Modified by Aaron Daniels - aaron@daniels.id.au
 */
class transport_http {
    function http_request ($url, $options = array ()) {
        if (!isset($options['timeout'])) {
            // if unset, set the timeout to 5 seconds.
            $options['timeout'] = 5;
        }
        $params['http'] = array (
            'timeout' => $options['timeout'],
            'ignore_errors' => 1,       // Important so that the fault response is not suppressed.
        );
        // Make sure there is a method set.
        if (!isset($options['method'])) {
            $options['method'] = 'GET';
        }
        $params['http']['method'] = $options['method'];

        // if the method is post we need to add the content.
        if ($options['method'] == "POST") {
            // Make sure there is content set.
            if (!isset($options['content'])) {
                $options['content'] = '';
            }
            $params['http']['content'] = $options['content'];
        }
        if (isset($options['nosslcheck'])) {
            // Disable SSL checks
            $params['ssl'] = array (
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            );
        }
        if (isset($options['headers'])) {
            $params['http']['header'] = $options['headers'];
        }

        // Print the configured options
        d_echo ("HTTP Options: " . print_r ($params, TRUE));

        $context = stream_context_create ($params);
        $page = @file_get_contents ($url, false, $context);

        $result = array ();
        if ($page != false) {
            $result['content'] = $page;
        } else if (!isset($http_response_header)) {
            throw new HTTPException("Unable to connect to host");
        }
        // Save the header
        $result['header'] = $http_response_header;

        // Get the *last* HTTP status code
        $nLines = count ($http_response_header);
        for ($i = $nLines - 1; $i >= 0; $i--) {
            $line = $http_response_header[$i];
            if (strncasecmp ("HTTP", $line, 4) == 0) {
                $response = explode (' ', $line);
                $result['http_code'] = $response[1];
                break;
            }
        }
        return $result;
    }

    public function is_sequential( array $ARRAY ) {
        $ISSEQ = TRUE;
        for (reset($ARRAY); is_int(key($ARRAY)); next($ARRAY)) {
            $ISSEQ = is_null(key($ARRAY));
        }
        return $ISSEQ;
    }

    public function make_sequential( array $ARRAY ) {
        if ($this->is_sequential($ARRAY))
        {
            d_echo("NOT Associative Array, make it one\n");
            $RETURN[] = $ARRAY;
        } else {
            d_echo("IS Associative Array\n");
            $RETURN = $ARRAY;
        }
        // All done, return the successful response.
        return $RETURN;
    }

}

class HTTPException extends \Exception {}
