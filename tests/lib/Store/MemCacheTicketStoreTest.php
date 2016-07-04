<?php

namespace SimpleSAML\Oauth2Server;

class sspmod_oauth2server_Store_MemCacheTicketStoreTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $configDirectory =  (dirname(__DIR__) . '/../../vendor/simplesamlphp/simplesamlphp/config/');

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

    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_MemCacheTicketStore(array('prefix' => 'dummy'));
    }

    public function testRemoveExpiredObjects()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheTicketStore(array('prefix' => 'dummy'));

        $store->removeExpiredObjects();
    }

    public function testGetNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheTicketStore(array('prefix' => 'dummy'));

        $object = $store->getObject('test');

        $this->assertNull($object);
    }
}