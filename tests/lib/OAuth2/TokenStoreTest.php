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

class sspmod_oauth2server_OAuth2_TokenStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     * @group oauth2
     */
    public function testConstructor()
    {
        new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testAddAuthorizationCode()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $code1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addAuthorizationCode($code1);

        $code2 = $store->getAuthorizationCode($code1['id']);

        $this->assertNotNull($code2);
        $this->assertEquals($code1['id'], $code2['id']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testGetNonExistentAuthorizationCode()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $code = $store->getAuthorizationCode('id');

        $this->assertNull($code);
    }

    /**
     * @group unit
     * @group oauth2
     * @expectedException \SimpleSAML_Error_Error
     * @expectedExceptionCode -1
     */
    public function testAddAlreadyAddedAuthorizationCode()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $code = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addAuthorizationCode($code);
        $store->addAuthorizationCode($code);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testRemoveAuthorizationCode()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $code1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addAuthorizationCode($code1);

        $code2 = $store->getAuthorizationCode($code1['id']);

        $this->assertNotNull($code2);
        $this->assertEquals($code1['id'], $code2['id']);

        $store->removeAuthorizationCode($code2['id']);

        $code3 = $store->getAuthorizationCode($code2['id']);

        $this->assertNull($code3);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testAddRefreshToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addRefreshToken($token1);

        $token2 = $store->getRefreshToken($token1['id']);

        $this->assertNotNull($token2);
        $this->assertEquals($token1['id'], $token2['id']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testGetNonExistentRefreshToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token = $store->getRefreshToken('id');

        $this->assertNull($token);
    }

    /**
     * @group unit
     * @group oauth2
     * @expectedException \SimpleSAML_Error_Error
     * @expectedExceptionCode -1
     */
    public function testAddAlreadyAddedRefreshToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addRefreshToken($token);
        $store->addRefreshToken($token);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testRemoveRefreshToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addRefreshToken($token1);

        $token2 = $store->getRefreshToken($token1['id']);

        $this->assertNotNull($token2);
        $this->assertEquals($token1['id'], $token2['id']);

        $store->removeRefreshToken($token2['id']);

        $token3 = $store->getRefreshToken($token2['id']);

        $this->assertNull($token3);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testAddAccessToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addAccessToken($token1);

        $token2 = $store->getAccessToken($token1['id']);

        $this->assertNotNull($token2);
        $this->assertEquals($token1['id'], $token2['id']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testGetNonExistentAccessToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token = $store->getAccessToken('id');

        $this->assertNull($token);
    }

    /**
     * @group unit
     * @group oauth2
     * @expectedException \SimpleSAML_Error_Error
     * @expectedExceptionCode -1
     */
    public function testAddAlreadyAddedAccessToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addAccessToken($token);
        $store->addAccessToken($token);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testRemoveAccessToken()
    {
        $store = new \sspmod_oauth2server_OAuth2_TokenStore($this->getDefaultConfiguration());

        $token1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addAccessToken($token1);

        $token2 = $store->getAccessToken($token1['id']);

        $this->assertNotNull($token2);
        $this->assertEquals($token1['id'], $token2['id']);

        $store->removeAccessToken($token2['id']);

        $token3 = $store->getAccessToken($token2['id']);

        $this->assertNull($token3);
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
        ), 'test');

        return $config;
    }
}