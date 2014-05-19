<?php

session_cache_limiter('nocache');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$as = new SimpleSAML_Auth_Simple($config->getValue('authsource'));

$as->requireAuth();

$state = SimpleSAML_Auth_State::loadState($_REQUEST['stateId'], 'oauth2server:authorization/consent');

$globalConfig = SimpleSAML_Configuration::getInstance();

$authorizationCodeFactory =
    new sspmod_oauth2server_OAuth2_TokenFactory(
        $config->getValue('authorization_code_time_to_live', 300),
        $config->getValue('access_token_time_to_live', 300),
        $config->getValue('refresh_token_time_to_live', 3600)
    );

$codeEntry = $authorizationCodeFactory->createCode($state['clientId'],
    $state['redirectUri'], array(), $as->getAttributes());

if (array_key_exists('grant', $_REQUEST)) {
    $codeEntry['scopes'] = array_intersect($state['requestedScopes'], $_REQUEST['grantedScopes']);

    $storeConfig = $config->getValue('store');
    $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
    $store = new $storeClass($storeConfig);

    $store->addAuthorizationCode($codeEntry);

    $response = array('code' => $codeEntry['id']);

    if (array_key_exists('state', $state)) {
        $response['state'] = $state['state'];
    }

    SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($state['returnUri'], $response));
} else if (array_key_exists('deny', $_REQUEST)) {

    $errorState = array('error' => 'access_denied',
        'error_description' => 'request denied by resource owner');

    $error_uri = SimpleSAML_Utilities::addURLparameter(
        SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'), $errorState);

    $response = array('error' => $errorState['error'], 'error_description' => $errorState['error_description'],
        'error_uri' => $error_uri);

    if (array_key_exists('state', $state)) {
        $response['state'] = $state['state'];
    }

    SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($state['returnUri'], $response));
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:authorization/consent.php');

$t->data['stateId'] = $_REQUEST['stateId'];
$t->data['clientId'] = $state['clientId'];
$t->data['scopes'] = $state['requestedScopes'];
$t->data['id'] = $codeEntry['id'];
$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/authorization/consent.php');

$t->show();


//TODO: add support for warning about non https redirection end points
//TODO: add support for choosing refresh token time to live