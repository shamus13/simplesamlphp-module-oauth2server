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
*/

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

$idAttribute = $config->getValue('user_id_attribute', 'eduPersonScopedAffiliation');

$codeEntry = $authorizationCodeFactory->createCode($state['clientId'],
    $state['redirectUri'], array(), $as->getAttributes()[$idAttribute][0]);

if (array_key_exists('grant', $_REQUEST)) {
    if(isset($_REQUEST['grantedScopes'])) {
        $codeEntry['scopes'] = array_intersect($state['requestedScopes'], $_REQUEST['grantedScopes']);
    } else {
        $codeEntry['scopes'] = array();
    }

    $storeConfig = $config->getValue('store');
    $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
    $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore(new $storeClass($storeConfig));

    $tokenStore->addAuthorizationCode($codeEntry);

    $user = $tokenStore->getUser($codeEntry['userId']);

    if (is_array($user)) {
        $user['attributes'] = $as->getAttributes();

        $liveTokens = array($codeEntry['id']);

        foreach($user['authorizationCodes'] as $tokenId) {
            if(!is_null($tokenStore->getAuthorizationCode($tokenId))) {
                array_push($liveTokens, $tokenId);
            }
        }

        $user['authorizationCodes'] = $liveTokens;

        if ($codeEntry['expire'] > $user['expire']) {
            $user['expire'] = $codeEntry['expire'];
        }

        $tokenStore->updateUser($user);
    } else {
        $tokenStore->addUser(array('id' => $codeEntry['userId'], 'attributes' => $as->getAttributes(),
            'authorizationCodes' => array($codeEntry['id']), 'refreshTokens' => array(), 'accessTokens' => array(),
            'expire' => $codeEntry['expire']));
    }

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

foreach ($config->getValue('scopes', array()) as $scope => $translations) {
    $t->includeInlineTranslation('{oauth2server:oauth2server:' . $scope . '}', $translations);
}

$t->data['stateId'] = $_REQUEST['stateId'];
$t->data['clientId'] = $state['clientId'];
$t->data['scopes'] = $state['requestedScopes'];
$t->data['id'] = $codeEntry['id'];
$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/authorization/consent.php');

$t->show();


//TODO: add support for warning about non https redirection end points
//TODO: add support for choosing refresh token time to live