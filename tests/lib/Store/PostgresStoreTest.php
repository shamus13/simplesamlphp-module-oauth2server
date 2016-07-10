<?php

namespace SimpleSAML\Oauth2Server;

class sspmod_oauth2server_Store_PostgresStoreTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @group integration
     */
    public function testConstructor()
    {
        new \sspmod_oauth2server_Store_SQLStore($this->config);
    }

    /**
     * @group integration
     */
    public function testRemoveExpiredObjects()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $store->removeExpiredObjects();
    }

    /**
     * @group integration
     */
    public function testGetNonExistingObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

        $object = $store->getObject('test');

        $this->assertNull($object);
    }

    /**
     * @group integration
     */
    public function testGetExpiredObject()
    {
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

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
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

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
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

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
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

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
        $store = new \sspmod_oauth2server_Store_SQLStore($this->config);

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

    public function getId() {
        return \SimpleSAML\Utils\Random::generateID();
    }

    public function evilObjectCreator($id) {
        $dsn = $this->config['dsn'];
        $username = $this->config['username'];
        $password = $this->config['password'];

        $pdo = new \PDO($dsn, $username, $password);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $insertStatement = "insert into OAuth2 values(:id, :value, :expire)";

        $preparedInsertStatement = $pdo->prepare($insertStatement);

        $preparedInsertStatement->execute(array(
            ':id' => $id,
            ':value' => rawurlencode(serialize('dummy')),
            ':expire' => time() + 1000
        ));
    }
}