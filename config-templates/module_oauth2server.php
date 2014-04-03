<?php
/*
 * Configuration for the module oauth2server.
 *
 */

$config = array(

    'authsource' => 'oauth',

    'store' => array(
        'class' => 'oauth2server:SQLStore',
        //'dsn' => 'pgsql:host=localhost;port=5432;dbname=name',
        'dsn' => 'sqlite:/path/to/sqlitedatabase.sq3',
        'username' => 'user',
        'password' => 'password'
    ),

    'clients' => array(
        'client_id' => array(
            'redirect_uri' => array('uri1', 'uri2'), // Registered redirection end points. Allow any query parameters.
            'scope' => array('scope1', 'scope2'), // Available scopes for this client. No default scope exists.
        ),
    ),

    //Authorization code properties.
    'authorization_code_time_to_live' => 300, // default life span of 300 seconds
);
