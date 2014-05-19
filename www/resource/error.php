<?php

session_cache_limiter('nocache');

$globalConfig = SimpleSAML_Configuration::getInstance();

$t = new SimpleSAML_XHTML_Template($globalConfig, 'oauth2server:authorization/error.php');

$t->data['error'] = 'server_error';
$t->data['errorDescription'] = 'no description';

if (array_key_exists('error', $_REQUEST)) {
    $t->data['error'] = $_REQUEST['error'];
}

if (array_key_exists('errorDescription', $_REQUEST)) {
    $t->data['errorDescription'] = $_REQUEST['errorDescription'];
}

$t->show();
