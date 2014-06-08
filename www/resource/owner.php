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
*    OAuth2 resource end point for accessing attributes associated with the resource owner
*/

session_cache_limiter('nocache');

header('Content-Type: application/json; charset=utf-8');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$errorCode = 200;
$response = null;

if ($config->getValue('enable_resource_owner_service', false)) {

    foreach (getallheaders() as $name => $value) {
        if ($name === 'Authorization' && strpos($value, 'Bearer ') === 0) {
            $tokenType = 'Bearer';
            $accessTokenId = base64_decode(trim(substr($value, 7)));
        }
    }

    if (isset($accessTokenId)) {
        if ('Bearer' === $tokenType) {
            $storeConfig = $config->getValue('store');
            $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
            $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore(new $storeClass($storeConfig));

            $accessToken = $tokenStore->getAccessToken($accessTokenId);

            if ($accessToken != null) {
                $user = $tokenStore->getUser($accessToken['userId']);
            }

            if (isset($user) && $user != null) {
                $configuredAttributeScopes = $config->getValue('resource_owner_service_attribute_scopes', array());

                $attributeScopes = array_intersect($accessToken['scopes'], array_keys($configuredAttributeScopes));

                if (count($attributeScopes) > 0) {
                    $response = array();

                    $attributeNames = array(); // null means grab all attributes

                    foreach ($attributeScopes as $scope) {
                        if (is_array($attributeNames) && is_array($configuredAttributeScopes[$scope])) {
                            $attributeNames = array_merge($attributeNames, $configuredAttributeScopes[$scope]);
                        } else {
                            $attributeNames = null;

                            break;
                        }
                    }

                    if (is_array($attributeNames)) {
                        $response = array();

                        foreach (array_unique($attributeNames) as $attributeName) {
                            if (array_key_exists($attributeName, $user['attributes'])) {
                                $response[$attributeName] = $user['attributes'][$attributeName];
                            }
                        }
                    } else {
                        $response = $user['attributes'];
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

    if (array_key_exists('error', $response)) {
        $authHeader .= 'error="' . $response['error'] . '",error_description="' . $response['error_description'] . '"';

        if (array_key_exists('scope', $response)) {
            $authHeader .= ',scope="' . $response['scope'] . '"';
        }
    }

    header($authHeader, true, $errorCode);
} else {
    echo json_encode($response);
}