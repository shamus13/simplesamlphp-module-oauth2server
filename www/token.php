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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (array_key_exists('grant_type', $_POST)) {
        if ($_POST['grant_type'] === 'authorization_code') {
            if (array_key_exists('client_id', $_POST)) {
                if (array_key_exists($_POST['client_id'], $clients)) {
                    if (array_key_exists('code', $_POST)) {
                        $storeConfig = $config->getValue('store');
                        $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
                        $store = new $storeClass($storeConfig);

                        $authorizationCodeEntry = $store->getAuthorizationCode($_POST['code']);

                        if (!is_null($authorizationCodeEntry)) {
                            if ($_POST['client_id'] == $authorizationCodeEntry['clientId']) {
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
                                }
                            } else {
                                $response = array('error' => 'invalid_grant',
                                    'error_description' => 'authorization code grant was not issued for client id: ' .
                                    $_POST['client_id']);
                            }
                        } else {
                            $response = array('error' => 'invalid_grant',
                                'error_description' => 'unknown authorization code grant: ' . $_POST['code']);
                        }
                    } else {
                        $response = array('error' => 'invalid_request', 'error_description' => 'missing code');
                    }
                } else {
                    $response = array('error' => 'invalid_client',
                        'error_description' => 'unknown client id: ' . $_POST['client_id']);
                }
            } else {
                $response = array('error' => 'invalid_request', 'error_description' => 'missing client id');
            }
        } else {
            $response = array('error' => 'unsupported_grant_type',
                'error_description' => 'unsupported grant type: ' . $_POST['grant_type']);
        }
    } else {
        $response = array('error' => 'invalid_request', 'error_description' => 'missing grant type');
    }
} else {
    $response = array('error' => 'invalid_request', 'error_description' => 'http(s) POST required');
}

if (array_key_exists('error', $response)) {
    header('X-PHP-Response-Code: 400', true, 400);
}

echo json_encode($response);
