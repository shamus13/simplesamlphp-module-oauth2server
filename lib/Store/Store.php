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