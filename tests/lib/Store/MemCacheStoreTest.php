<?php

namespace SimpleSAML\Oauth2Server;

class sspmod_oauth2server_Store_MemCacheStoreTest extends \PHPUnit_Framework_TestCase
{
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
    \'memcache_store.servers\' => array(
        array(
            array(\'hostname\' => \'localhost\'),
        ),
    ),
);
'));

        fclose($file);
    }


    /**
     * @group integration
     * @group memcached
     */
    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));
    }

    /**
     * @group integration
     * @group memcached
     */
    public function testRemoveExpiredObjects()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

        $store->removeExpiredObjects();
    }

    /**
     * @group integration
     * @group memcached
     */
    public function testGetNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

        $object = $store->getObject('test');

        $this->assertNull($object);
    }

    /**
     * @group integration
     * @group memcached
     */
    public function testGetExpiredObject()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

        $object = array('id' => 'dummy', 'test' => 'x', 'expire' => (time() - 1000));

        $store->addObject($object);

        $object = $store->getObject('dummy');

        $this->assertNull($object);
    }

    /**
     * @group integration
     * @group memcached
     */
    public function testGetObjectMissingExpire()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

        $object = array('id' => 'dummy', 'test' => 'x');

        \SimpleSAML_Memcache::set('dummy.' . $object['id'], $object, time() + 1000);

        $object = $store->getObject('dummy');

        $this->assertNotNull($object);
        $this->assertEquals('dummy', $object['id']);
        $this->assertEquals('x', $object['test']);
    }

    /**
     * @group integration
     * @group memcached
     */
    public function testGetNonObject()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

        $object = 'blah';

        \SimpleSAML_Memcache::set('dummy.blah', $object, time() + 1000);

        $object = $store->getObject('blah');

        $this->assertNull($object);
    }

    /**
     * @group integration
     * @group memcached
     */
    public function testAddObject()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

        $object = array('id' => 'dummy', 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object = $store->getObject('dummy');

        $this->assertNotNull($object);
        $this->assertEquals('dummy', $object['id']);
        $this->assertEquals('x', $object['test']);
    }

    /**
     * @group integration
     * @group memcached
     */
    public function testUpdateObject()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

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

    /**
     * @group integration
     * @group memcached
     */
    public function testRemoveObject()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheStore(array('prefix' => 'dummy'));

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