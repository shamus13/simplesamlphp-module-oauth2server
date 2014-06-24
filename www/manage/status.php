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

$tokenStore = new sspmod_oauth2server_OAuth2_TokenStore($config);
$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);
$userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

$user = $userStore->getUser($as->getAttributes()[$idAttribute][0]);

$globalConfig = SimpleSAML_Configuration::getInstance();

$authorizationCodes = array();
$refreshTokens = array();
$accessTokens = array();
$clients = array();

if (!is_null($user)) {
    $liveAuthorizationCodes = array();
    foreach ($user['authorizationCodes'] as $id) {
        $token = $tokenStore->getAuthorizationCode($id);

        if (!is_null($token)) {
            if (isset($_REQUEST['tokenId']) && $id === $_REQUEST['tokenId']) {
                $tokenStore->removeAuthorizationCode($id);
            } else {
                array_push($authorizationCodes, $token);
                array_push($liveAuthorizationCodes, $token['id']);
            }
        }
    }

    $liveRefreshTokens = array();
    foreach ($user['refreshTokens'] as $id) {
        $token = $tokenStore->getRefreshToken($id);

        if (!is_null($token)) {
            if (isset($_REQUEST['tokenId']) && $id === $_REQUEST['tokenId']) {
                $tokenStore->removeRefreshToken($id);
            } else {
                array_push($refreshTokens, $token);
                array_push($liveRefreshTokens, $token['id']);
            }
        }
    }

    $liveAccessTokens = array();
    foreach ($user['accessTokens'] as $id) {
        $token = $tokenStore->getAccessToken($id);

        if (!is_null($token)) {
            if (isset($_REQUEST['tokenId']) && $id === $_REQUEST['tokenId']) {
                $tokenStore->removeAuthorizationCode($id);
            } else {
                array_push($accessTokens, $token);
                array_push($liveAccessTokens, $token['id']);
            }
        }
    }

    $liveClients = array();
    foreach ($user['clients'] as $id) {
        $client = $clientStore->getClient($id);

        if (!is_null($client)) {
            if (isset($_REQUEST['clientId']) && $id === $_REQUEST['clientId']) {
                $clientStore->removeClient($id);
            } else {
                array_push($clients, $client);
                array_push($liveClients, $client['id']);
            }
        }
    }

    if (count($liveAuthorizationCodes) != count($user['authorizationCodes']) ||
        count($liveRefreshTokens) != count($user['refreshTokens']) ||
        count($liveAccessTokens) != count($user['accessTokens']) ||
        count($liveClients) != count($user['clients'])
    ) {
        $user['authorizationCodes'] = $liveAuthorizationCodes;
        $user['refreshTokens'] = $liveRefreshTokens;
        $user['accessTokens'] = $liveAccessTokens;
        $user['clients'] = $liveClients;

        $userStore->updateUser($user);
    }
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:manage/status.php');

$t->data['authorizationCodes'] = $authorizationCodes;
$t->data['refreshTokens'] = $refreshTokens;
$t->data['accessTokens'] = $accessTokens;

if ($config->getValue('enable_client_registration', false)) {
    $t->data['clients'] = $clients;

    foreach ($clients as $client) {
        $t->includeInlineTranslation('{oauth2server:oauth2server:client_description_' . $client['id'] . '}',
            $client['description']);
    }
}

$t->data['statusForm'] = SimpleSAML_Module::getModuleURL('oauth2server/manage/status.php');
$t->data['tokenForm'] = SimpleSAML_Module::getModuleURL('oauth2server/manage/token.php');
$t->data['clientForm'] = SimpleSAML_Module::getModuleURL('oauth2server/manage/client.php');

$t->show();
