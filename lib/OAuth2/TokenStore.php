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
     * @param $codeId string
     * @return array|null
     */
    public function getAuthorizationCode($codeId)
    {
        $code = $this->store->getObject($this->scopeIdentity('c', $codeId));

        if (is_array($code)) {
            $code['id'] = $codeId;
        }

        return $code;
    }

    public function addAuthorizationCode(array $code)
    {
        $this->store->removeExpiredObjects();

        if ($this->store->getObject($this->scopeIdentity('c', $code['id'])) === null) {
            $code['id'] = $this->scopeIdentity('c', $code['id']);
            return $this->store->addObject($code);
        } else {
            throw new SimpleSAML_Error_Error('oauth2server:DUPLICATE');
        }
    }

    /**
     * @param $codeId string
     */
    public function removeAuthorizationCode($codeId)
    {
        $this->store->removeObject($this->scopeIdentity('c', $codeId));
    }

    /**
     * @param $tokenId string
     * @return array|null
     */
    public function getRefreshToken($tokenId)
    {
        $token = $this->store->getObject($this->scopeIdentity('r', $tokenId));

        if (is_array($token)) {
            $token['id'] = $tokenId;
        }

        return $token;
    }

    public function addRefreshToken(array $token)
    {
        $this->store->removeExpiredObjects();

        if ($this->store->getObject($this->scopeIdentity('r', $token['id'])) === null) {
            $token['id'] = $this->scopeIdentity('r', $token['id']);
            return $this->store->addObject($token);
        } else {
            throw new SimpleSAML_Error_Error('oauth2server:DUPLICATE');
        }
    }

    /**
     * @param $tokenId string
     */
    public function removeRefreshToken($tokenId)
    {
        $this->store->removeObject($this->scopeIdentity('r', $tokenId));
    }

    /**
     * @param $tokenId string
     * @return array|null
     */
    public function getAccessToken($tokenId)
    {
        $token = $this->store->getObject($this->scopeIdentity('a', $tokenId));

        if (is_array($token)) {
            $token['id'] = $tokenId;
        }

        return $token;
    }

    public function addAccessToken(array $token)
    {
        $this->store->removeExpiredObjects();

        if ($this->store->getObject($this->scopeIdentity('a', $token['id'])) === null) {
            $token['id'] = $this->scopeIdentity('a', $token['id']);
            return $this->store->addObject($token);
        } else {
            throw new SimpleSAML_Error_Error('oauth2server:DUPLICATE');
        }
    }

    /**
     * @param $tokenId string
     */
    public function removeAccessToken($tokenId)
    {
        $this->store->removeObject($this->scopeIdentity('a', $tokenId));
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