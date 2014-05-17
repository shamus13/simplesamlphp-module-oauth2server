<?php
/*
 * Service for retrieving the attributes of a resource owner at the time the accessToken was granted
 *
 *
 */
session_cache_limiter('nocache');

header('Content-Type: application/json; charset=utf-8');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$errorCode = 200;
$response = null;

if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $tokenType = $_SERVER['PHP_AUTH_USER'];
    $accessTokenId = $_SERVER['PHP_AUTH_PW'];

    if ('Bearer' === $tokenType) {
        $storeConfig = $config->getValue('store');
        $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
        $store = new $storeClass($storeConfig);

        $accessToken = $store->getAccessToken($accessTokenId);

        if ($accessToken != null) {
            //TODO: check scope is sufficient etc
            if (true) {
                $response = $accessToken['attributes'];
            } else {
                $errorCode = 403;

                $response = array('error' => 'insufficient_scope',
                    'error_description' => 'The token does not have the scopes required for access.');

                //TODO: add scope parameter with scope strings granting access
            }
        } else {
            // no such token, token expired or revoked
            $errorCode = 401;

            header("WWW-Authenticate: Bearer error=\"invalid_token\", error_description=\"No such token\"");

            $response = array('error' => 'invalid_token',
                'error_description' => 'The token does not exist. It may have been revoked or expired.');
        }
    } else {
        // wrong token type
        $errorCode = 401;

        header("WWW-Authenticate: Bearer error=\"invalid_token\", error_description=\"Only Bearer tokens are supported\"");

        $response = array('error' => 'invalid_token',
            'error_description' => 'Only Bearer tokens are supported');
    }
} else {
    // error missing token
    $errorCode = 401;

    header("WWW-Authenticate: Bearer");
}

if($errorCode !== 200) {
    $error_uri = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Module::getModuleURL('oauth2server/error.php'),
        $response);

    $response['error_uri'] = $error_uri;
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

echo json_encode($response);