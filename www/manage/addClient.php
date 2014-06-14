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

$storeConfig = $config->getValue('store');
$storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');

$globalConfig = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:manage/addClient.php');

$scopes = $config->getValue('scopes', array());

foreach ($scopes as $scope => $translations) {
    $t->includeInlineTranslation('{oauth2server:oauth2server:' . $scope . '}', $translations);
}

$idAttribute = $config->getValue('user_id_attribute', 'eduPersonScopedAffiliation');

$id = $as->getAttributes()[$idAttribute][0];

$scopeMap = array();

foreach ($scopes as $scope => $translations) {
    $scopeMap[$scope] = false;
}

if (isset($_REQUEST['availableScopes'])) {
    foreach ($_REQUEST['availableScopes'] as $scope) {
//        if (array_key_exists($scope, $scopeMap)) {
            $scopeMap[$scope] = true;
//        }
    }
}

$t->data['id'] = isset($_REQUEST['clientId']) ? $_REQUEST['clientId'] : '';
$t->data['scopes'] = $scopeMap;
$t->data['uris'] = isset($_REQUEST['uris']) ? $_REQUEST['uris'] : '';
$t->data['owner'] = $id;
$t->data['expire'] = isset($_REQUEST['expire']) ? $_REQUEST['expire'] : 0;

$t->data['password'] = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';

$t->data['alternativePassword'] = isset($_REQUEST['alternativePassword']) ? $_REQUEST['alternativePassword'] : '';

$t->data['clientDescription'] = isset($_REQUEST['clientDescription']) ? $_REQUEST['clientDescription'] : '';

$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/manage/addClient.php');

$t->show();
