<?php

abstract class sspmod_oauth2server_Store_Store
{
    public function __construct($config)
    {
    }

    public abstract function getAuthorizationCode($codeId);

    public abstract function addAuthorizationCode($code);

    public abstract function removeAuthorizationCode($codeId);
}