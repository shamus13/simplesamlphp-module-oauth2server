<?php

//request parameters
$response_type = 0;
$client_id = 0;
$redirect_uri = 0;
$scope = 0;

//response parameters
$code = 0;

//error parameters

$error = 'invalid_request/unauthorized_client/access_denied/unsupported_response_type/invalid_scope/server_error/temporarily_unavailable/';
$error_description = 'brief description of the error';
$error_uri = 'page with detailed error description';

//redirect unless redirect uri is "bad"
$parameters = array();

if (isset($_REQUEST['state'])) {
    $parameters['state'] = $_REQUEST['state'];
}

if (is_string($error)) {
    $state = array('error' => $error);

    $stateId = SimpleSAML_Auth_State::saveState($state, 'oauth2server:error');

    $parameters['stateId'] = $stateId;

    $uri = SimpleSAML_Module::getModuleURL('oauth2server/error.php');
} else {
    $parameters['code'] = $code;

    $uri = $redirect_uri;
}

SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($uri, $parameters));
