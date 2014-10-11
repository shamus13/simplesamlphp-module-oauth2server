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

class sspmod_oauth2server_Store_LDAPStore extends sspmod_oauth2server_Store_Store
{
    private $ldapUrl;
    private $enableTLS;
    private $ldapUsername;
    private $ldapPassword;
    private $searchBase;

    public function __construct($config)
    {
        $this->ldapUrl = $config['url'];
        $this->enableTLS = $config['tls'];
        $this->ldapUsername = $config['username'];
        $this->ldapPassword = $config['password'];
        $this->searchBase = $config['base'];
    }

    public function removeExpiredObjects()
    {
        $connection = $this->bindToLdap();

        $expire = strval(time() + 60);

        if ($resultSet = ldap_search($connection, $this->searchBase,
            "(&(expireTime<=$expire)(objectClass=jsonObject))", array(), true)
        ) {
            if ($results = ldap_get_entries($connection, $resultSet)) {

                $value = null;

                if ($results['count'] > 0) {
                    for ($i = 0; $i < $results['count']; ++$i) {
                        if (!ldap_delete($connection, "{$results[$i]['dn']}")) {
                            $error = 'failed to delete object';
                        }
                    }
                }
            } else {
                $error = 'failed to retrieve search result';
            }
        } else {
            $error = 'failed to execute search';
        }

        ldap_close($connection);

        if (isset($error)) {
            throw new Exception($error);
        }
    }

    public function getObject($id)
    {
        $connection = $this->bindToLdap();

        if ($resultSet = ldap_search($connection, $this->searchBase, "(&(cn=$id)(objectClass=jsonObject))")) {
            if ($results = ldap_get_entries($connection, $resultSet)) {
                if ($results['count'] > 0) {
                    $value = json_decode($results[0]['jsonstring'][0], true);

                    $value['id'] = $results[0]['cn'][0];
                    $value['expire'] = intval($results[0]['expiretime'][0]);
                }
            } else {
                $error = 'failed to retrieve search result';
            }
        } else {
            $error = 'failed to execute search';
        }

        ldap_close($connection);

        if (isset($error)) {
            throw new Exception($error);
        } else if (isset($value) && $value['expire'] > time()) {
            return $value;
        } else {
            return null;
        }
    }

    public function addObject($object)
    {
        $connection = $this->bindToLdap();

        if (!ldap_add($connection, "cn={$object['id']},{$this->searchBase}",
            array('jsonString' => array(json_encode($object)),
                'expireTime' => array(strval($object['expire'])),
                'objectClass' => array('jsonObject')))
        ) {
            $error = 'failed to add object';
        }

        ldap_close($connection);

        if (isset($error)) {
            throw new Exception($error);
        }
    }

    public function updateObject($object)
    {
        $connection = $this->bindToLdap();

        if (!ldap_modify($connection, "cn={$object['id']},{$this->searchBase}",
            array('jsonString' => array(json_encode($object)),
                'expireTime' => array(strval($object['expire'])),
                'objectClass' => array('jsonObject')))
        ) {
            $error = 'failed to update object';
        }

        ldap_close($connection);

        if (isset($error)) {
            throw new Exception($error);
        }
    }

    public function removeObject($id)
    {
        $connection = $this->bindToLdap();

        if (!ldap_delete($connection, "cn={$id},{$this->searchBase}")) {
            $error = 'failed to delete object';
        }

        ldap_close($connection);

        if (isset($error)) {
            throw new Exception($error);
        }
    }

    private function bindToLdap()
    {
        if ($connection = ldap_connect($this->ldapUrl)) {
            if (ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3)) {
                if ($this->enableTLS) {
                    if (!ldap_start_tls($connection)) {
                        $error = 'failed to enable TLS';
                    }
                }

                if (!isset($error)) {
                    if (ldap_bind($connection, $this->ldapUsername, $this->ldapPassword)) {
                        return $connection;
                    } else {
                        $error = 'failed to bind to ldap';
                    }
                }
            } else {
                $error = 'failed to enable protocol version 3';
            }

            ldap_close($connection);
        } else {
            $error = 'failed to connect to ldap';
        }

        throw new Exception($error);
    }
}