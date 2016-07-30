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

$state = SimpleSAML_Auth_State::loadState($_REQUEST['stateId'], 'oauth2server:authorization/consent');

$globalConfig = SimpleSAML_Configuration::getInstance();

$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

$client = $clientStore->getClient($state['clientId']);

$as = new SimpleSAML_Auth_Simple($config->getValue('authsource'));

$params = sspmod_oauth2server_Utility_Uri::calculateScopingParameters($client);

$as->requireAuth($params);

$authorizationCodeTTL = $config->getValue('authorization_code_time_to_live');
$accessTokenTTL = $config->getValue('access_token_time_to_live');
$tokenTTLs = $config->getValue('refresh_token_time_to_live');

if (empty($tokenTTLs)) {
    array_push($tokenTTLs, 3600);
}

if (array_key_exists('grant', $_REQUEST)) {

    if (array_key_exists('ttl', $_REQUEST) && array_key_exists($_REQUEST['ttl'], $tokenTTLs)) {
        $tokenTTL = $_REQUEST['ttl'];
    } else {
        $ttlNames = array_keys($tokenTTLs);

        $tokenTTL = $tokenTTLs[$ttlNames[0]];
    }

    if (isset($client['expire'])) {
        $clientGracePeriod = $config->getValue('client_grace_period', 30 * 24 * 60 * 60);

        $now = time();

        if ($client['expire'] < $now + $clientGracePeriod / 2) {
            $client['expire'] = $now + $clientGracePeriod;

            $clientStore->updateClient($client);
        }
    }


    $idAttribute = $config->getValue('user_id_attribute', 'eduPersonScopedAffiliation');
    $attributes = $as->getAttributes();

    if ($state['response_type'] === 'code') {
        $authorizationCodeFactory = new sspmod_oauth2server_OAuth2_TokenFactory($authorizationCodeTTL,
            $accessTokenTTL, $tokenTTL);
        $token = $authorizationCodeFactory->createAuthorizationCode($state['clientId'],
            $state['redirectUri'], array(), $attributes[$idAttribute][0]);
    } else {
        $authorizationCodeFactory = new sspmod_oauth2server_OAuth2_TokenFactory($authorizationCodeTTL,
            $tokenTTL, $tokenTTL);
        $token = $authorizationCodeFactory->createBearerAccessToken($state['clientId'],
            array(), $attributes[$idAttribute][0]);
    }

    if (isset($_REQUEST['grantedScopes'])) {
        $scopesTemp = $_REQUEST['grantedScopes'];
    } else {
        $scopesTemp = array();
    }

    foreach ($client['scope'] as $scope => $required) {
        if ($required) {
            array_push($scopesTemp, $scope);
        }
    }

    $token['scopes'] = array_intersect($state['requestedScopes'], $scopesTemp);

    $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore($config);

    if ($state['response_type'] === 'code') {
        $tokenStore->addAuthorizationCode($token);
    } else {
        $tokenStore->addAccessToken($token);
    }

    $userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

    $user = $userStore->getUser($token['userId']);

    if (is_array($user)) {
        $user['attributes'] = $as->getAttributes();

        $liveTokens = array($token['id']);

        if ($state['response_type'] === 'code') {
            foreach ($user['authorizationCodes'] as $tokenId) {
                if (!is_null($tokenStore->getAuthorizationCode($tokenId))) {
                    array_push($liveTokens, $tokenId);
                }
            }

            $user['authorizationCodes'] = $liveTokens;
        } else {
            foreach ($user['accessTokens'] as $tokenId) {
                if (!is_null($tokenStore->getAccessToken($tokenId))) {
                    array_push($liveTokens, $tokenId);
                }
            }

            $user['accessTokens'] = $liveTokens;
        }

        if ($token['expire'] > $user['expire']) {
            $user['expire'] = $token['expire'];
        }

        if (isset($client['expire']) && $client['expire'] > $user['expire']) {
            $user['expire'] = $client['expire'];
        }

        $userStore->updateUser($user);
    } else {
        $expire = isset($client['expire']) && $client['expire'] > $token['expire'] ?
            $client['expire'] : $token['expire'];

        $user = array(
            'id' => $token['userId'],
            'attributes' => $as->getAttributes(),
            'authorizationCodes' => array(),
            'refreshTokens' => array(),
            'accessTokens' => array(),
            'clients' => array(),
            'expire' => $expire
        );

        if ($state['response_type'] === 'code') {
            array_push($user['authorizationCodes'], $token['id']);
        } else {
            array_push($user['accessTokens'], $token['id']);
        }

        $userStore->addUser($user);
    }

    if ($state['response_type'] === 'code') {
        $response = array('code' => $token['id']);

        if (array_key_exists('state', $state)) {
            $response['state'] = $state['state'];
        }

        // build return uri with authorization code and redirect

        sspmod_oauth2server_Utility_Uri::redirectUri(sspmod_oauth2server_Utility_Uri::addQueryParametersToUrl($state['returnUri'],
            $response));
    } else {
        $fragment = '#access_token=' . $token['id'] . '&token_type=bearer&expires_in=' . ($token['expire'] - time());

        if (count($token['scopes']) > 0) {
            $fragment .= '&scope=';
            $fragment .= urlencode(trim(implode(' ', $token['scopes'])));
        }

        if (array_key_exists('state', $state)) {
            $fragment .= '&state=';
            $fragment .= $state['state'];
        }

        sspmod_oauth2server_Utility_Uri::redirectUri($state['returnUri'] . $fragment);
    }
} else {
    if (array_key_exists('deny', $_REQUEST)) {

        $errorState = array(
            'error' => 'access_denied',
            'error_description' => 'request denied by resource owner',
            'error_code_internal' => 'CONSENT_NOT_GRANTED',
            'error_parameters_internal' => array(),
        );

        $error_uri = SimpleSAML\Utils\HTTP::addURLParameters(
            SimpleSAML_Module::getModuleURL('oauth2server/authorization/error.php'), $errorState);

        $response = array(
            'error' => $errorState['error'],
            'error_description' => $errorState['error_description'],
            'error_uri' => $error_uri
        );

        if (array_key_exists('state', $state)) {
            $response['state'] = $state['state'];
        }

        sspmod_oauth2server_Utility_Uri::redirectUri(sspmod_oauth2server_Utility_Uri::addQueryParametersToUrl($state['returnUri'],
            $response));
    }
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:authorization/consent.php');

foreach ($config->getValue('scopes', array()) as $scope => $translations) {
    $t->includeInlineTranslation('{oauth2server:oauth2server:' . $scope . '}', $translations);
}

$t->includeInlineTranslation('{oauth2server:oauth2server:client_description}',
    array_key_exists('description', $client) ? $client['description'] : array('' => ''));

$t->data['clientId'] = $state['clientId'];
$t->data['stateId'] = $_REQUEST['stateId'];

$t->data['scopes'] = array();

$scopes = isset($client['scope']) ? $client['scope'] : array();

foreach ($state['requestedScopes'] as $scope) {
    $t->data['scopes'][$scope] = isset($scopes[$scope]) && $scopes[$scope];
}

$t->data['form'] = SimpleSAML_Module::getModuleURL('oauth2server/authorization/consent.php');

foreach ($tokenTTLs as $ttl => $translations) {
    $t->includeInlineTranslation('{oauth2server:oauth2server:ttl_' . $ttl . '}', $translations);
}

$t->data['ttlChoices'] = array_keys($tokenTTLs);
$t->data['ttlDefault'] = $t->data['ttlChoices'][0];
sort($t->data['ttlChoices'], SORT_NUMERIC);

switch (parse_url($state['returnUri'], PHP_URL_SCHEME)) {
    case 'http':
        $t->data['redirection'] = 'insecure';
        break;
    case 'https':
        break;
    default:
        $t->data['redirection'] = 'unknown';
}

$t->show();
