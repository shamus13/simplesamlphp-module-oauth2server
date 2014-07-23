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

$clientStore = new sspmod_oauth2server_OAuth2_ClientStore($config);

$client = $clientStore->getClient($state['clientId']);

$refreshTokenTTLs = $config->getValue('refresh_token_time_to_live');

if (empty($refreshTokenTTLs)) {
    array_push($refreshTokenTTLs, 3600);
}

if (array_key_exists('grant', $_REQUEST)) {

    if (array_key_exists('ttl', $_REQUEST) && array_key_exists($_REQUEST['ttl'], $refreshTokenTTLs)) {
        $refreshTokenTTL = $_REQUEST['ttl'];
    } else {
        $refreshTokenTTL = $refreshTokenTTLs[array_keys($refreshTokenTTLs)[0]];
    }

    $authorizationCodeFactory =
        new sspmod_oauth2server_OAuth2_TokenFactory(
            $config->getValue('authorization_code_time_to_live', 300),
            $config->getValue('access_token_time_to_live', 300),
            $refreshTokenTTL
        );

    $idAttribute = $config->getValue('user_id_attribute', 'eduPersonScopedAffiliation');

    $codeEntry = $authorizationCodeFactory->createAuthorizationCode($state['clientId'],
        $state['redirectUri'], array(), $as->getAttributes()[$idAttribute][0]);

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

    $codeEntry['scopes'] = array_intersect($state['requestedScopes'], $scopesTemp);

    $tokenStore = new sspmod_oauth2server_OAuth2_TokenStore($config);

    $tokenStore->addAuthorizationCode($codeEntry);

    $userStore = new sspmod_oauth2server_OAuth2_UserStore($config);

    if (isset($client['expire'])) {
        $clientGracePeriod = $config->getValue('client_grace_period', 30 * 24 * 60 * 60);

        $now = time();

        if ($client['expire'] < $now + $clientGracePeriod / 2) {
            $client['expire'] = $now + $clientGracePeriod;

            $clientStore->updateClient($client);
        }
    }

    $user = $userStore->getUser($codeEntry['userId']);

    if (is_array($user)) {
        $user['attributes'] = $as->getAttributes();

        $liveTokens = array($codeEntry['id']);

        foreach ($user['authorizationCodes'] as $tokenId) {
            if (!is_null($tokenStore->getAuthorizationCode($tokenId))) {
                array_push($liveTokens, $tokenId);
            }
        }

        $user['authorizationCodes'] = $liveTokens;

        if ($codeEntry['expire'] > $user['expire']) {
            $user['expire'] = $codeEntry['expire'];
        }

        if (isset($client['expire']) && $client['expire'] > $user['expire']) {
            $user['expire'] = $client['expire'];
        }

        $userStore->updateUser($user);
    } else {
        $expire = isset($client['expire']) && $client['expire'] > $codeEntry['expire'] ?
            $client['expire'] : $codeEntry['expire'];

        $userStore->addUser(array('id' => $codeEntry['userId'], 'attributes' => $as->getAttributes(),
            'authorizationCodes' => array($codeEntry['id']), 'refreshTokens' => array(), 'accessTokens' => array(),
            'clients' => array(), 'expire' => $expire));
    }

    $response = array('code' => $codeEntry['id']);

    if (array_key_exists('state', $state)) {
        $response['state'] = $state['state'];
    }

    // build redirect uri

    $url = $state['returnUri'];

    $fragmentStart = strpos($url, '#');

    if ($fragmentStart !== FALSE) { //strip fragment if any
        $fragment = substr($url, $fragmentStart);
        $url = substr($url, 0, $fragmentStart);
    } else {
        $fragment = '';
    }

    $queryStart = strpos($url, '?');

    if ($queryStart !== FALSE) { //strip query if any
        $query = SimpleSAML_Utilities::parseQueryString(substr($url, $queryStart + 1));
        $url = substr($url, 0, $queryStart);
    } else {
        $query = array();
    }

    $query = array_merge($query, $response);
    $url .= '?' . http_build_query($query, '', '&') . $fragment;

    /* Set the HTTP result code. This is either 303 See Other or
     * 302 Found. HTTP 303 See Other is sent if the HTTP version
     * is HTTP/1.1 and the request type was a POST request.
     */
    if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
        $_SERVER['REQUEST_METHOD'] === 'POST'
    ) {
        $code = 303;
    } else {
        $code = 302;
    }

    if (strlen($url) > 2048) {
        SimpleSAML_Logger::warning('Redirecting to a URL longer than 2048 bytes.');
    }

    /* Set the location header. */
    header('Location: ' . $url, TRUE, $code);

    /* Disable caching of this response. */
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');

    /* Show a minimal web page with a clickable link to the URL. */
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' .
        ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
    echo '<html xmlns="http://www.w3.org/1999/xhtml">';
    echo '<head>
  					<meta http-equiv="content-type" content="text/html; charset=utf-8">
  					<title>Redirect</title>
  				</head>';
    echo '<body>';
    echo '<h1>Redirect</h1>';
    echo '<p>';
    echo 'You were redirected to: ';
    echo '<a id="redirlink" href="' .
        htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
    echo '<script type="text/javascript">document.getElementById("redirlink").focus();</script>';
    echo '</p>';
    echo '</body>';
    echo '</html>';

    /* End script execution. */
    exit;

} else if (array_key_exists('deny', $_REQUEST)) {

    $errorState = array('error' => 'access_denied',
        'error_description' => 'request denied by resource owner',
        'error_code_internal' => 'CONSENT_NOT_GRANTED',
        'error_parameters_internal' => array(),
    );

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

foreach ($refreshTokenTTLs as $ttl => $translations) {
    $t->includeInlineTranslation('{oauth2server:oauth2server:ttl_' . $ttl . '}', $translations);
}

$t->data['ttlChoices'] = array_keys($refreshTokenTTLs);
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
