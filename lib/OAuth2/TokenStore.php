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

class sspmod_oauth2server_OAuth2_TokenStore
{
    private $store;

    public function __construct($config)
    {
        $storeConfig = $config->getValue('store');
        $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');

        $this->store = new $storeClass($storeConfig);
    }

    public function getAuthorizationCode($codeId)
    {
        return $this->store->getObject($codeId);
    }

    public function addAuthorizationCode($code)
    {
        $this->store->removeExpiredObjects();

        return $this->store->addObject($code);
    }

    public function removeAuthorizationCode($codeId)
    {
        $this->store->removeObject($codeId);
    }

    public function getRefreshToken($tokenId)
    {
        return $this->store->getObject($tokenId);
    }

    public function addRefreshToken($token)
    {
        $this->store->removeExpiredObjects();

        return $this->store->addObject($token);
    }

    public function removeRefreshToken($tokenId)
    {
        $this->store->removeObject($tokenId);
    }

    public function getAccessToken($tokenId)
    {
        return $this->store->getObject($tokenId);
    }

    public function addAccessToken($token)
    {
        $this->store->removeExpiredObjects();

        return $this->store->addObject($token);
    }

    public function removeAccessToken($tokenId)
    {
        $this->store->removeObject($tokenId);
    }
}