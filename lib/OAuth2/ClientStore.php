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

class sspmod_oauth2server_OAuth2_ClientStore
{
    private $store;
    private $configuredClients;
    private $validScopes;

    public function __construct($config)
    {
        $this->configuredClients = $config->getValue('clients', array());

        $storeConfig = $config->getValue('store');
        $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');

        $this->store = new $storeClass($storeConfig);

        $this->validScopes = array_keys($config->getValue('scopes', array()));
    }

    public function getClient($clientId)
    {
        $client = null;

        if (array_key_exists($clientId, $this->configuredClients)) {
            $client = $this->configuredClients[$clientId];
        } else {
            $client = $this->store->getObject($clientId);
        }

        if (!is_null($client)) {
            $client['scope'] = array_intersect($client['scope'], $this->validScopes);
            $client['id'] = $clientId;
        }

        return $client;
    }

    public function addClient($client)
    {
        if (!array_key_exists($client['id'], $this->configuredClients)) {
            $this->store->removeExpiredObjects();

            return $this->store->addObject($client);
        } else {
            throw new SimpleSAML_Error_Error('DUPLICATE');
        }
    }

    public function updateClient($client)
    {
        if (!array_key_exists($client['id'], $this->configuredClients)) {
            return $this->store->updateObject($client);
        } else {
            throw new SimpleSAML_Error_Error('READONLY');
        }
    }

    public function removeClient($clientId)
    {
        if (!array_key_exists($clientId, $this->configuredClients)) {
            return $this->store->removeObject($clientId);
        } else {
            throw new SimpleSAML_Error_Error('READONLY');
        }
    }
}