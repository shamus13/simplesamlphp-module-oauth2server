<?php

/*
*    simpleSAMLphp-oauth2server is an OAuth 2.0 authorization and resource server in the form of a simpleSAMLphp module
*
*    Copyright (C) 2014  Bjorn R. Jensen
*
*    This library is free software; you can redistribute it and/or
*    modify it under the terms of the GNU Lesser General Public
*    License as published by the Free Software Foundation; either
*    version 2.1 of the License, or (at your option) any later version.
*
*    This library is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
*    Lesser General Public License for more details.
*
*    You should have received a copy of the GNU Lesser General Public
*    License along with this library; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*
*/

class sspmod_oauth2server_Utility_Uri
{

    public static function addQueryParametersToUrl($url, $response)
    {
        $fragmentStart = strpos($url, '#');

        if ($fragmentStart !== false) { //strip fragment if any
            $fragment = substr($url, $fragmentStart);
            $url = substr($url, 0, $fragmentStart);
        } else {
            $fragment = '';
        }

        $queryStart = strpos($url, '?');

        if ($queryStart !== false) { //strip query if any
            $query = \SimpleSAML\Utils\HTTP::parseQueryString(substr($url, $queryStart + 1));
            $url = substr($url, 0, $queryStart);
        } else {
            $query = array();
        }

        $query = array_merge($query, $response);
        $url .= '?' . http_build_query($query, '', '&') . $fragment;
        return $url;
    }

    public static function redirectUri($url)
    {
        /* Set the HTTP result code. This is either 303 See Other or
             * 302 Found. HTTP 303 See Other is sent if the HTTP version
             * is HTTP/1.1 and the request type was a POST request.
             */
        if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
            $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            $code = 303;
        } else {
            $code = 302;
        }

        if (strlen($url) > 2048) {
            SimpleSAML_Logger::warning('Redirecting to a URL longer than 2048 bytes.');
        }

        /* Set the location header. */
        header('Location: ' . $url, true, $code);

        /* Disable caching of this response. */
        header('Pragma: no-cache');
        header('Cache-Control: no-cache, must-revalidate');

        /* Show a minimal web page with a clickable link to the URL. */
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' .
            ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>
      					<meta http-equiv="content-type" content="text/html; charset=utf-8">
      					<title>Redirect</title>
      				</head>';
        echo '<body>';
        echo '<h1>Redirect</h1>';
        echo '<p>';
        echo 'You were redirected to: ';
        echo '<a id="redirlink" href="' .
            htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
        echo '<script type="text/javascript">document.getElementById("redirlink").focus();</script>';
        echo '</p>';
        echo '</body>';
        echo '</html>';

        /* End script execution. */
        exit;
    }

    /**
     * @param $client
     * @return array
     */
    public static function calculateScopingParameters(array $client)
    {
        $params = array();

        if (array_key_exists('IDPList', $client)) {
            if (sizeof($client['IDPList']) > 1) {
                $params['saml:IDPList'] = $client['IDPList'];

                return $params;
            } else {
                if (sizeof($client['IDPList']) === 1) {
                    $params['saml:idp'] = $client['IDPList'][0];

                    return $params;
                }

                return $params;
            }
        }

        return $params;
    }

    /**
     * @param $returnUri
     * @param $client
     * @return bool
     */
    public static function validateRedirectUri($returnUri, $client)
    {
        $legalRedirectUri = false;

        $parsedUri = parse_url($returnUri);

        if (is_array($parsedUri) && ($parsedUri['scheme'] == 'intent' ||
                (($parsedUri['scheme'] == 'http' || $parsedUri['scheme'] == 'https') &&
                    !array_key_exists('fragment', $parsedUri)))
        ) {
            foreach ($client['redirect_uri'] as $uri) {
                $legalRedirectUri |= ($returnUri === $uri);
            }
            return $legalRedirectUri;
        }
        return $legalRedirectUri;
    }
}