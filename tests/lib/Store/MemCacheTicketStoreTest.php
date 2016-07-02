<?php

namespace SimpleSAML\Oauth2Server;

class sspmod_oauth2server_Store_MemCacheTicketStoreTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_MemCacheTicketStore(array('prefix' => 'dummy'));
    }

    public function testRemoveExpiredObjects()
    {
        $store = new \sspmod_oauth2server_Store_MemCacheTicketStore(array('prefix' => 'dummy'));

        $store->removeExpiredObjects();
    }
}