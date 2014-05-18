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

if ($config->getValue('enable_resource_owner_service', false)) {
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        $tokenType = $_SERVER['PHP_AUTH_USER'];
        $accessTokenId = $_SERVER['PHP_AUTH_PW'];

        if ('Bearer' === $tokenType) {
            $storeConfig = $config->getValue('store');
            $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
            $store = new $storeClass($storeConfig);

            $accessToken = $store->getAccessToken($accessTokenId);

            if ($accessToken != null) {
                $configuredAttributeScopes = $config->getValue('resource_owner_service_attribute_scopes', array());

                $attributeScopes = array_intersect($accessToken['scopes'], array_keys($configuredAttributeScopes));

                if (count($attributeScopes) > 0) {
                    $response = array();

                    $attributeNames = array(); // null means grab all attributes

                    foreach($attributeScopes as $scope) {
                        if(is_array($attributeNames) && is_array($configuredAttributeScopes[$scope])) {
                            $attributeNames = array_merge($attributeNames, $configuredAttributeScopes[$scope]);
                        } else {
                            $attributeNames = null;

                            break;
                        }
                    }

                    if(is_array($attributeNames)) {
                        $response = array();

                        foreach(array_unique($attributeNames) as $attributeName) {
                            if(array_key_exists($attributeName, $accessToken['attributes'])) {
                                $response[$attributeName] = $accessToken['attributes'][$attributeName];
                            }
                        }
                    } else {
                        $response = $accessToken['attributes'];
                    }
                } else {
                    $errorCode = 403;

                    $response = array('error' => 'insufficient_scope',
                        'error_description' => 'The token does not have the scopes required for access.');

                    $response['scope'] = trim(implode(' ', array_keys($configuredAttributeScopes)));
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
} else {
    $errorCode = 403;

    $response = array('error' => 'invalid_request',
        'error_description' => 'resourceOwner end point not enabled');
}

if ($errorCode !== 200 && !is_null($response)) {
    $error_uri = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Module::getModuleURL('oauth2server/error.php'),
        $response);

    $response['error_uri'] = $error_uri;
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

echo json_encode($response);