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

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$storeConfig = $config->getValue('store');

$storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
$store = new $storeClass($storeConfig);

$authorizationCodeFactory = new AuthorizationCodeFactory($config->getValue('authorization_code_time_to_live', 300));

$clients = $config->getValue('clients', array());

if (isset($_GET['client_id']) && array_key_exists($_GET['client_id'], $clients)) {
    $client = $clients[$_GET['client_id']];

    if (array_key_exists('redirect_uri', $client) &&
        is_array($client['redirect_uri']) &&
        count($client['redirect_uri']) > 0
    ) {
        $redirect_uri = (isset($_GET['redirect_uri'])) ? $_GET['redirect_uri'] : $client['redirect_uri'][0];

        $legalRedirectUri = false; //TODO: we also need to verify, that there is no fragment part in the uri but how?

        foreach ($client['redirection_uri'] as $uri) {
            $legalRedirectUri |= strpos($redirect_uri, $uri) === 0;
        }

        if ($legalRedirectUri) {
            $requestedScopes = (isset($_GET['scope'])) ? $_GET['scope'] : array();
            $definedScopes = (isset($_GET['scope'])) ? $_GET['scope'] : array();

            $invalidScopes = array_diff($requestedScopes, $definedScopes);

            if (count($invalidScopes) == 0) {
                if (!isset($_GET['response_type'])) {
                    $error = 'invalid_request';
                    $error_description = 'missing response type';
                } else if ($_GET['response_type'] != 'code') {
                    $error = 'unsupported_response_type';
                    $error_description = 'unsupported response type: ' . $_GET['response_type'];
                }
            } else {
                $error = 'invalid_scope';
                $error_description = 'invalid scope: ' . $invalidScopes[0];
            }

        } else {
            $error = 'invalid_redirect_uri'; // this is not a proper error code used only internally
            $error_description = 'illegal redirect_uri: ' . $redirect_uri;
        }
    } else {
        $error = 'server_error';
        $error_description = 'no redirection uri associated with client id';
    }
} else if (isset($_GET['client_id'])) {
    $error = 'unauthorized_client';
    $error_description = 'unauthorized_client: ' . $_GET['client_id'];
} else {
    $error = 'missing_client';
    $error_description = 'missing client id';
}

$parameters = array();

if (isset($_REQUEST['state'])) {
    $parameters['state'] = $_REQUEST['state'];
}

if (!is_string($error)) { // do all provided parameters check out?
    $parameters['code'] = $code; // do some stuff to create and return a grant

    $uri = $redirect_uri;
} else { // nope, we have a problem
    $parameters['error'] = $error;
    $parameters['error_description'] = $error_description;

    $stateId = SimpleSAML_Auth_State::saveState($parameters, 'oauth2server:error');

    $error_uri = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Module::getModuleURL('oauth2server/error.php'),
        array('stateId' => $stateId));

    //We have nowhere to redirect the user agent to so send the user agent to an error page
    if ($error === 'missing_client' || $error === 'unauthorized_client' ||
        $error === 'invalid_redirect_uri' || $error === 'server_error'
    ) {
        $uri = $error_uri;
    } else { // we have a valid uri to pass an error code to
        $parameters['error_uri'] = $error_uri;

        $uri = SimpleSAML_Utilities::addURLparameter($redirect_uri, $parameters);
    }
}

SimpleSAML_Utilities::redirect($uri);
