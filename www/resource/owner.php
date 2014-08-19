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
//TODO: make this able to look up user data in an LDAP etc
session_cache_limiter('nocache');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

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
            $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore($config);

            $userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

            $accessToken = $tokenStore->getAccessToken($accessTokenId);

            if ($accessToken != null) {
                $user = $userStore->getUser($accessToken['userId']);
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

                    $response['error_uri'] = SimpleSAML_Utilities::addURLparameter(
                        SimpleSAML_Module::getModuleURL('oauth2server/resource/error.php'),
                        array('error_code_internal' => 'INSUFFICIENT_SCOPE',
                            'error_parameters_internal' => array('SCOPES' => $response['scope'])));

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

if ($errorCode !== 200) {
    $authHeader = "WWW-Authenticate: Bearer ";

    if (array_key_exists('error', $response)) {
        $authHeader .= 'error="' . $response['error'] . '",error_description="' .
            $response['error_description'] . '",' . 'error_uri="' . urlencode($response['error_uri']) . '"';

        if (array_key_exists('scope', $response)) {
            $authHeader .= ',scope="' . $response['scope'] . '"';
        }
    }

    header($authHeader, true, $errorCode);
} else {
    echo count($response) > 0 ? json_encode($response): '{}';
}