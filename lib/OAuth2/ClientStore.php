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
    /**
     * @var sspmod_oauth2server_Store_Store
     */
    private $store;
    private $configuredClients;
    private $validScopes;
    private $registrationEnabled;

    public function __construct(\SimpleSAML_Configuration $config)
    {
        $this->configuredClients = array();

        foreach ($config->getValue('clients', array()) as $clientId => $client) {
            $scopes = array();

            foreach ((isset($client['scope']) ? $client['scope'] : array()) as $scope) {
                $scopes[$scope] = false;
            }

            foreach ((isset($client['scopeRequired']) ? $client['scopeRequired'] : array()) as $scope) {
                $scopes[$scope] = true;
            }

            unset($client['scopeRequired']);
            $client['scope'] = $scopes;

            $this->configuredClients[$clientId] = $client;
        }

        $storeConfig = $config->getValue('store');
        $storeClass = SimpleSAML_Module::resolveClass($storeConfig['class'], 'Store');

        $this->store = new $storeClass($storeConfig);

        $this->validScopes = array_keys($config->getValue('scopes', array()));

        $this->registrationEnabled = $config->getValue('enable_client_registration', false);
    }

    /**
     * @param $clientId
     * @return array|null
     */
    public function getClient($clientId)
    {
        $client = null;

        if (array_key_exists($clientId, $this->configuredClients)) {
            $client = $this->configuredClients[$clientId];
        } else {
            if ($this->registrationEnabled) {
                $client = $this->store->getObject($clientId);
            }
        }

        if (!is_null($client)) {
            $scopes = array();

            foreach ($this->validScopes as $scope) {
                if (array_key_exists($scope, $client['scope'])) {
                    $scopes[$scope] = $client['scope'][$scope];
                }
            }

            $client['scope'] = $scopes;
            $client['id'] = $clientId;
        }

        return $client;
    }

    public function addClient(array $client)
    {
        if (!array_key_exists($client['id'], $this->configuredClients)) {
            $this->store->removeExpiredObjects();

            if ($this->registrationEnabled) {
                if (is_null($this->store->getObject($client['id']))) {

                    return $this->store->addObject($client);
                } else {
                    throw new SimpleSAML_Error_Error('oauth2server:DUPLICATE');
                }
            } else {
                throw new SimpleSAML_Error_Error('oauth2server:REGISTRATION_DISABLED');
            }
        } else {
            throw new SimpleSAML_Error_Error('oauth2server:DUPLICATE');
        }
    }

    public function updateClient(array $client)
    {
        if (!array_key_exists($client['id'], $this->configuredClients)) {
            if ($this->registrationEnabled) {
                return $this->store->updateObject($client);
            } else {
                throw new SimpleSAML_Error_Error('oauth2server:REGISTRATION_DISABLED');
            }
        } else {
            throw new SimpleSAML_Error_Error('oauth2server:READONLY');
        }
    }

    /**
     * @param $clientId
     * @throws \SimpleSAML_Error_Error
     */
    public function removeClient($clientId)
    {
        if (!array_key_exists($clientId, $this->configuredClients)) {
            return $this->store->removeObject($clientId);
        } else {
            throw new SimpleSAML_Error_Error('oauth2server:READONLY');
        }
    }
}