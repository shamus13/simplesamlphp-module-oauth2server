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

if (!$config->getValue('enable_client_registration', false)) {
    throw new SimpleSAML_Error_Error('oauth2server:REGISTRATION_DISABLED');
}

$idAttribute = $config->getValue('user_id_attribute', 'eduPersonScopedAffiliation');

$id = $as->getAttributes()[$idAttribute][0];

$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

$globalConfig = SimpleSAML_Configuration::getInstance();

$scopes = $config->getValue('scopes', array());

if (array_key_exists('clientId', $_REQUEST)) {
    $client = $clientStore->getClient($_REQUEST['clientId']);
}

$clientGracePeriod = $config->getValue('client_grace_period', 30 * 24 * 60 * 60);

if (!isset($client) || !is_array($client)) {
    $client = array(
        'id' => '',
        'redirect_uri' => array(),
        'description' => array('' => ''),
        'scope' => array(),
        'owner' => $id,
        'expire' => time() + $clientGracePeriod,
    );
}

$ownedByMe = isset($client['owner']) && $client['owner'] === $id;

if ($ownedByMe && isset($_POST['update'])) {
    if (isset($_POST['uris'])) {
        $client['redirect_uri'] = explode(PHP_EOL, trim($_POST['uris']));
    }

    if (isset($_POST['availableScopes'])) {
        $client['scope'] = array_intersect($_POST['availableScopes'], array_keys($scopes));
    }

    if (isset($_POST['clientDescription']) && isset($_POST['language'])) {
        $client['description'][$_POST['language']] = trim($_POST['clientDescription']);
    }

    if (isset($_POST['password'])) {
        if (strlen(trim($_POST['password'])) > 0) {
            $client['password'] = trim($_POST['password']);
        } else {
            unset($client['password']);
        }
    }

    if (isset($_POST['alternativePassword'])) {
        if (strlen(trim($_POST['alternativePassword'])) > 0) {
            $client['alternative_password'] = trim($_POST['alternativePassword']);
        } else {
            unset($client['alternative_password']);
        }
    }

    $client['expire'] = time() + $clientGracePeriod;

    $userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

    $user = $userStore->getUser($id);

    if (is_null($user)) {
        $user = array('attributes' => $as->getAttributes(), 'authorizationCodes' => array(),
            'refreshTokens' => array(), 'accessTokens' => array(),
            'clients' => array(), 'expire' => $client['expire']);

        $userModified = true;
    } else {
        $userModified = false;
    }

    if ($client['id'] != '') {
        $clientStore->updateClient($client);
    } else {
        $client['id'] = 'CL' . substr(SimpleSAML_Utilities::generateID(), 1);

        $clientStore->addClient($client);
    }

    if (array_search($client['id'], $user['clients']) === false) {
        array_push($user['clients'], $client['id']);

        $userModified = true;
    }

    if ($client['expire'] - $user['expire'] > $clientGracePeriod / 2) {
        $user['expire'] = $client['expire'];
        $userModified = true;
    }

    if ($userModified) {
        if (isset($user['id'])) {
            $userStore->updateUser($user);
        } else {
            $user['id'] = $id;

            $userStore->addUser($user);
        }
    }
} else if (isset($_POST['cancel'])) {
    SimpleSAML_Utilities::redirect(SimpleSAML_Module::getModuleURL('oauth2server/manage/status.php'));
} else if ($ownedByMe && isset($_POST['delete'])) {
    $clientStore->removeClient($client['id']);

    SimpleSAML_Utilities::redirect(SimpleSAML_Module::getModuleURL('oauth2server/manage/status.php'));
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:manage/client.php');

foreach ($scopes as $scope => $translations) {
    $t->includeInlineTranslation('{oauth2server:oauth2server:' . $scope . '}', $translations);
}

$scopeMap = array();

foreach ($scopes as $scope => $translations) {
    $scopeMap[$scope] = false;
}

foreach ($client['scope'] as $scope) {
    if (array_key_exists($scope, $scopeMap)) {
        $scopeMap[$scope] = true;
    }
}

$t->includeInlineTranslation('{oauth2server:oauth2server:client_description_text}', $client['description']);

$t->data['editable'] = $ownedByMe;
$t->data['id'] = $client['id'];
$t->data['scopes'] = $scopeMap;
$t->data['uris'] = $ownedByMe ? $client['redirect_uri'] : array();
$t->data['owner'] = $id;

if (isset($client['expire'])) {
    $t->data['expire'] = $client['expire'];
}

$t->data['password'] = $ownedByMe && isset($client['password']) ? $client['password'] : '';

$t->data['alternativePassword'] = $ownedByMe && isset($client['alternative_password']) ? $client['alternative_password'] : '';

$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/manage/client.php');

$t->show();


//TODO: handle the new internal scope (name => required) format