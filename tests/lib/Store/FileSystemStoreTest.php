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

class sspmod_oauth2server_Store_FileSystemStoreTest extends \PHPUnit_Framework_TestCase
{
    private $config = array(
        'class' => 'oauth2server:FileSystemTicketStore',
        'directory' => 'tests'
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
     * @group filesystem
     */
    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_FileSystemStore($this->config);
    }

    /**
     * @group integration
     * @group filesystem
     *
     * @expectedException \Exception
     * @expectedExceptionCode 8
     * @expectedExceptionMessage Undefined index: directory
     */
    public function testMissingStoreDirectoryOption()
    {
        new \sspmod_oauth2server_Store_FileSystemStore(array('class' => $this->config['class']));
    }

    /**
     * @group integration
     * @group filesystem
     *
     * @expectedException \Exception
     * @expectedExceptionCode 0
     * @expectedExceptionMessage Invalid directory option in config.
     */
    public function testInvalidStoreDirectoryOption()
    {
        new \sspmod_oauth2server_Store_FileSystemStore(array('class' => $this->config['class'],
            'directory' => 117));
    }

    /**
     * @group integration
     * @group filesystem
     *
     * @expectedException \Exception
     * @expectedExceptionCode 0
     */
    public function testNonExistentStoreDirectory()
    {
        new \sspmod_oauth2server_Store_FileSystemStore(array(
            'class' => $this->config['class'],
            'directory' => 'does_not_exist'
        ));
    }

    /**
     * @group integration
     * @group filesystem
     *
     * @expectedException \Exception
     * @expectedExceptionCode 0
     */
    public function testWriteProtectedStoreDirectory()
    {
        new \sspmod_oauth2server_Store_FileSystemStore(array(
            'class' => $this->config['class'],
            'directory' => '../../../../../../../../'
        ));
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testRemoveExpiredObjects()
    {
        $this->evilObjectCreator($this->getId(), time() -1000);

        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $store->removeExpiredObjects();
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testGetNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $object = $store->getObject('test');

        $this->assertNull($object);
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testGetExpiredObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() - 1000));

        $store->addObject($object);

        $object = $store->getObject($object['id']);

        $this->assertNull($object);
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testGetNonObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $object = $this->getId();

        $this->evilObjectCreator($object, time() + 1000);

        $object2 = $store->getObject($object);

        $this->assertNull($object2);
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testAddObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);

        $object2 = $store->getObject($object['id']);

        $this->assertNotNull($object2);
        $this->assertEquals($object['id'], $object2['id']);
        $this->assertEquals('x', $object2['test']);
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testAddDuplicateObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $object = array('id' => $this->getId(), 'test' => 'x', 'expire' => (time() + 1000));

        $store->addObject($object);
        $store->addObject($object);

        $object2 = $store->getObject($object['id']);

        $this->assertNotNull($object2);
        $this->assertEquals($object['id'], $object2['id']);
        $this->assertEquals('x', $object2['test']);
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testUpdateObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

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
     * @group filesystem
     */
    public function testUpdateNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $object = array('id' => $this->getId(), 'tset' => 'y', 'expire' => (time() + 1000));

        $store->updateObject($object);
    }

    /**
     * @group integration
     * @group filesystem
     */
    public function testRemoveObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

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
     * @group filesystem
     */
    public function testRemoveNonExistentObject()
    {
        $store = new \sspmod_oauth2server_Store_FileSystemStore($this->config);

        $object = array('id' => $this->getId(), 'tset' => 'y', 'expire' => (time() + 1000));

        $store->removeObject($object['id']);
    }

    private function getId()
    {
        return \SimpleSAML\Utils\Random::generateID();
    }

    private function evilObjectCreator($id, $expire)
    {
        $conf = new \SimpleSAML_Configuration(array(), '');
        $path = $conf->resolvePath('tests');

        $filename = $path . '/' . $expire . '-' . $id;
        file_put_contents($filename, serialize(array(
            'jsonString' => array(json_encode('dummy')),
            'objectClass' => array('jsonObject')
        )));
    }
}