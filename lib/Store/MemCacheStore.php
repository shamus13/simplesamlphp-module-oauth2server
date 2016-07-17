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

class sspmod_oauth2server_Store_MemCacheStore extends sspmod_oauth2server_Store_Store
{
    private $prefix = '';

    public function __construct(array $config)
    {
        if (array_key_exists('prefix', $config)) {
            $this->prefix = $config['prefix'];
        }
    }

    public function removeExpiredObjects()
    {
    }

    public function getObject($identity)
    {
        $scopedId = $this->scopeId($identity);

        $object = SimpleSAML_Memcache::get($scopedId);

        if (is_array($object) && (!array_key_exists('expire', $object) || $object['expire'] >= time())) {
            return $object;
        } else {
            return null;
        }
    }

    public function addObject(array $object)
    {
        $scopedId = $this->scopeId($object['id']);

        SimpleSAML_Memcache::set($scopedId, $object, $object['expire']);
    }

    public function updateObject(array $object)
    {
        $scopedId = $this->scopeId($object['id']);

        SimpleSAML_Memcache::set($scopedId, $object, $object['expire']);
    }

    public function removeObject($identity)
    {
        $scopedId = $this->scopeId($identity);

        SimpleSAML_Memcache::delete($scopedId);
    }

    private function scopeId($identity)
    {
        return $this->prefix . '.' . $identity;
    }
}
