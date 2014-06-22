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

    //Definition of available scopes and descriptions
    'scopes' => array(
        'USER_ID' => array(
            'en' => 'Can read the user id',
        ),
        'USER_NAME' => array(
            'en' => 'Can read name attributes',
        ),
        'USER_AFFILIATION' => array(
            'en' => 'Can read user affiliation',
        ),
        'FULL_ACCESS' => array(
            'en' => 'Can read all attributes',
        ),
    ),

    //Definition of static oauth2 clients
    'clients' => array(
        'client_id' => array(
            'redirect_uri' => array('uri1', 'uri2'), // Registered redirection end points. Allow any query parameters.
            'scope' => array('scope1', 'scope2'), // Available scopes for this client. No default scope exists.
            'password' => 'password', // Optional password to be used for basic authentication of clients.
            'alternative_password' => 'new_password', // Optional alternative password used for graceful password changes.
            'description' => array( // Description of what the client does and why it should be granted scopes.
                'en' => 'OAuth2 Test Client',
            ),
        ),
    ),

    //Configuration of dynamic clients
    'enable_client_registration' => false, //allow users to register clients, default false
    'client_grace_period' => 30 * 24 * 60 * 60, //grace period in seconds before unused clients expire, default 30 days

    //Authorization server properties
    'user_id_attribute' => 'eduPersonPrincipalName',

    'authorization_code_time_to_live' => 300, // default life span of 300 seconds
    'refresh_token_time_to_live' => 3600, // default life span of 3600 seconds
    'access_token_time_to_live' => 300, // default life span of 300 seconds

    //resourceOwner properties.
    'enable_resource_owner_service' => false, // allow clients to retrieve attributes, defaults to false
    'resource_owner_service_attribute_scopes' => array(
        'USER_ID' => array('eduPersonPrincipalName'), // single named attribute
        'USER_NAME' => array('cn', 'gn', 'sn'), // multiple named attributes
        'USER_AFFILIATION' => array('eduPersonScopeAffiliation'),
        'FULL_ACCESS' => null, // all attributes
    ),
);
