<?php
/*
 *
 *
 * grant_type    - only 'code' corresponding to the authorization code grant flow is supported
 * code          - authorization code
 * client_id     - a configured id string agreed upon by any given client and authorization server
 * redirect_uri  - same redirect_uri as used for the authorization code grant request
 */
session_cache_limiter('nocache');

header('Content-Type: application/json; charset=utf-8');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$clients = $config->getValue('clients', array());

$response = null;

SimpleSAML_Logger::debug('token:' . var_export($_SERVER, true));

$errorCode = 200;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (array_key_exists('grant_type', $_POST)) {
        if ($_POST['grant_type'] === 'authorization_code') {
            $clientId = null;
            $password = null;

            if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                $clientId = $_SERVER['PHP_AUTH_USER'];
                $password = $_SERVER['PHP_AUTH_PW'];
            } elseif (array_key_exists('client_id', $_POST)) {
                $clientId = $_POST['client_id'];
            }

            if (!is_null($clientId)) {
                if (array_key_exists($clientId, $clients)) {
                    if ((!isset($clients[$clientId]['password']) && is_null($password)) ||
                        $password === $clients[$clientId]['password']) {
                        if (array_key_exists('code', $_POST)) {
                            $storeConfig = $config->getValue('store');
                            $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
                            $store = new $storeClass($storeConfig);

                            $authorizationCodeEntry = $store->getAuthorizationCode($_POST['code']);

                            if (!is_null($authorizationCodeEntry)) {
                                if ($clientId == $authorizationCodeEntry['clientId']) {
                                    $redirectUri = array_key_exists('redirect_uri', $_POST) ? $_POST['redirect_uri'] : null;

                                    if ($authorizationCodeEntry['redirectUri'] == $redirectUri) {
                                        $store->removeAuthorizationCode($_POST['code']);

                                        $tokenFactory =
                                            new sspmod_oauth2server_OAuth2_TokenFactory(
                                                $config->getValue('authorization_code_time_to_live', 300),
                                                $config->getValue('access_token_time_to_live', 300)
                                            );

                                        $accessToken =
                                            $tokenFactory->createBearerAccessToken($authorizationCodeEntry['clientId'],
                                                $authorizationCodeEntry['scopes'], $authorizationCodeEntry['attributes']);

                                        $store->addAccessToken($accessToken);

                                        $response = array('access_token' => $accessToken['id'],
                                            'token_type' => $accessToken['type'],
                                            'expires_in' => ($accessToken['expire'] - time()));
                                    } else {
                                        $response = array('error' => 'invalid_grant',
                                            'error_description' => 'mismatching redirection uri, expected: ' .
                                            $authorizationCodeEntry['redirect_uri'] . ' got: ' . $redirectUri);

                                        $errorCode = 400;
                                    }
                                } else {
                                    $response = array('error' => 'invalid_grant',
                                        'error_description' => 'authorization code grant was not issued for client id: ' .
                                        $clientId);

                                    $errorCode = 400;
                                }
                            } else {
                                $response = array('error' => 'invalid_grant',
                                    'error_description' => 'unknown authorization code grant: ' . $_POST['code']);

                                $errorCode = 400;
                            }
                        } else {
                            $response = array('error' => 'invalid_request', 'error_description' => 'missing code');

                            $errorCode = 400;
                        }
                    } else {
                        $response = array('error' => 'invalid_client',
                            'error_description' => 'invalid client credentials: ' . $clientId);

                        $errorCode = 401;
                    }
                } else {
                    $response = array('error' => 'invalid_client',
                        'error_description' => 'unknown client id: ' . $clientId);

                    $errorCode = 400;
                }
            } else {
                $response = array('error' => 'invalid_request', 'error_description' => 'missing client id');

                $errorCode = 400;
            }
        } else {
            $response = array('error' => 'unsupported_grant_type',
                'error_description' => 'unsupported grant type: ' . $_POST['grant_type']);

            $errorCode = 400;
        }
    } else {
        $response = array('error' => 'invalid_request', 'error_description' => 'missing grant type');

        $errorCode = 400;
    }
} else {
    $response = array('error' => 'invalid_request', 'error_description' => 'http(s) POST required');

    $errorCode = 400;
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

if ($errorCode === 401) {
    header("WWW-Authenticate: Basic realm=\"OAuth 2.0\"");
}

echo json_encode($response);
