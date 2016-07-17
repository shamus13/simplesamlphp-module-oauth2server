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

namespace SimpleSAML\Oauth2Server\Store;

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