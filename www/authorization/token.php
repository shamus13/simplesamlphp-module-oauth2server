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
*    grant_type    - 'code' corresponding to the authorization code grant flow
*    code          - authorization code issued by authorization end point during authorization code grant flow.
*
*       or
*
*    grant_type    - 'refresh_token' corresponding to the refresh flow.
*    refresh_token - refresh token previously issued by this token end point.
*
*    client_id     - a configured id string agreed upon by any given client and authorization server
*    redirect_uri  - same redirect_uri as used for the authorization code grant request
*
*    Clients may or may not have to provide Basic authentication header information based on their configuration.
*/

session_cache_limiter('nocache');

header('Content-Type: application/json; charset=utf-8');

//headers to support javascript ajax clients
header('Access-Control-Allow-Origin: *'); //allow cross domain
header('Access-Control-Allow-Headers: Authorization'); //allow custom header

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

$response = null;

$errorCode = 200;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (array_key_exists('grant_type', $_POST)) {
        if ($_POST['grant_type'] === 'authorization_code' || $_POST['grant_type'] === 'refresh_token') {
            $clientId = null;
            $password = null;

            if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                $clientId = $_SERVER['PHP_AUTH_USER'];
                $password = $_SERVER['PHP_AUTH_PW'];
            } elseif (array_key_exists('client_id', $_POST)) {
                $clientId = $_POST['client_id'];
            }

            if (!is_null($clientId)) {
                $client = $clientStore->getClient($clientId);

                if (!is_null($client)) {
                    if ((!isset($client['password']) && is_null($password)) ||
                        (isset($client['password']) && $password === $client['password']) ||
                        (isset($client['alternative_password']) && $password === $client['alternative_password'])
                    ) {

                        $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore($config);

                        $userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

                        $authorizationTokenId = null;
                        $authorizationToken = null;
                        $user = null;

                        if ($_POST['grant_type'] === 'authorization_code' && array_key_exists('code', $_POST)) {
                            $authorizationTokenId = $_POST['code'];
                            $authorizationToken = $tokenStore->getAuthorizationCode($authorizationTokenId);
                            $tokenStore->removeAuthorizationCode($_POST['code']);
                        } elseif ($_POST['grant_type'] === 'refresh_token' && array_key_exists('refresh_token', $_POST)) {
                            $authorizationTokenId = $_POST['refresh_token'];
                            $authorizationToken = $tokenStore->getRefreshToken($authorizationTokenId);
                        }

                        if (!is_null($authorizationToken)) {
                            $user = $userStore->getUser($authorizationToken['userId']);
                        }

                        if (!is_null($user)) {
                            if ($clientId == $authorizationToken['clientId']) {
                                $redirectUri = array_key_exists('redirect_uri', $_POST) ? $_POST['redirect_uri'] : null;

                                if ($authorizationToken['redirectUri'] == $redirectUri) {
                                    $tokenFactory =
                                        new sspmod_oauth2server_OAuth2_TokenFactory(
                                            $authorizationToken['authorizationCodeTTL'],
                                            $authorizationToken['accessTokenTTL'],
                                            $authorizationToken['refreshTokenTTL']
                                        );

                                    $accessToken =
                                        $tokenFactory->createBearerAccessToken($authorizationToken['clientId'],
                                            $authorizationToken['scopes'], $authorizationToken['userId']);

                                    if ($_POST['grant_type'] === 'authorization_code') {
                                        $refreshToken =
                                            $tokenFactory->createRefreshToken($authorizationToken['clientId'],
                                                $authorizationToken['redirectUri'],
                                                $authorizationToken['scopes'],
                                                $authorizationToken['userId']);

                                        $tokenStore->addRefreshToken($refreshToken);

                                        $liveRefreshTokens = array($refreshToken['id']);

                                        foreach ($user['refreshTokens'] as $tokenId) {
                                            if (!is_null($tokenStore->getRefreshToken($tokenId))) {
                                                array_push($liveRefreshTokens, $tokenId);
                                            }
                                        }

                                        $user['refreshTokens'] = $liveRefreshTokens;

                                        if ($refreshToken['expire'] > $user['expire']) {
                                            $user['expire'] = $refreshToken['expire'];
                                        }

                                        if (($index = array_search($authorizationTokenId, $user['authorizationCodes'])) !== false) {
                                            unset($user['authorizationCodes'][$index]);
                                        }

                                    } else {
                                        $refreshToken = $authorizationToken;
                                    }

                                    if ($accessToken['expire'] > $refreshToken['expire']) {
                                        $accessToken['expire'] = $refreshToken['expire'];
                                    }

                                    $tokenStore->addAccessToken($accessToken);

                                    $liveAccessTokens = array($accessToken['id']);

                                    foreach ($user['accessTokens'] as $tokenId) {
                                        if (!is_null($tokenStore->getAccessToken($tokenId))) {
                                            array_push($liveAccessTokens, $tokenId);
                                        }
                                    }

                                    $user['accessTokens'] = $liveAccessTokens;

                                    if (isset($client['expire'])) {
                                        $clientGracePeriod = $config->getValue('client_grace_period', 30 * 24 * 60 * 60);

                                        $now = time();

                                        if ($client['expire'] < $now + $clientGracePeriod / 2) {
                                            $client['expire'] = $now + $clientGracePeriod;

                                            $clientStore->updateClient($client);
                                        }

                                        if ($client['expire'] > $user['expire']) {
                                            $user['expire'] = $client['expire'];
                                        }
                                    }

                                    $response = array('access_token' => $accessToken['id'],
                                        'token_type' => $accessToken['type'],
                                        'expires_in' => ($accessToken['expire'] - time()),
                                        'refresh_token' => $refreshToken['id'],
                                        'scope' => trim(implode(' ', $accessToken['scopes']))
                                    );
                                } else {
                                    $response = array('error' => 'invalid_grant',
                                        'error_description' => 'mismatching redirection uri, expected: ' .
                                        $authorizationToken['redirectUri'] . ' got: ' . $redirectUri,
                                        'error_code_internal' => 'MISMATCHING_' . strtoupper($_POST['grant_type']) . '_URI',
                                        'error_parameters_internal' => array('URI_ACTUAL' => $redirectUri),
                                    );

                                    $errorCode = 400;
                                }
                            } else {
                                if ($_POST['grant_type'] === 'authorization_code') {
                                    $response = array('error' => 'invalid_grant',
                                        'error_description' => 'authorization code grant was not issued for client id: ' . $clientId,
                                        'error_code_internal' => 'MISMATCHING_AUTHORIZATION_CODE_CLIENT',
                                        'error_parameters_internal' => array('CLIENT_ID' => $clientId),
                                    );
                                } else {
                                    $response = array('error' => 'invalid_grant',
                                        'error_description' => 'refresh token was not issued for client id: ' . $clientId,
                                        'error_code_internal' => 'MISMATCHING_REFRESH_TOKEN_CLIENT',
                                        'error_parameters_internal' => array('CLIENT_ID' => $clientId),
                                    );
                                }

                                $errorCode = 400;
                            }

                            $userStore->updateUser($user);

                        } else if (is_null($authorizationTokenId)) {
                            if ($_POST['grant_type'] === 'authorization_code') {
                                $response = array('error' => 'invalid_request',
                                    'error_description' => 'missing authorization code',
                                    'error_code_internal' => 'MISSING_AUTHORIZATION_CODE',
                                    'error_parameters_internal' => array(),
                                );
                            } else {
                                $response = array('error' => 'invalid_request',
                                    'error_description' => 'missing refresh token',
                                    'error_code_internal' => 'MISSING_REFRESH_TOKEN',
                                    'error_parameters_internal' => array(),
                                );
                            }

                            $errorCode = 400;
                        } else {
                            if ($_POST['grant_type'] === 'authorization_code') {
                                $response = array('error' => 'invalid_grant',
                                    'error_description' => 'unknown authorization code grant: ' . $authorizationTokenId,
                                    'error_code_internal' => 'INVALID_AUTHORIZATION_CODE',
                                    'error_parameters_internal' => array('CODE' => $authorizationTokenId),
                                );
                            } else {
                                $response = array('error' => 'invalid_grant',
                                    'error_description' => 'unknown refresh token: ' . $authorizationTokenId,
                                    'error_code_internal' => 'INVALID_REFRESH_TOKEN',
                                    'error_parameters_internal' => array('TOKEN_ID' => $authorizationTokenId),
                                );
                            }

                            $errorCode = 400;
                        }
                    } else {
                        $response = array('error' => 'invalid_client',
                            'error_description' => 'invalid client credentials: ' . $clientId,
                            'error_code_internal' => 'INVALID_CLIENT_CREDENTIALS',
                            'error_parameters_internal' => array(),
                        );

                        $errorCode = 401;
                    }
                } else {
                    $response = array('error' => 'invalid_client',
                        'error_description' => 'unknown client id: ' . $clientId,
                        'error_code_internal' => 'UNAUTHORIZED_CLIENT_ID',
                        'error_parameters_internal' => array('CLIENT_ID' => $clientId),
                    );

                    $errorCode = 400;
                }
            } else {
                $response = array('error' => 'invalid_request',
                    'error_description' => 'missing client id',
                    'error_code_internal' => 'MISSING_CLIENT_ID',
                    'error_parameters_internal' => array(),
                );

                $errorCode = 400;
            }
        } else {
            $response = array('error' => 'unsupported_grant_type',
                'error_description' => 'unsupported grant type: ' . $_POST['grant_type'],
                'error_code_internal' => 'UNSUPPORTED_GRANT_TYPE',
                'error_parameters_internal' => array('GRANT_TYPE' => $_POST['grant_type']),
            );

            $errorCode = 400;
        }
    } else {
        $response = array('error' => 'invalid_request',
            'error_description' => 'missing grant type',
            'error_code_internal' => 'MISSING_GRANT_TYPE',
            'error_parameters_internal' => array(),
        );

        $errorCode = 400;
    }
} else if ($_SERVER['REQUEST_METHOD'] != 'OPTIONS') { //dont freak over the damn ajax options pre-flight requests

    $response = array('error' => 'invalid_request',
        'error_description' => 'http(s) POST required',
        'error_code_internal' => 'MUST_POST',
        'error_parameters_internal' => array(),
    );

    $errorCode = 400;
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

if ($errorCode === 401) {
    header("WWW-Authenticate: Basic realm=\"OAuth 2.0\"", true, $errorCode);
}

if (!is_null($response)) {
    if (array_key_exists('error', $response)) {
        $error_uri = SimpleSAML_Utilities::addURLparameter(
            SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'), $response);

        $response['error_uri'] = $error_uri;
        unset($response['error_code_internal']);
        unset($response['error_parameters_internal']);
    }

    echo json_encode($response);
}