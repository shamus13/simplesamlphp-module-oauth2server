<?php
/*
 * Configuration for the module oauth2server.
 *
 */

$config = array(

    'authsource' => 'oauth',

    /*
    'store' => array(
        'class' => 'oauth2server:SQLStore',
        //'dsn' => 'pgsql:host=localhost;port=5432;dbname=name',
        'dsn' => 'sqlite:/path/to/sqlitedatabase.sq3',
        'username' => 'user',
        'password' => 'password'
    ),
    */

    /*
    'store' => array(
        'class' => 'oauth2server:LDAPStore',

        'url' => 'ldap://example.com/',
        'tls' => true,
        'username' => 'cn=Manager,dc=example,dc=com',
        'password' => 'secret',
        'base' => 'dc=store,dc=example,dc=com'
        'deref' => LDAP_DEREF_NEVER, // or one of LDAP_DEREF_SEARCHING, LDAP_DEREF_FINDING, LDAP_DEREF_ALWAYS
    ),
    */
    'store' => array(
        'class' => 'oauth2server:MemCacheTicketStore',
        'prefix' => 'some_prefix',
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

    //Definition of oauth2 resource servers
    'resources' => array(
        'resource_id' => array(
            'password' => 'password', // Password to be used for basic authentication of clients.
            'alternative_password' => 'new_password', // Optional alternative password used for graceful password changes.
        )
    ),

    //Definition of static oauth2 clients
    'clients' => array(
        'client_id' => array( // standard oauth2 client
            'redirect_uri' => array('uri1', 'uri2'), // Registered redirection end points. Allow any query parameters.
            'scope' => array('scope1', 'scope2'), // Available scopes for this client. No default scope exists.
            'scopeRequired' => array('scope1'), // Mandatory scopes for this client. Defaults to none.
            'password' => 'password', // Optional password to be used for basic authentication of clients.
            'alternative_password' => 'new_password', // Optional alternative password used for graceful password changes.
            'description' => array( // Description of what the client does and why it should be granted scopes.
                'en' => 'OAuth2 Test Client',
            ),
            'IDPList' => array( // Optional list of entity id's of IdP's to used for scoping during the wayf step.
                'entityID1',    // Only applicable to SAML2 authsources.
                'entityID2',
            ),
        ),
        'android' => array( // android oauth2 client using intent redirect uri for apps relying on an external browser
            'redirect_uri' => array('intent://example.com/#Intent;scheme=oauth2;package=com.examle.oauth2_app;end'),
            'scope' => array('USER_ID', 'USER_NAME'), // Available scopes for this client. No default scope exists.
            'scopeRequired' => array(), // Mandatory scopes for this client. Defaults to none.
            'password' => 'password', // Optional password to be used for basic authentication of confidential clients.
            'description' => array(
                'en' => 'OAuth2 test app',
                'da' => 'Oauth2 test app',
            ),
        ),
    ),

    //Configuration of dynamic clients
    'enable_client_registration' => false, //allow users to register clients, default false
    'client_grace_period' => 30 * 24 * 60 * 60, //grace period in seconds before unused clients expire, default 30 days

    //Authorization server properties
    'user_id_attribute' => 'eduPersonPrincipalName',

    'authorization_code_time_to_live' => 300, // default life span of 300 seconds
    'refresh_token_time_to_live' => array( // preselects first entry, no default defined
        3600 => array('en' => 'an hour'),
        300 => array('en' => '5 minutes'),
        (24 * 3600) => array('en' => 'a day'),
        (30 * 24 * 3600) => array('en' => 'a month'),
        (365 * 24 * 3600) => array('en' => 'a year')
    ),
    'access_token_time_to_live' => 300, // default life span of 300 seconds

    //resourceOwner properties.
    'enable_resource_owner_service' => false, // allow clients to retrieve attributes, defaults to false
    'resource_owner_service_attribute_scopes' => array(
        'USER_ID' => array('eduPersonPrincipalName'), // single named attribute
        'USER_NAME' => array('cn', 'gn', 'sn'), // multiple named attributes
        'USER_AFFILIATION' => array('eduPersonScopeAffiliation'),
        'FULL_ACCESS' => null, // all attributes
    ),

    //proxy service properties
    'proxy_end_points' => array(
        array(
            'path' => '/a/{x}/b/{y}',
            'target' => 'http://example.com:1234/abc/{attributeName}/def/{y}/{x}',
            'scope_required' => array(
                'DELETE' => array('X', 'Y'),
                'GET' => array(),
                'POST' => array('A', 'B'),
                'PUT' => array('A', 'B'),
            ),
            'additional_headers' => array(
                'realm' => 'acme',
                'user-id' => '{eduPersonPrincipalName}'
            ),
        ),
    ),
);
