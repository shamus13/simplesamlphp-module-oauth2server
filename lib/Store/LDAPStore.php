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
        $connection = ldap_connect($this->ldapUrl); //todo: check errors

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3); //todo: check errors

        if ($this->enableTLS) {
            ldap_start_tls($connection); //todo: check errors
        }

        ldap_bind($connection, $this->ldapUsername, $this->ldapPassword); //todo: check errors

        $expire = time() + 60;

        $resultSet = ldap_search($connection, $this->searchBase,
            "(&(expireTime < $expire)(objectClass=jsonObject))", null, true); //todo: check errors

        $results = ldap_get_entries($connection, $resultSet); //todo: check errors

        $value = null;

        if ($results != false && $results['count'] > 0) {
            for ($i = 0; $i < $results['count']; ++$i) {
                ldap_delete($connection, "cn={$results[$i]['cn'][0]}");
            }
        }

        ldap_close($connection);
    }

    public function getObject($id)
    {
        $connection = ldap_connect($this->ldapUrl); //todo: check errors

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3); //todo: check errors

        if ($this->enableTLS) {
            ldap_start_tls($connection); //todo: check errors
        }

        ldap_bind($connection, $this->ldapUsername, $this->ldapPassword); //todo: check errors

        $resultSet = ldap_search($connection, $this->searchBase, "(&(cn=$id)(objectClass=jsonObject))"); //todo: check errors

        $results = ldap_get_entries($connection, $resultSet); //todo: check errors

        $value = null;

        if ($results != false && $results['count'] > 0) {
            $value = json_decode($results[0]['jsonstring'][0], true);

            $value['id'] = $results[0]['cn'][0];
            $value['expire'] = intval($results[0]['expiretime'][0]);
        }

        ldap_close($connection);

        return $value;
    }

    public function addObject($object)
    {
        $connection = ldap_connect($this->ldapUrl); //todo: check errors

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3); //todo: check errors

        if ($this->enableTLS) {
            ldap_start_tls($connection); //todo: check errors
        }

        ldap_bind($connection, $this->ldapUsername, $this->ldapPassword); //todo: check errors

        ldap_add($connection, "cn={$object['id']},{$this->searchBase}",
            array('jsonString' => array(json_encode($object)),
                'expireTime' => array(strval($object['expire'])),
                'objectClass' => array('jsonObject')));

        ldap_close($connection);
    }

    public function updateObject($object)
    {
        $connection = ldap_connect($this->ldapUrl); //todo: check errors

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3); //todo: check errors

        if ($this->enableTLS) {
            ldap_start_tls($connection); //todo: check errors
        }

        ldap_bind($connection, $this->ldapUsername, $this->ldapPassword); //todo: check errors

        ldap_modify($connection, "cn={$object['id']},{$this->searchBase}",
            array('jsonString' => array(json_encode($object)),
                'expireTime' => array(strval($object['expire'])),
                'objectClass' => array('jsonObject')));

        ldap_close($connection);
    }

    public function removeObject($id)
    {
        $connection = ldap_connect($this->ldapUrl); //todo: check errors

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3); //todo: check errors

        if ($this->enableTLS) {
            ldap_start_tls($connection); //todo: check errors
        }

        ldap_bind($connection, $this->ldapUsername, $this->ldapPassword); //todo: check errors

        ldap_delete($connection, "cn={$id},{$this->searchBase}");

        ldap_close($connection);
    }
}