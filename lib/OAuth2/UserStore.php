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

class sspmod_oauth2server_OAuth2_UserStore
{
    /**
     * @var sspmod_oauth2server_Store_Store
     */
    private $store;

    public function __construct(\SimpleSAML_Configuration $config)
    {
        $storeConfig = $config->getValue('store');
        $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');

        $this->store = new $storeClass($storeConfig);
    }

    /**
     * @param $userId string
     * @return array|null
     */
    public function getUser($userId)
    {
        $user = $this->store->getObject($this->scopeIdentity('u', $userId));

        if (is_array($user)) {
            $user['id'] = $userId;
        }

        return $user;
    }

    public function addUser(array $user)
    {
        $this->store->removeExpiredObjects();

        if ($this->store->getObject($this->scopeIdentity('u', $user['id'])) === null) {
            $user['id'] = $this->scopeIdentity('u', $user['id']);
            return $this->store->addObject($user);
        } else {
            throw new SimpleSAML_Error_Error('oauth2server:DUPLICATE');
        }
    }

    public function updateUser(array $user)
    {
        $user['id'] = $this->scopeIdentity('u', $user['id']);
        $this->store->updateObject($user);
    }

    /**
     * @param $userId string
     */
    public function removeUser($userId)
    {
        $this->store->removeObject($this->scopeIdentity('u', $userId));
    }

    /**
     * @param $type string
     * @param $identity string
     * @return string
     */
    private function scopeIdentity($type, $identity)
    {
        return $type . $identity;
    }
}