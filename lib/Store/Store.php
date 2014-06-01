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
<?php

abstract class sspmod_oauth2server_Store_Store
{
    public function __construct($config)
    {
    }

    public abstract function getAuthorizationCode($codeId);

    public abstract function addAuthorizationCode($code);

    public abstract function removeAuthorizationCode($codeId);

    public abstract function getRefreshToken($tokenId);

    public abstract function addRefreshToken($token);

    public abstract function removeRefreshToken($tokenId);

    public abstract function getAccessToken($tokenId);

    public abstract function addAccessToken($token);

    public abstract function removeAccessToken($tokenId);

    public abstract function getUser($userId);

    public abstract function addUser($user);

    public abstract function updateUser($user);

    public abstract function removeUser($userId);

}