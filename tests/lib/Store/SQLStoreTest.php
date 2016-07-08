<?php

namespace SimpleSAML\Oauth2Server;

class sspmod_oauth2server_Store_SQLStoreTest extends \PHPUnit_Framework_TestCase
{
    private $config = array(
        'class' => 'oauth2server:SQLStore',
        'dsn' => 'pgsql:host=localhost;port=5432;dbname=oauth2server_test',
        'username' => 'postgres',
        'password' => ''
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

    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_SQLStore($this->config);
    }

    public function testRemoveExpiredObjects()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $store->removeExpiredObjects();
    }

    public function testGetNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = $store->getObject('test');

        $this->assertNull($object);
    }

    public function testGetExpiredObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = array('id' => 'dummy', 'test' => 'x', 'expire' => (time() - 1000));

        $store->addObject($object);

        $object = $store->getObject('dummy');

        $this->assertNull($object);
    }

    public function testGetObjectMissingExpire()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = array('id' => 'dummy', 'test' => 'x');

        \SimpleSAML_Memcache::set('dummy.' . $object['id'], $object, time() + 1000);

        $object = $store->getObject('dummy');

        $this->assertNotNull($object);
        $this->assertEquals('dummy', $object['id']);
        $this->assertEquals('x', $object['test']);
    }

    public function testGetNonObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = 'blah';

        \SimpleSAML_Memcache::set('dummy.blah', $object, time() + 1000);

        $object = $store->getObject('blah');

        $this->assertNull($object);
    }

    public function testAddObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = array('id' => 'dummy', 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object = $store->getObject('dummy');

        $this->assertNotNull($object);
        $this->assertEquals('dummy', $object['id']);
        $this->assertEquals('x', $object['test']);
    }

    public function testUpdateObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = array('id' => 'dummy', 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object = $store->getObject('dummy');

        $this->assertNotNull($object);
        $this->assertEquals('dummy', $object['id']);
        $this->assertEquals('x', $object['test']);

        $object = array('id' => 'dummy', 'tset' => 'y', 'expire' => (time() + 1000));

        $store->updateObject($object);

        $object = $store->getObject('dummy');

        $this->assertNotNull($object);
        $this->assertEquals('dummy', $object['id']);
        $this->assertEquals('y', $object['tset']);
    }

    public function testRemoveObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = array('id' => 'dummy', 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object = $store->getObject('dummy');

        $this->assertNotNull($object);
        $this->assertEquals('dummy', $object['id']);
        $this->assertEquals('x', $object['test']);

        $object = array('id' => 'dummy', 'tset' => 'y', 'expire' => (time() + 1000));

        $store->removeObject($object['id']);

        $object = $store->getObject('dummy');

        $this->assertNull($object);
    }
}