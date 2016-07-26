<?php

$config = array(

    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ),

    'oauth' => array(
        'exampleauth:Static',
        'uid' => array('testuser'),
        'eduPersonAffiliation' => array('member', 'employee'),
        'cn' => array('Test User'),
    ),
);
