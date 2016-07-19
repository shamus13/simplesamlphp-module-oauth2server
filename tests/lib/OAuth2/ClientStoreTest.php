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

namespace SimpleSAML\Oauth2Server\OAuth2;

class sspmod_oauth2server_OAuth2_ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     * @group oauth2
     */
    public function testConstructor()
    {
        $config = new \SimpleSAML_Configuration(array(
            'store' => array(
                'class' => 'oauth2server:MockStore',
            ),
            'scopes' => array(
                'USER_ID' => array(
                    'en' => 'Can read the user id',
                ),
                'FULL_ACCESS' => array(
                    'en' => 'Can read all attributes',
                ),
            ),
            'clients' => array(
                'client_id' => array(
                    'redirect_uri' => array('uri1', 'uri2'),
                    'scope' => array('scope1', 'scope2'),
                    'scopeRequired' => array('scope1'),
                    'password' => 'password',
                    'alternative_password' => 'new_password',
                    'description' => array(
                        'en' => 'OAuth2 Test Client',
                    ),
                    'IDPList' => array(
                        'entityID1',
                        'entityID2',
                    ),
                ),
            ),

            'enable_client_registration' => false,
        ), 'test');

        new \sspmod_oauth2server_OAuth2_ClientStore($config);
    }
}