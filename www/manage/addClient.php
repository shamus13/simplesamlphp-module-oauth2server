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

$id = $as->getAttributes()[$idAttribute][0];

$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

$globalConfig = SimpleSAML_Configuration::getInstance();

$scopes = $config->getValue('scopes', array());

if (array_key_exists('clientId', $_REQUEST)) {
    $temp = $clientStore->getClient($_REQUEST['clientId']);

    if (!is_null($temp) && $temp['owner'] === $id) {
        $client = $temp;
    }
}

if (!isset($client)) {
    $client = array(
        'id' => 'CL' . substr(SimpleSAML_Utilities::generateID(), 1),
        'redirect_uri' => '',
        'description' => '',
        'scope' => array(),
        'owner' => $id,
        'expire' => time() + 1234,
    );
}

if (isset($_POST['uris'])) {
    $client['redirect_uri'] = explode(PHP_EOL, trim($_POST['uris']));
}

if (isset($_POST['availableScopes'])) {
    $client['scope'] = array_intersect($_POST['availableScopes'], array_keys($scopes));
}

if (isset($_POST['clientDescription'])) {
    $client['description'] = trim($_POST['clientDescription']);
}

if (isset($_POST['expire'])) {
    $client['expire'] = $_POST['expire'];
}

if (isset($_POST['password'])) {
    if(strlen(trim($_POST['password'])) > 0) {
       $client['password'] = trim($_POST['password']);
    } else {
        unset($client['password']);
    }
}

if (isset($_POST['alternativePassword'])) {
    if(strlen(trim($_POST['alternativePassword'])) > 0) {
       $client['alternative_password'] = trim($_POST['alternativePassword']);
    } else {
        unset($client['alternative_password']);
    }
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:manage/addClient.php');

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


$t->data['id'] = $client['id'];
$t->data['scopes'] = $scopeMap;
$t->data['uris'] = $client['redirect_uri'];
$t->data['owner'] =  $id;
$t->data['expire'] = isset($client['expire']) ? $client['expire'] : 0;

$t->data['password'] = isset($client['password']) ? $client['password'] : '';

$t->data['alternativePassword'] = isset($client['alternative_password']) ? $client['alternative_password'] : '';

$t->data['clientDescription'] = isset($client['description']) ? $client['description'] : '';

$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/manage/addClient.php');

$t->show();
