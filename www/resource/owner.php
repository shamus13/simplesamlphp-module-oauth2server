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
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) { //todo: this is broken, bearer tokens dont parse like this
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

                $response = array('error' => 'invalid_token',
                    'error_description' => 'The token does not exist. It may have been revoked or expired.');
            }
        } else {
            // wrong token type
            $errorCode = 401;

            $response = array('error' => 'invalid_token',
                'error_description' => 'Only Bearer tokens are supported');
        }
    } else {
        // error missing token
        $errorCode = 401;

        $response = array();
    }
} else {
    $errorCode = 403;

    $response = array('error' => 'invalid_request',
        'error_description' => 'resource owner end point not enabled');
}

header('X-PHP-Response-Code: ' . $errorCode, true, $errorCode);

if ($errorCode !== 200) {
    $authHeader = "WWW-Authenticate: Bearer ";

    if(array_key_exists('error', $response)) {
        $authHeader .= 'error=\"'.$response['error'].'",error_description="'.$response['error_description'].'"';

        if(array_key_exists('scope', $response)) {
            $authHeader .= ',scope="'.$response['scope'].'"';
        }
    }

    header($authHeader);
} else {
    echo json_encode($response);
}