<?php

class sspmod_oauth2server_OAuth2_AuthorizationCodeFactory
{
    private $timeToLive;

    public function __construct($timeToLive)
    {
        $this->timeToLive = $timeToLive;
    }

    public function createCode($clientId, $scopes = array())
    {
        return array(
            'id' => SimpleSAML_Utilities::generateID(),
            'clientId' => $clientId,
            'scopes' => $scopes,
            'expire' => time() + $this->timeToLive);
    }
}