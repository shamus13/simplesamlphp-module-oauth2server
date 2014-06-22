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
*    response_type - only 'code' corresponding to the authorization code grant flow is supported
*    client_id     - a configured id string agreed upon by any given client and authorization server
*    redirect_uri  - an optional configured uri to redirect the user agent to after authorization is granted or denied
*    scope         - optional configured scope strings agreed upon by any given client and authorization server
*    state         - optional string which clients can use to maintain state during authentication and authorization flows.
*/

session_cache_limiter('nocache');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$as = new SimpleSAML_Auth_Simple($config->getValue('authsource'));

$as->requireAuth();

$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

$responseParameters = array();

if (isset($_REQUEST['state'])) {
    $responseParameters['state'] = $_REQUEST['state'];
}

if (isset($_REQUEST['client_id'])) {
    $client = $clientStore->getClient($_REQUEST['client_id']);
}

if (isset($client)) {
    if (array_key_exists('redirect_uri', $client) &&
        is_array($client['redirect_uri']) &&
        count($client['redirect_uri']) > 0
    ) {
        $returnUri = (isset($_REQUEST['redirect_uri'])) ? $_REQUEST['redirect_uri'] : $client['redirect_uri'][0];

        $legalRedirectUri = false; //TODO: we also need to verify, that there is no fragment part in the uri but how?

        foreach ($client['redirect_uri'] as $uri) {
            $legalRedirectUri |= strpos($returnUri, $uri) === 0;
        }

        if ($legalRedirectUri) {
            $requestedScopes = (isset($_REQUEST['scope'])) ? explode(' ', $_REQUEST['scope']) : array();
            $definedScopes = (isset($client['scope'])) ? $client['scope'] : array();

            $invalidScopes = array_diff($requestedScopes, $definedScopes);

            if (count($invalidScopes) == 0) {
                if (isset($_REQUEST['response_type']) && $_REQUEST['response_type'] === 'code') {

                    $state = array('clientId' => $_REQUEST['client_id'],
                        'redirectUri' => (isset($_REQUEST['redirect_uri'])) ? $_REQUEST['redirect_uri'] : null,
                        'requestedScopes' => $requestedScopes,
                        'returnUri' => $returnUri);

                    if (array_key_exists('state', $_REQUEST)) {
                        $state['state'] = $_REQUEST['state'];
                    }

                    $stateId = SimpleSAML_Auth_State::saveState($state, 'oauth2server:authorization/consent');

                    $consentUri =
                        SimpleSAML_Utilities::addURLparameter(
                            SimpleSAML_Module::getModuleURL('oauth2server/authorization/consent.php'),
                            array('stateId' => $stateId));

                    SimpleSAML_Utilities::redirect($consentUri);

                } else if (!isset($_REQUEST['response_type'])) {
                    $error = 'invalid_request';
                    $error_description = 'missing response type';
                } else {
                    $error = 'unsupported_response_type';
                    $error_description = 'unsupported response type: ' . $_REQUEST['response_type'];
                }
            } else {
                $error = 'invalid_scope';
                $error_description = 'invalid scope: ' . $invalidScopes[0];
            }

            //something went wrong, but we do have a valid uri to redirect to.

            $responseParameters['error'] = $error;
            $responseParameters['error_description'] = $error_description;

            $error_uri =
                SimpleSAML_Utilities::addURLparameter(
                    SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'),
                    $responseParameters);

            $responseParameters['error_uri'] = $error_uri;

            SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($returnUri, $responseParameters));
        } else {
            $error = 'invalid_redirect_uri'; // this is not a proper error code used only internally
            $error_description = 'illegal redirect_uri: ' . $returnUri;
        }
    } else {
        $error = 'server_error';
        $error_description = 'no redirection uri associated with client id';
    }
} else if (isset($_REQUEST['client_id'])) {
    $error = 'unauthorized_client';
    $error_description = 'unauthorized_client: ' . $_REQUEST['client_id'];
} else {
    $error = 'missing_client';
    $error_description = 'missing client id';
}

//something went wrong, and we do not have a valid uri to redirect to.

$responseParameters['error'] = $error;
$responseParameters['error_description'] = $error_description;

$error_uri = SimpleSAML_Utilities::addURLparameter(
    SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'), $responseParameters);

SimpleSAML_Utilities::redirect($error_uri);
