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
            $requestedScopes = sspmod_oauth2server_Utility_Uri::augmentRequestedScopesWithRequiredScopes($client,
                (isset($_REQUEST['scope'])) ? explode(' ', $_REQUEST['scope']) : array());

            $invalidScopes = sspmod_oauth2server_Utility_Uri::findInvalidScopes($client, $requestedScopes);

            if (count($invalidScopes) == 0) {
                if (isset($_REQUEST['response_type']) &&
                    ($_REQUEST['response_type'] === 'code' || $_REQUEST['response_type'] === 'token')) {
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
                        $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
                            'invalid_request', 'missing response type', 'MISSING_RESPONSE_TYPE', array()
                        );
                    } else {
                        $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
                            'unsupported_response_type', 'unsupported response type: ' . $_REQUEST['response_type'],
                            'UNSUPPORTED_RESPONSE_TYPE', array('RESPONSE_TYPE' => $_REQUEST['response_type'])
                        );
                    }
                }
            } else {
                $firstOffendingScope = array_pop($invalidScopes);

                $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
                    'invalid_scope', 'invalid scope: ' . $firstOffendingScope, 'INVALID_SCOPE',
                    array('SCOPE' => $firstOffendingScope)
                );
            }

            //something went wrong, but we do have a valid uri to redirect to.

            $errorParameters['error_uri'] =
                SimpleSAML\Utils\HTTP::addURLParameters(
                    SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'), $errorParameters);

            if (isset($_REQUEST['state'])) {
                $errorParameters['state'] = $_REQUEST['state'];
            }

            unset($errorParameters['error_code_internal']);
            unset($errorParameters['error_parameters_internal']);

            sspmod_oauth2server_Utility_Uri::redirectUri(sspmod_oauth2server_Utility_Uri::
            addQueryParametersToUrl($returnUri, $errorParameters));
        } else {
            if (is_string(parse_url($returnUri, PHP_URL_FRAGMENT))) {
                $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
                    'invalid_redirect_uri', 'fragments are not allowed in redirect_uri: ' . $returnUri,
                    'FRAGMENT_REDIRECT_URI',
                    array(
                        'REDIRECT_URI' => $returnUri,
                        'FRAGMENT' => parse_url($returnUri, PHP_URL_FRAGMENT)
                    )
                );
            } else {
                // this is not a proper error code used only internally
                $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
                    'invalid_redirect_uri', 'illegal redirect_uri: ' . $returnUri,
                    'INVALID_REDIRECT_URI', array('REDIRECT_URI' => $returnUri));
            }
        }
    } else {
        $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
            'server_error', 'no redirection uri associated with client id', 'NO_REDIRECT_URI', array());
    }
} else {
    if (isset($_REQUEST['client_id'])) {
        $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
            'unauthorized_client', 'unauthorized_client: ' . $_REQUEST['client_id'],
            'UNAUTHORIZED_CLIENT', array('CLIENT_ID' => $_REQUEST['client_id']));
    } else {
        $errorParameters = \sspmod_oauth2server_Utility_Uri::buildErrorResponse(
            'missing_client', 'missing client id', 'MISSING_CLIENT_ID', array());
    }
}

//something went wrong, and we do not have a valid uri to redirect to.
$error_uri = SimpleSAML\Utils\HTTP::addURLParameters(
    SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'), $errorParameters);

SimpleSAML\Utils\HTTP::redirectTrustedURL($error_uri);
