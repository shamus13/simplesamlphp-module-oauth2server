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
}