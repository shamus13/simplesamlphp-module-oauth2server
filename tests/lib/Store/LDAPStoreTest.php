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

namespace SimpleSAML\Oauth2Server;

class sspmod_oauth2server_Store_LDAPStoreTest extends \PHPUnit_Framework_TestCase
{
    private $config = array(
        'class' => 'oauth2server:LDAPStore',
        'url' => 'ldap://localhost:1234',
        'tls' => false,
        'username' => 'cn=admin,dc=example,dc=com',
        'password' => 'secret',
        'base' => 'ou=store,dc=example,dc=com',
        'deref' => \LDAP_DEREF_NEVER, // or one of LDAP_DEREF_SEARCHING, LDAP_DEREF_FINDING, LDAP_DEREF_ALWAYS
    );

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $configDirectory = (dirname(__DIR__) . '/../../vendor/simplesamlphp/simplesamlphp/config/');

        $file = fopen($configDirectory . 'config.php', 'w');

        fwrite($file, sprintf('<?php
/*
 * The configuration of SimpleSAMLphp
 *
 */

$config = array(
);
'));

        fclose($file);
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_LDAPStore($this->config);
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testRemoveExpiredObjects()
    {
        $this->evilObjectCreator($this->getId(), time() -1000);

        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $store->removeExpiredObjects();
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testGetNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = $store->getObject('test');

        $this->assertNull($object);
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testGetExpiredObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() - 1000));

        $store->addObject($object);

        $object = $store->getObject($object['id']);

        $this->assertNull($object);
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testGetNonObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = $this->getId();

        $this->evilObjectCreator($object, time() + 1000);

        $object2 = $store->getObject($object);

        $this->assertNull($object2);
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testAddObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object2 = $store->getObject($object['id']);

        $this->assertNotNull($object2);
        $this->assertEquals($object['id'], $object2['id']);
        $this->assertEquals('x', $object2['test']);
    }

    /**
     * @group integration
     * @group ldap
     * 
     * @expectedException \Exception
     * @expectedExceptionCode 2
     * @expectedExceptionMessage ldap_add(): Add: Already exists
     */
    public function testAddDuplicateObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);
        $store->addObject($object);
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testUpdateObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object2 = $store->getObject($object['id']);

        $this->assertNotNull($object2);
        $this->assertEquals($object['id'], $object2['id']);
        $this->assertEquals('x', $object2['test']);

        $object3 = array('id' => $object['id'], 'tset' => 'y', 'expire' => (time() + 1000));

        $store->updateObject($object3);

        $object4 = $store->getObject($object3['id']);

        $this->assertNotNull($object4);
        $this->assertEquals($object3['id'], $object4['id']);
        $this->assertEquals('y', $object4['tset']);
    }

    /**
     * @group integration
     * @group ldap
     *
     * @expectedException \Exception
     * @expectedExceptionCode 2
     * @expectedExceptionMessage ldap_modify(): Modify: No such object
     */
    public function testUpdateNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = array('id' => $this->getId(), 'tset' => 'y', 'expire' => (time() + 1000));

        $store->updateObject($object);
    }

    /**
     * @group integration
     * @group ldap
     */
    public function testRemoveObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object2 = $store->getObject($object['id']);

        $this->assertNotNull($object2);
        $this->assertEquals($object['id'], $object2['id']);
        $this->assertEquals('x', $object2['test']);

        $object3 = array('id' => $object2['id'], 'tset' => 'y', 'expire' => (time() + 1000));

        $store->removeObject($object3['id']);

        $object4 = $store->getObject($object3['id']);

        $this->assertNull($object4);
    }

    /**
     * @group integration
     * @group ldap
     *
     * @expectedException \Exception
     * @expectedExceptionCode 2
     * @expectedExceptionMessage ldap_delete(): Delete: No such object
     */
    public function testRemoveNonExistentObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = array('id' => $this->getId(), 'tset' => 'y', 'expire' => (time() + 1000));

        $store->removeObject($object['id']);
    }

    private function getId()
    {
        return \SimpleSAML\Utils\Random::generateID();
    }

    private function evilObjectCreator($id, $expire)
    {
        $connection = ldap_connect($this->config['url']);

        ldap_set_option($connection, LDAP_OPT_DEREF, $this->config['deref']);
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_bind($connection, $this->config['username'], $this->config['password']);

        ldap_add($connection, "cn={$id},{$this->config['base']}",
            array(
                'jsonString' => array(json_encode('dummy')),
                'expireTime' => array(strval($expire)),
                'objectClass' => array('jsonObject')
            ));

        ldap_close($connection);
    }
}