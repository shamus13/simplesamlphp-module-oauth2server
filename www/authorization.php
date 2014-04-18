<?php
/*
 *
 *
 * response_type - only 'code' corresponding to the authorization code grant flow is supported
 * client_id     - a configured id string agreed upon by any given client and authorization server
 * redirect_uri  - an optional configured uri to redirect the user agent to after authorization is granted or denied
 * scope         - optional configured scope strings agreed upon by any given client and authorization server
 * state         - optional string which clients can use to maintain state during authentication and authorization flows.
 */
session_cache_limiter('nocache');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$as = new SimpleSAML_Auth_Simple($config->getValue('authsource'));

$as->requireAuth();

$clients = $config->getValue('clients', array());

$responseParameters = array();

if (isset($_REQUEST['state'])) {
    $responseParameters['state'] = $_REQUEST['state'];
}

if (isset($_REQUEST['client_id']) && array_key_exists($_REQUEST['client_id'], $clients)) {
    $client = $clients[$_REQUEST['client_id']];

    if (array_key_exists('redirect_uri', $client) &&
        is_array($client['redirect_uri']) &&
        count($client['redirect_uri']) > 0
    ) {
        $redirect_uri = (isset($_REQUEST['redirect_uri'])) ? $_REQUEST['redirect_uri'] : $client['redirect_uri'][0];

        $legalRedirectUri = false; //TODO: we also need to verify, that there is no fragment part in the uri but how?

        foreach ($client['redirect_uri'] as $uri) {
            $legalRedirectUri |= strpos($redirect_uri, $uri) === 0;
        }

        if ($legalRedirectUri) {
            $requestedScopes = (isset($_REQUEST['scope'])) ? explode(' ', $_REQUEST['scope']) : array();
            $definedScopes = (isset($client['scope'])) ? $client['scope'] : array();

            $invalidScopes = array_diff($requestedScopes, $definedScopes);

            if (count($invalidScopes) == 0) {
                if (isset($_REQUEST['response_type']) && $_REQUEST['response_type'] === 'code') {
                    //TODO: we need to ask the user to authorize the the grant and possibly prune the scopes

                    $state = array('clientId' => $_REQUEST['client_id'],
                        'redirectUri' => (isset($_REQUEST['redirect_uri'])) ? $_REQUEST['redirect_uri'] : null,
                        'requestedScopes' => $requestedScopes);

                    if (array_key_exists('state', $_REQUEST)) {
                        $state['state'] = $_REQUEST['state'];
                    }

                    $stateId = SimpleSAML_Auth_State::saveState($state, 'oauth2server:consent');

                    $consentUri = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Module::getModuleURL('oauth2server/consent.php'),
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

            $stateId = SimpleSAML_Auth_State::saveState($responseParameters, 'oauth2server:error');

            $error_uri = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Module::getModuleURL('oauth2server/error.php'),
                array('stateId' => $stateId));

            $responseParameters['error_uri'] = $error_uri;

            SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($redirect_uri, $responseParameters));
        } else {
            $error = 'invalid_redirect_uri'; // this is not a proper error code used only internally
            $error_description = 'illegal redirect_uri: ' . $redirect_uri;
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

$stateId = SimpleSAML_Auth_State::saveState($responseParameters, 'oauth2server:error');

$error_uri = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Module::getModuleURL('oauth2server/error.php'),
    array('stateId' => $stateId));

SimpleSAML_Utilities::redirect($error_uri);
