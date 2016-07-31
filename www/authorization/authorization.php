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
*    response_type - 'code' corresponding to the authorization code grant flow and
*                    'token' corresponding to the implicit grant flow is supported.
*    client_id     - a configured id string agreed upon by any given client and authorization server
*    redirect_uri  - an optional configured uri to redirect the user agent to after authorization is granted or denied
*    scope         - optional configured scope strings agreed upon by any given client and authorization server
*    state         - optional string which clients can use to maintain state during authentication and authorization flows.
*/

session_cache_limiter('nocache');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

$responseParameters = array();

if (isset($_REQUEST['state'])) {
    $responseParameters['state'] = $_REQUEST['state'];
}

if (isset($_REQUEST['client_id'])) {
    $client = $clientStore->getClient($_REQUEST['client_id']);
}

if (isset($client)) {
    $as = new SimpleSAML_Auth_Simple($config->getValue('authsource'));

    $params = sspmod_oauth2server_Utility_Uri::calculateScopingParameters($client);

    $as->requireAuth($params);

    if (array_key_exists('redirect_uri', $client) &&
        is_array($client['redirect_uri']) &&
        count($client['redirect_uri']) > 0
    ) {
        $returnUri = (isset($_REQUEST['redirect_uri'])) ? $_REQUEST['redirect_uri'] : $client['redirect_uri'][0];

        $legalRedirectUri = sspmod_oauth2server_Utility_Uri::validateRedirectUri($returnUri, $client);

        if ($legalRedirectUri) {
            $requestedScopes = (isset($_REQUEST['scope'])) ? explode(' ', $_REQUEST['scope']) : array();
            $definedScopes = (isset($client['scope'])) ? $client['scope'] : array();

            foreach ($client['scope'] as $scope => $required) {
                if ($required) {
                    array_push($requestedScopes, $scope);
                }
            }

            $invalidScopes = array_diff($requestedScopes, array_keys($definedScopes));

            if (count($invalidScopes) == 0) {
                if (isset($_REQUEST['response_type']) &&
                    ($_REQUEST['response_type'] === 'code' || $_REQUEST['response_type'] === 'token')
                ) {

                    $state = array(
                        'clientId' => $_REQUEST['client_id'],
                        'redirectUri' => (isset($_REQUEST['redirect_uri'])) ? $_REQUEST['redirect_uri'] : null,
                        'requestedScopes' => array_unique($requestedScopes),
                        'returnUri' => $returnUri,
                        'response_type' => $_REQUEST['response_type']
                    );

                    if (array_key_exists('state', $_REQUEST)) {
                        $state['state'] = $_REQUEST['state'];
                    }

                    $stateId = SimpleSAML_Auth_State::saveState($state, 'oauth2server:authorization/consent');

                    $consentUri =
                        SimpleSAML\Utils\HTTP::addURLParameters(
                            SimpleSAML_Module::getModuleURL('oauth2server/authorization/consent.php'),
                            array('stateId' => $stateId));

                    SimpleSAML\Utils\HTTP::redirectTrustedURL($consentUri);

                } else {
                    if (!isset($_REQUEST['response_type'])) {
                        $error = 'invalid_request';
                        $error_description = 'missing response type';
                        $error_code_internal = 'MISSING_RESPONSE_TYPE';
                        $error_parameters_internal = array();
                    } else {
                        $error = 'unsupported_response_type';
                        $error_description = 'unsupported response type: ' . $_REQUEST['response_type'];
                        $error_code_internal = 'UNSUPPORTED_RESPONSE_TYPE';
                        $error_parameters_internal = array('RESPONSE_TYPE' => $_REQUEST['response_type']);

                    }
                }
            } else {
                $firstOffendingScope = array_pop($invalidScopes);

                $error = 'invalid_scope';
                $error_description = 'invalid scope: ' . $firstOffendingScope;
                $error_code_internal = 'INVALID_SCOPE';
                $error_parameters_internal = array('SCOPE' => $firstOffendingScope);
            }

            //something went wrong, but we do have a valid uri to redirect to.

            $responseParameters['error'] = $error;
            $responseParameters['error_description'] = $error_description;

            $error_uri =
                SimpleSAML\Utils\HTTP::addURLParameters(
                    SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'),
                    array(
                        'error' => $error,
                        'error_description' => $error_description,
                        'error_code_internal' => $error_code_internal,
                        'error_parameters_internal' => $error_parameters_internal
                    ));

            $responseParameters['error_uri'] = $error_uri;

            sspmod_oauth2server_Utility_Uri::redirectUri(sspmod_oauth2server_Utility_Uri::addQueryParametersToUrl($returnUri,
                $responseParameters));
        } else {
            if (is_string(parse_url($returnUri, PHP_URL_FRAGMENT))) {
                $error = 'invalid_redirect_uri'; // this is not a proper error code used only internally
                $error_description = 'fragments are not allowed in redirect_uri: ' . $returnUri;
                $error_code_internal = 'FRAGMENT_REDIRECT_URI';
                $error_parameters_internal = array(
                    'REDIRECT_URI' => $returnUri,
                    'FRAGMENT' => parse_url($returnUri, PHP_URL_FRAGMENT)
                );

            } else {
                $error = 'invalid_redirect_uri'; // this is not a proper error code used only internally
                $error_description = 'illegal redirect_uri: ' . $returnUri;
                $error_code_internal = 'INVALID_REDIRECT_URI';
                $error_parameters_internal = array('REDIRECT_URI' => $returnUri);
            }
        }
    } else {
        $error = 'server_error';
        $error_description = 'no redirection uri associated with client id';
        $error_code_internal = 'NO_REDIRECT_URI';
        $error_parameters_internal = array();
    }
} else {
    if (isset($_REQUEST['client_id'])) {
        $error = 'unauthorized_client';
        $error_description = 'unauthorized_client: ' . $_REQUEST['client_id'];
        $error_code_internal = 'UNAUTHORIZED_CLIENT';
        $error_parameters_internal = array('CLIENT_ID' => $_REQUEST['client_id']);
    } else {
        $error = 'missing_client';
        $error_description = 'missing client id';
        $error_code_internal = 'MISSING_CLIENT_ID';
        $error_parameters_internal = array();
    }
}

//something went wrong, and we do not have a valid uri to redirect to.

$responseParameters['error'] = $error;
$responseParameters['error_description'] = $error_description;
$responseParameters['error_code_internal'] = $error_code_internal;
$responseParameters['error_parameters_internal'] = $error_parameters_internal;

$error_uri = SimpleSAML\Utils\HTTP::addURLParameters(
    SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'), $responseParameters);

SimpleSAML\Utils\HTTP::redirectTrustedURL($error_uri);
