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

$idAttribute = $config->getValue('user_id_attribute', 'eduPersonScopedAffiliation');

$storeConfig = $config->getValue('store');
$storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');
$tokenStore = new sspmod_oauth2server_OAuth2_TokenStore(new $storeClass($storeConfig));

$userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

$user = $userStore->getUser($as->getAttributes()[$idAttribute][0]);

if (!is_null($user) && isset($_REQUEST['tokenId'])) {
    if (array_search($_REQUEST['tokenId'], $user['authorizationCodes']) !== false) {
        $token = $tokenStore->getAuthorizationCode($_REQUEST['tokenId']);
    } else if (array_search($_REQUEST['tokenId'], $user['refreshTokens']) !== false) {
        $token = $tokenStore->getRefreshToken($_REQUEST['tokenId']);
    } else if (array_search($_REQUEST['tokenId'], $user['accessTokens']) !== false) {
        $token = $tokenStore->getAccessToken($_REQUEST['tokenId']);
    }
}

$globalConfig = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:manage/token.php');

foreach ($config->getValue('scopes', array()) as $scope => $translations) {
    $t->includeInlineTranslation('{oauth2server:oauth2server:' . $scope . '}', $translations);
}

if (isset($token)) {
    $clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

    $client = $clientStore->getClient($token['clientId']);

    if (!is_null($client)) {
        $t->data['token'] = $token;
    }
}

$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/manage/status.php');

$t->show();
