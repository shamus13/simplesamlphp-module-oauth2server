<?php

session_cache_limiter('nocache');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$as = new SimpleSAML_Auth_Simple($config->getValue('authsource'));

$as->requireAuth();

$state = SimpleSAML_Auth_State::loadState($_REQUEST['stateId'], 'oauth2server:consent');

$globalConfig = SimpleSAML_Configuration::getInstance();

$authorizationCodeFactory =
    new sspmod_oauth2server_OAuth2_TokenFactory(
        $config->getValue('authorization_code_time_to_live', 300),
        $config->getValue('access_token_time_to_live', 300)
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

    SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($state['redirectUri'], $response));
} else if (array_key_exists('deny', $_REQUEST)) {

    $errorState = array('error' => 'access_denied',
        'error_description' => 'request denied by resource owner');

    $stateId = SimpleSAML_Auth_State::saveState($errorState, 'oauth2server:error');

    $error_uri = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Module::getModuleURL('oauth2server/error.php'),
        array('stateId' => $stateId));

    SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($state['redirectUri'],
        array('error' => $errorState['error'], 'error_description' => $errorState['error_description'],
            'error_uri' => $error_uri)));
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:consent.php');

$t->data['stateId'] = $_REQUEST['stateId'];
$t->data['clientId'] = $state['clientId'];
$t->data['scopes'] = $state['requestedScopes'];
$t->data['id'] = $codeEntry['id'];
$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/consent.php');

$t->show();
