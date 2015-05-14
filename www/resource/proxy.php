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
                    foreach($config->getValue('proxy_end_points', array()) as $proxyEndPoint) {

                        $pathMapping = array_combine(explode('/', $proxyEndPoint['path']),
                            explode('/', $_SERVER['PATH_INFO']));

                        $matches = $pathMapping != false;

                        $pathVariables = array();

                        if ($matches) {
                            foreach ($pathMapping as $k => $v) {
                                if(strlen($v) > 0 && preg_match('/\{.*?\}/', $k)) {
                                    $pathVariables[$k] = $v;
                                } else {
                                    $matches &= $k === $v;
                                }
                            }
                        }

                        if ($matches) {
                            //check access right
                            if (array_key_exists($_SERVER['REQUEST_METHOD'], $proxyEndPoint['scope_required'])) {
                                $authorizingScopes = array_intersect($accessToken['scopes'],
                                    $proxyEndPoint['scope_required'][$_SERVER['REQUEST_METHOD']]);

                                if (count($authorizingScopes) === 0) {
                                    $errorCode = 403;

                                    $response = array('error' => 'insufficient_scope',
                                        'error_description' => 'The token does not have the scopes required for access.');

                                    $response['scope'] = trim(implode(' ',
                                        $proxyEndPoint['scope_required'][$_SERVER['REQUEST_METHOD']]));

                                    $response['error_uri'] = SimpleSAML_Utilities::addURLparameter(
                                        SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
                                        array('error_code_internal' => 'INSUFFICIENT_SCOPE',
                                            'error_parameters_internal' => array('SCOPES' => $response['scope'])));

                                }
                            }

                            if ($errorCode === 200) {
                                //build target url
                                $target = $proxyEndPoint['target'];

                                foreach ($user['attributes'] as $name => $values) {
                                    if (count($values) > 0) {
                                        $target = str_replace('{' . $name . '}', $values[0], $target);
                                    }
                                }

                                foreach($pathVariables as $name => $value) {
                                    $target = str_replace($name, $value, $target);
                                }

                                //read raw request

                                //add extra headers

                                //forward request to target

                                //forward response from target

                                //return response
                                $response = array(
                                    'path' => $proxyEndPoint['path'],
                                    'target' => $target,
                                    'mapping' => $pathMapping
                                );
                            }

                        } else {
                            $errorCode = 404;
                        }
                    }
                } else {
                    // no such token, token expired or revoked
                    $errorCode = 401;

                    $response = array('error' => 'invalid_token',
                        'error_description' => 'The token does not exist. It may have been revoked or expired.');

                    $response['error_uri'] = SimpleSAML_Utilities::addURLparameter(
                        SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
                        array('error_code_internal' => 'INVALID_ACCESS_TOKEN',
                            'error_parameters_internal' => array('TOKEN_ID' => $accessTokenId)));
                }
            } else {
                // wrong token type
                $errorCode = 401;

                $response = array('error' => 'invalid_token',
                    'error_description' => 'Only Bearer tokens are supported');

                $response['error_uri'] = SimpleSAML_Utilities::addURLparameter(
                    SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
                    array('error_code_internal' => 'UNSUPPORTED_ACCESS_TOKEN',
                        'error_parameters_internal' => array('TOKEN_ID' => $accessTokenId)));
            }
        } else {
            // error missing token
            $errorCode = 401;

            $response = array();
        }
    }
} else {
    $errorCode = 403;

    $response = array('error' => 'invalid_request',
        'error_description' => 'resource owner end point not enabled');

    $response['error_uri'] = SimpleSAML_Utilities::addURLparameter(
        SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
        array('error_code_internal' => 'DISABLED',
            'error_parameters_internal' => array()));
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

if($errorCode === 200) {
    header('Content-Type: application/json; charset=utf-8');

    echo count($response) > 0 ? json_encode($response) : '{}';
} else if ($errorCode !== 404) {
    $authHeader = "WWW-Authenticate: Bearer ";

    if (array_key_exists('error', $response)) {
        $authHeader .= 'error="' . $response['error'] . '",error_description="' .
            $response['error_description'] . '",' . 'error_uri="' . urlencode($response['error_uri']) . '"';

        if (array_key_exists('scope', $response)) {
            $authHeader .= ',scope="' . $response['scope'] . '"';
        }
    }

    header($authHeader, true, $errorCode);
}