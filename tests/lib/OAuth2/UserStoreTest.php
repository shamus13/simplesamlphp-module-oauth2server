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

class sspmod_oauth2server_OAuth2_UserStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     * @group oauth2
     */
    public function testConstructor()
    {
        new \sspmod_oauth2server_OAuth2_UserStore($this->getDefaultConfiguration());
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testGetNonexistentUser()
    {
        $store = new \sspmod_oauth2server_OAuth2_UserStore($this->getDefaultConfiguration());

        $user = $store->getUser('unknown');

        $this->assertNull($user);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testAddUser()
    {
        $store = new \sspmod_oauth2server_OAuth2_UserStore($this->getDefaultConfiguration());

        $user1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addUser($user1);

        $user2 = $store->getUser($user1['id']);

        $this->assertNotNull($user2);
        $this->assertEquals($user1['id'], $user2['id']);
    }

    /**
     * @group unit
     * @group oauth2
     * @expectedException \SimpleSAML_Error_Error
     * @expectedExceptionCode -1
     */
    public function testAddAlreadyAddedUser()
    {
        $store = new \sspmod_oauth2server_OAuth2_UserStore($this->getDefaultConfiguration());

        $client = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addUser($client);
        $store->addUser($client);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testUpdateUser()
    {
        $store = new \sspmod_oauth2server_OAuth2_UserStore($this->getDefaultConfiguration());

        $user1 = array('id' => 'dummy', 'expire' => time() + 1000, 'data' => 'dummy1');

        $store->addUser($user1);

        $user2 = array('id' => 'dummy', 'expire' => time() + 1000, 'data' => 'dummy2');

        $store->updateUser($user2);

        $user3 = $store->getUser($user2['id']);

        $this->assertNotNull($user3);
        $this->assertEquals($user2['id'], $user3['id']);
        $this->assertEquals($user2['scope'], $user3['scope']);
    }

    /**
     * @group unit
     * @group oauth2
     */
    public function testRemoveUser()
    {
        $store = new \sspmod_oauth2server_OAuth2_UserStore($this->getDefaultConfiguration());

        $user1 = array('id' => 'dummy', 'expire' => time() + 1000);

        $store->addUser($user1);

        $user2 = $store->getUser($user1['id']);

        $this->assertNotNull($user2);
        $this->assertEquals($user1['id'], $user2['id']);

        $store->removeUser($user2['id']);

        $user3 = $store->getUser($user2['id']);

        $this->assertNull($user3);
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