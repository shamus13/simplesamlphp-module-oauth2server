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

class sspmod_oauth2server_OAuth2_TokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $codeTTL = 250;
    private $accessTTL = 300;
    private $refreshTTL = 4800;

    /**
     * @group unit
     * @group oauth2
     */
    public function testCreateAuthorizationCode()
    {
        $factory = $this->newTokenFactory();

        $clientId = 'id';
        $redirectUri = 'uri';
        $scopes = array('scope1', 'scope2');
        $userId = 'userId';

        $now = time();

        $code = $factory->createAuthorizationCode($clientId, $redirectUri, $scopes, $userId);

        $this->assertNotNull($code);
        $this->assertEquals('AC', substr($code['id'], 0, 2));
        $this->assertEquals('AuthorizationCode', $code['type']);
        $this->assertEquals($clientId, $code['clientId']);
        $this->assertEquals($redirectUri, $code['redirectUri']);
        $this->assertEquals($scopes, $code['scopes']);
        $this->assertEquals($now + $this->codeTTL, $code['expire']);
        $this->assertEquals($this->codeTTL, $code['authorizationCodeTTL']);
        $this->assertEquals($this->refreshTTL, $code['refreshTokenTTL']);
        $this->assertEquals($this->accessTTL, $code['accessTokenTTL']);
        $this->assertEquals($userId, $code['userId']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testCreateRefreshToken()
    {
        $factory = $this->newTokenFactory();

        $clientId = 'id';
        $redirectUri = 'uri';
        $scopes = array('scope1', 'scope2');
        $userId = 'userId';

        $now = time();

        $code = $factory->createRefreshToken($clientId, $redirectUri, $scopes, $userId);

        $this->assertNotNull($code);
        $this->assertEquals('RE', substr($code['id'], 0, 2));
        $this->assertEquals('RefreshToken', $code['type']);
        $this->assertEquals($clientId, $code['clientId']);
        $this->assertEquals($redirectUri, $code['redirectUri']);
        $this->assertEquals($scopes, $code['scopes']);
        $this->assertEquals($now + $this->refreshTTL, $code['expire']);
        $this->assertEquals($this->codeTTL, $code['authorizationCodeTTL']);
        $this->assertEquals($this->refreshTTL, $code['refreshTokenTTL']);
        $this->assertEquals($this->accessTTL, $code['accessTokenTTL']);
        $this->assertEquals($userId, $code['userId']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testCreateBearerAccessToken()
    {
        $factory = $this->newTokenFactory();

        $clientId = 'id';
        $scopes = array('scope1', 'scope2');
        $userId = 'userId';

        $now = time();

        $code = $factory->createBearerAccessToken($clientId, $scopes, $userId);

        $this->assertNotNull($code);
        $this->assertEquals('BA', substr($code['id'], 0, 2));
        $this->assertEquals('Bearer', $code['type']);
        $this->assertEquals($clientId, $code['clientId']);
        $this->assertEquals($scopes, $code['scopes']);
        $this->assertEquals($now + $this->accessTTL, $code['expire']);
        $this->assertEquals($this->codeTTL, $code['authorizationCodeTTL']);
        $this->assertEquals($this->refreshTTL, $code['refreshTokenTTL']);
        $this->assertEquals($this->accessTTL, $code['accessTokenTTL']);
        $this->assertEquals($userId, $code['userId']);
    }

    private function newTokenFactory()
    {
        return new \sspmod_oauth2server_OAuth2_TokenFactory($this->codeTTL,
            $this->accessTTL, $this->refreshTTL);
    }
}