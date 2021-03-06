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
*    A configurable proxy for wrapping an OAuth2 authentication and authorization layer
*    around existing REST services.
*/
session_cache_limiter('nocache');

//headers to support javascript ajax clients
header('Access-Control-Allow-Origin: *'); //allow cross domain
header('Access-Control-Allow-Headers: Authorization'); //allow custom header

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$errorCode = 200;
$response = null;

if ($config->getValue('enable_resource_owner_service', false)) {

    if ($_SERVER['REQUEST_METHOD'] != 'OPTIONS') {  //sort of ignore the damn ajax options pre-flight requests
        foreach (getallheaders() as $name => $value) {
            if ($name === 'Authorization' && strpos($value, 'Bearer ') === 0) {
                $tokenType = 'Bearer';
                $accessTokenId = base64_decode(trim(substr($value, 7)));
            }
        }

        if (isset($accessTokenId)) {
            if ('Bearer' === $tokenType) {
                $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore($config);

                $userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

                $accessToken = $tokenStore->getAccessToken($accessTokenId);

                if ($accessToken != null) {
                    $user = $userStore->getUser($accessToken['userId']);
                }

                if (isset($user) && $user != null) {
                    $matchingEndpoint = null;

                    foreach ($config->getValue('proxy_end_points', array()) as $proxyEndPoint) {

                        $explodedPatternPath = explode('/', $proxyEndPoint['path']);
                        $explodedRequestPath = explode('/', $_SERVER['PATH_INFO']);

                        $pathVariables = array();

                        if (count($explodedPatternPath) === count($explodedRequestPath)) {
                            $matches = true;

                            foreach (array_combine($explodedPatternPath, $explodedRequestPath) as $k => $v) {
                                if (strlen($v) > 0 && preg_match('/\{.*?\}/', $k)) {
                                    $pathVariables[$k] = $v;
                                } else {
                                    $matches &= $k === $v;
                                }
                            }

                            if ($matches) {
                                $matchingEndpoint = $proxyEndPoint;

                                break;
                            }
                        }
                    }

                    if ($matchingEndpoint != null) {
                        //check access right
                        if (array_key_exists($_SERVER['REQUEST_METHOD'], $matchingEndpoint['scope_required'])) {
                            $authorizingScopes = array_intersect($accessToken['scopes'],
                                $matchingEndpoint['scope_required'][$_SERVER['REQUEST_METHOD']]);

                            if (count($authorizingScopes) === 0) {
                                $errorCode = 403;

                                $response = array(
                                    'error' => 'insufficient_scope',
                                    'error_description' => 'The token does not have the scopes required for access.'
                                );

                                $response['scope'] = trim(implode(' ',
                                    $matchingEndpoint['scope_required'][$_SERVER['REQUEST_METHOD']]));

                                $response['error_uri'] = SimpleSAML\Utils\HTTP::addURLParameters(
                                    SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
                                    array(
                                        'error_code_internal' => 'INSUFFICIENT_SCOPE',
                                        'error_parameters_internal' => array('SCOPES' => $response['scope'])
                                    ));

                            }
                        }

                        if ($errorCode === 200) {
                            //build target url
                            $target = $matchingEndpoint['target'];

                            foreach ($user['attributes'] as $name => $values) {
                                if (count($values) > 0) {
                                    $target = str_replace('{' . $name . '}', rawurlencode($values[0]), $target);
                                }
                            }

                            foreach ($pathVariables as $name => $value) {
                                $target = str_replace($name, rawurlencode($value), $target);
                            }

                            if ($_SERVER['QUERY_STRING'] !== '') {
                                $target .= '?' . $_SERVER['QUERY_STRING'];
                            }

                            $headers = array();

                            foreach ($_SERVER as $key => $value) {
                                if (substr($key, 0, 5) === 'HTTP_') {
                                    $header = str_replace(' ', '-',
                                        ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                                    $headers[$header] = $value;
                                }
                            }

                            foreach ($matchingEndpoint['additional_headers'] as $k => $v) {
                                if ($v != null) {
                                    foreach ($user['attributes'] as $name => $values) {
                                        if (count($values) > 0) {
                                            $v = str_replace('{' . $name . '}', $values[0], $v);
                                        }
                                    }
                                }

                                $headers[$k] = $v;
                            }

                            $body = file_get_contents('php://input');

                            $info = array();
                            $options = array(
                                CURLOPT_URL => $target,
                                CURLOPT_HEADER => 1,
                                CURLOPT_HTTPHEADER => $headers,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 4,
                            );

                            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                $options[CURLOPT_POST] = 1;
                                $options[CURLOPT_FRESH_CONNECT] = 1;
                                $options[CURLOPT_RETURNTRANSFER] = 1;
                                $options[CURLOPT_FORBID_REUSE] = 1;
                                $options[CURLOPT_POSTFIELDS] = $body;
                            } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                                $options[CURLOPT_PUT] = 1;
                                $options[CURLOPT_FRESH_CONNECT] = 1;
                                $options[CURLOPT_RETURNTRANSFER] = 1;
                                $options[CURLOPT_FORBID_REUSE] = 1;
                                $options[CURLOPT_POSTFIELDS] = $body;
                            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                                $options[CURLOPT_FRESH_CONNECT] = 1;
                                $options[CURLOPT_RETURNTRANSFER] = 1;
                                $options[CURLOPT_FORBID_REUSE] = 1;
                                $options[CURLOPT_POSTFIELDS] = $body;
                            } else { // GET

                            }

                            $curl = curl_init();

                            curl_setopt_array($curl, $options);

                            if (!$result = curl_exec($curl)) {
                                trigger_error(curl_error($curl));
                            }

                            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                            $errorCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                            $contentLength = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                            $header = substr($result, 0, $header_size);

                            curl_close($curl);

                            $contentTypeHeader = array();
                            if (preg_match('/(Content-Type:.*?)\r\n/', $header, $contentTypeHeader)) {
                                header($contentTypeHeader[1]);
                            }

                            if ($contentLength != false) {
                                header('Content-Length: ' . $contentLength);
                            }

                            $response = substr($result, $header_size);
                        }

                    } else {
                        $errorCode = 404;;
                    }
                } else {
                    // no such token, token expired or revoked
                    $errorCode = 401;

                    $response = array(
                        'error' => 'invalid_token',
                        'error_description' => 'The token does not exist. It may have been revoked or expired.'
                    );

                    $response['error_uri'] = SimpleSAML\Utils\HTTP::addURLParameters(
                        SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
                        array(
                            'error_code_internal' => 'INVALID_ACCESS_TOKEN',
                            'error_parameters_internal' => array('TOKEN_ID' => $accessTokenId)
                        ));
                }
            } else {
                // wrong token type
                $errorCode = 401;

                $response = array(
                    'error' => 'invalid_token',
                    'error_description' => 'Only Bearer tokens are supported'
                );

                $response['error_uri'] = SimpleSAML\Utils\HTTP::addURLParameters(
                    SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
                    array(
                        'error_code_internal' => 'UNSUPPORTED_ACCESS_TOKEN',
                        'error_parameters_internal' => array('TOKEN_ID' => $accessTokenId)
                    ));
            }
        } else {
            // error missing token
            $errorCode = 401;

            $response = array();
        }
    }
} else {
    $errorCode = 403;

    $response = array(
        'error' => 'invalid_request',
        'error_description' => 'resource owner end point not enabled'
    );

    $response['error_uri'] = SimpleSAML\Utils\HTTP::addURLParameters(
        SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
        array(
            'error_code_internal' => 'DISABLED',
            'error_parameters_internal' => array()
        ));
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

if (is_array($response)) { // we encountered a local error, return diagnostics header
    $authHeader = "WWW-Authenticate: Bearer ";

    if (is_array($response) && array_key_exists('error', $response)) {
        $authHeader .= 'error="' . $response['error'] . '",error_description="' .
            $response['error_description'] . '",' . 'error_uri="' . urlencode($response['error_uri']) . '"';

        if (array_key_exists('scope', $response)) {
            $authHeader .= ',scope="' . $response['scope'] . '"';
        }
    }

    header($authHeader, true, $errorCode);
} else { // return the response from the matched proxy
    echo $response;
}
