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
*    Input:
*    access_token - the id of the access token to validate.
*
*    Resource servers must provide Basic authentication header information.
*
*    Output:
*    json array containing a status attribute as well as access token properties, if
*   the token was valid
*
*/

session_cache_limiter('nocache');

header('Content-Type: application/json; charset=utf-8');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_token']) &&
    isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])
) {

    $resourceServerId = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    $resourceServers = $config->getValue('resources', array());

    if (array_key_exists($resourceServerId, $resourceServers)) {
        $resourceServer = $resourceServers[$resourceServerId];

        if ($password === $resourceServer['password'] ||
            (array_key_exists('alternative_password', $resourceServer) &&
                $password === $resourceServer['alternative_password'])
        ) {
            $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore($config);

            $token = $tokenStore->getAccessToken($_POST['access_token']);

            if (is_array($token)) {
                $clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);
                $userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

                if (is_array($clientStore->getClient($token['clientId'])) &&
                    is_array($userStore->getUser($token['userId']))
                ) {

                    echo json_encode(array(
                        'status' => 'valid_token',
                        'expires_in' => ($accessToken['expire'] - time()),
                        'scope' => $accessToken['scopes'],
                        'userId' => $accessToken['userId']
                    ));

                    return;
                }
            }

            echo json_encode(array('status' => 'unknown_token'));

            return;
        }
    }

    $errorCode = 401;
    $status = 'invalid_resource';
} else if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    $errorCode = 401;
    $status = 'invalid_resource';
} else {
    $errorCode = 400;
    $status = 'invalid_request';
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

if ($errorCode === 401) {
    header("WWW-Authenticate: Basic realm=\"OAuth 2.0\"", true, $errorCode);
}

echo json_encode(array('status' => $status));
