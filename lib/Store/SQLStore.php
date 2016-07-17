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

class sspmod_oauth2server_Store_SQLStore extends sspmod_oauth2server_Store_Store
{
    private $pdo;
    private $driver;

    public function __construct(array $config)
    {
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

    public function removeExpiredObjects()
    {
        $delete = "delete from OAuth2 where expire < :expire";

        $prepared = $this->pdo->prepare($delete);

        $prepared->execute(array(':expire' => time() + 60));
    }

    public function getObject($identity)
    {
        $query = 'select id, value, expire from OAuth2 where id = :id';

        $query = $this->pdo->prepare($query);
        $query->execute(array(':id' => $identity));

        if (($row = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
            if ($row['expire'] > time()) {
                $value = $row['value'];
                $value = urldecode($value);
                $value = unserialize($value);

                if (is_array($value)) {
                    $value['id'] = $row['id'];
                    $value['expire'] = $row['expire'];

                    return $value;
                }
            }
        }

        return null;
    }

    public function addObject(array $object)
    {
        $insert = "insert into OAuth2 values(:id, :value, :expire)";

        $prepared = $this->pdo->prepare($insert);

        $prepared->execute(array(
            ':id' => $object['id'],
            ':value' => rawurlencode(serialize($object)),
            ':expire' => $object['expire']
        ));

        return $object['id'];
    }

    public function updateObject(array $object)
    {
        $update = "update OAuth2 set value = :value, expire = :expire where id = :id";

        $prepared = $this->pdo->prepare($update);

        $prepared->execute(array(
            ':id' => $object['id'],
            ':value' => rawurlencode(serialize($object)),
            ':expire' => $object['expire']
        ));

        return $object['id'];
    }

    public function removeObject($identity)
    {
        $delete = "delete from OAuth2 where id = :id";

        $prepared = $this->pdo->prepare($delete);

        $prepared->execute(array(':id' => $identity));
    }
}
