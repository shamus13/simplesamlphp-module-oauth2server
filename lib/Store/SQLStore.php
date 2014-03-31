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

    public function getAuthorizationCode($codeId) {
        $query = 'select id, code, expire from AuthorizationCode where id = :id';

        $query = $this->pdo->prepare($query);
        $query->execute(array(':id' => $codeId));

        if (($row = $query->fetch(PDO::FETCH_ASSOC)) != FALSE) {
            $value = $row['code'];

            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }

            $value = urldecode($value);
            $value = unserialize($value);

            $value['id'] = $row['id'];
            $value['expire'] = $row['expire'];

            return $value;
        } else {
            return null;
        }
    }

    public function addAuthorizationCode($code) {
        $insertStatement = "insert into AuthorizationCode values(:id, :code, :expire)";

        $preparedInsertStatement = $this->pdo->prepare($insertStatement);

        $preparedInsertStatement->execute(array(':id' => $code['id'],
            ':code' => rawurlencode(serialize($code)),
            ':expire' => $code['expire']
        ));

        return $code['id'];
    }

    public function removeAuthorizationCode($codeId) {
        $deleteStatement = "delete from AuthorizationCode where id = :id";

        $preparedDeleteStatement = $this->pdo->prepare($deleteStatement);

        $preparedDeleteStatement->execute(array(':id' => $codeId));
    }
}
