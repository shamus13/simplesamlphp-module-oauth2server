<?php

class sspmod_oauth2server_Store_SQLStore extends sspmod_oauth2server_Store_Store
{
    public $pdo;
    public $driver;

    public function __construct($config)
    {
        parent::__construct($config);

        $dsn = $config['dsn'];
        $username = $config['username'];
        $password = $config['password'];

        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($this->driver === 'mysql') {
            $this->pdo->exec('SET time_zone = "+00:00"');
        }
    }

    private function removeExpiredObjects() {
        $cleanUpStatement = "delete from OAuth where expire < :expire";

        $preparedCleanUpStatement = $this->pdo->prepare($cleanUpStatement);

        $preparedCleanUpStatement->execute(array(':expire' => time()));
    }

    private function getObject($id)
    {
        $query = 'select id, value, expire from OAuth2 where id = :id';

        $query = $this->pdo->prepare($query);
        $query->execute(array(':id' => $id));

        if (($row = $query->fetch(PDO::FETCH_ASSOC)) != FALSE) {
            if ($row['expire'] > time()) {
                $value = $row['value'];

                if (is_resource($value)) {
                    $value = stream_get_contents($value);
                }

                $value = urldecode($value);
                $value = unserialize($value);

                $value['id'] = $row['id'];
                $value['expire'] = $row['expire'];

                return $value;
            }
        }

        return null;
    }

    private function addObject($object)
    {
        $insertStatement = "insert into OAuth2 values(:id, :value, :expire)";

        $preparedInsertStatement = $this->pdo->prepare($insertStatement);

        $preparedInsertStatement->execute(array(':id' => $object['id'],
            ':value' => rawurlencode(serialize($object)),
            ':expire' => $object['expire']
        ));

        return $object['id'];
    }

    private function removeObject($id)
    {
        $deleteStatement = "delete from OAuth2 where id = :id";

        $preparedDeleteStatement = $this->pdo->prepare($deleteStatement);

        $preparedDeleteStatement->execute(array(':id' => $id));
    }


    public function getAuthorizationCode($codeId)
    {
        return $this->getObject($codeId);
    }

    public function addAuthorizationCode($code)
    {
        $this->removeExpiredObjects();

        return $this->addObject($code);
    }

    public function removeAuthorizationCode($codeId)
    {
        $this->removeObject($codeId);
    }

    public function getRefreshToken($tokenId)
    {
        return $this->getObject($tokenId);
    }

    public function addRefreshToken($token)
    {
        $this->removeExpiredObjects();

        return $this->addObject($token);
    }

    public function removeRefreshToken($tokenId)
    {
        $this->removeObject($tokenId);
    }

    public function getAccessToken($tokenId)
    {
        return $this->getObject($tokenId);
    }

    public function addAccessToken($token)
    {
        $this->removeExpiredObjects();

        return $this->addObject($token);
    }

    public function removeAccessToken($tokenId)
    {
        $this->removeObject($tokenId);
    }
}
