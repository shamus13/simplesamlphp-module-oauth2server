<?php

session_cache_limiter('nocache');

$config = SimpleSAML_Configuration::getConfig('module_oauth2server.php');

$as = new SimpleSAML_Auth_Simple($config->getValue('authsource'));

$as->requireAuth();

$state = SimpleSAML_Auth_State::loadState($_REQUEST['stateId'], 'oauth2server:consent');

$globalConfig = SimpleSAML_Configuration::getInstance();

SimpleSAML_Logger::debug('oauth2server:' . var_export($_REQUEST, true));

$authorizationCodeFactory =
    new sspmod_oauth2server_OAuth2_AuthorizationCodeFactory($config->getValue('authorization_code_time_to_live', 300));

$codeEntry = $authorizationCodeFactory->createCode($state['clientId'],
    $state['redirectUri'], $state['requestedScopes'], $as->getAttributes());

if (false) {
    //everything is good, so we create a grant and redirect

    $storeConfig = $config->getValue('store');
    $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
    $store = new $storeClass($storeConfig);

    $store->addAuthorizationCode($codeEntry);

    $responseParameters = array('code' => $codeEntry['id']);

    if(array_key_exists('state', $state)) {
        $responseParameters['state'] = $state['state'];
    }

    SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::addURLparameter($state['redirectUri'], $responseParameters));
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:consent.php');

$t->data['stateId'] = $_REQUEST['stateId'];
$t->data['clientId'] = $state['clientId'];
$t->data['scopes'] = $state['requestedScopes'];
$t->data['id'] = $codeEntry['id'];
$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/consent.php');

$t->show();
