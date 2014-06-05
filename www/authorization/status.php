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

$user = $tokenStore->getUser($as->getAttributes()[$idAttribute][0]);

$globalConfig = SimpleSAML_Configuration::getInstance();

$tokens = array();

foreach($user['authorizationCodes'] as $id) {
    $token = $tokenStore->getAuthorizationCode($id);

    if(!is_null($token)) {
        array_push($tokens, $token);
    }
}

foreach($user['refreshTokens'] as $id) {
    $token = $tokenStore->getRefreshToken($id);

    if(!is_null($token)) {
        array_push($tokens, $token);
    }
}

foreach($user['accessTokens'] as $id) {
    $token = $tokenStore->getAccessToken($id);

    if(!is_null($token)) {
        array_push($tokens, $token);
    }
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:authorization/status.php');

$t->data['tokens'] = $tokens;
$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/authorization/status.php');

$t->show();
