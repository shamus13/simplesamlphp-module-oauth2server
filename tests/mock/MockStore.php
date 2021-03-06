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

class sspmod_oauth2server_Store_MockStore extends sspmod_oauth2server_Store_Store
{
    private $store;

    public function __construct(array $config)
    {
        $this->store = array();
    }

    public function removeExpiredObjects()
    {
        foreach ($this->store as $identity => $object) {
            if(!$this->isValid($object)) {
                unset($this->store[$identity]);
            }
        }
     }

    public function getObject($identity)
    {
        if(array_key_exists($identity, $this->store)) {
            $object = $this->store[$identity];

            if ($this->isValid($object)) {
                return $object;
            }
        }

        return null;
    }

    public function addObject(array $object)
    {
        $this->store[$object['id']] =$object;
    }

    public function updateObject(array $object)
    {
        $this->store[$object['id']] =$object;
    }

    public function removeObject($identity)
    {
        unset($this->store[$identity]);
    }

    public function isValid(array $object)
    {
        return is_array($object) && array_key_exists('expire', $object) && $object['expire'] >= time();
    }
}