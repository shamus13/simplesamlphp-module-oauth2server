<?php

session_cache_limiter('nocache');

$globalConfig = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:error.php');

if (array_key_exists('stateId', $_REQUEST)) {
    $state = SimpleSAML_Auth_State::loadState($_REQUEST['stateId'], 'oauth2server:error');

    $t->data['error'] = $state['error'];
} else {
    $t->data['error'] = 'server_error';
}

$t->show();
