<?php

namespace SimpleSAML\Oauth2Server;

class sspmod_oauth2server_Store_LDAPStoreTest extends \PHPUnit_Framework_TestCase
{
    private $config = array(
        'class' => 'oauth2server:LDAPStore',
        'url' => 'ldap://localhost:1234',
        'tls' => false,
        'username' => 'cn=admin,dc=example,dc=com',
        'password' => 'secret',
        'base' => 'dc=store,dc=example,dc=com',
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
     */
    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_LDAPStore($this->config);
    }

    /**
     * @group integration
     */
    public function testRemoveExpiredObjects()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $store->removeExpiredObjects();
    }

    /**
     * @group integration
     */
    public function testGetNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = $store->getObject('test');

        $this->assertNull($object);
    }

    /**
     * @group integration
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
     */
    public function testGetNonObject()
    {
        $store = new \sspmod_oauth2server_Store_LDAPStore($this->config);

        $object = 'blah';

        $this->evilObjectCreator($object);

        $object2 = $store->getObject($object);

        $this->assertNull($object2);
    }

    /**
     * @group integration
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

    private function getId()
    {
        return \SimpleSAML\Utils\Random::generateID();
    }

    private function evilObjectCreator($id)
    {
        $connection = ldap_connect($this->config['url']);

        ldap_set_option($connection, LDAP_OPT_DEREF, $this->config['deref']);
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_bind($connection, $this->config['username'], $this->config['password']);

        ldap_add($connection, "cn={$id},{$this->config['base']}",
            array(
                'jsonString' => array(json_encode('dummy')),
                'expireTime' => array(strval(time() + 1000)),
                'objectClass' => array('jsonObject')
            ));

        ldap_close($connection);
    }
}