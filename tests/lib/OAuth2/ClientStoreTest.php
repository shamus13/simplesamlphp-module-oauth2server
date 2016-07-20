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
        new \sspmod_oauth2server_OAuth2_ClientStore($this->getDefaultConfiguration());
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testGetRegisteredClient()
    {
        $store = new \sspmod_oauth2server_OAuth2_ClientStore($this->getDefaultConfiguration());

        $client = $store->getClient('client_id');

        $this->assertNotNull($client);
        $this->assertEquals(array('uri1', 'uri2'), $client['redirect_uri']);
        $this->assertEquals(array('scope1' => true, 'scope2' => false), $client['scope']);
        $this->assertEquals('password', $client['password']);
        $this->assertEquals('new_password', $client['alternative_password']);
        $this->assertEquals(array('en' => 'OAuth2 Test Client'), $client['description']);
        $this->assertEquals(array('entityID1', 'entityID2'), $client['IDPList']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testGetMinimalRegisteredClient()
    {
        $store = new \sspmod_oauth2server_OAuth2_ClientStore($this->getDefaultConfiguration());

        $client = $store->getClient('minimal');

        $this->assertNotNull($client);
        $this->assertEquals(array('uri'), $client['redirect_uri']);
        $this->assertEquals(array(), $client['scope']);
        $this->assertEquals('password', $client['password']);
        $this->assertEquals(array('en' => 'OAuth2 Test Client'), $client['description']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testGetMinimalNonexistentClient()
    {
        $store = new \sspmod_oauth2server_OAuth2_ClientStore($this->getDefaultConfiguration());

        $client = $store->getClient('unknown');

        $this->assertNull($client);
    }

    /**
     * @return \SimpleSAML_Configuration
     */
    private function getDefaultConfiguration()
    {
        $config = new \SimpleSAML_Configuration(array(
            'store' => array(
                'class' => 'oauth2server:MockStore',
            ),
            'scopes' => array(
                'scope1' => array(
                    'en' => 'magic scope one',
                ),
                'scope2' => array(
                    'en' => 'magic scope two',
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
                'minimal' => array(
                    'redirect_uri' => array('uri'),
                    'password' => 'password',
                    'description' => array(
                        'en' => 'OAuth2 Test Client',
                    ),
                ),
            ),

            'enable_client_registration' => false,
        ), 'test');

        return $config;
    }
}