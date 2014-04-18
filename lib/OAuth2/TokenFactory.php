<?php

class sspmod_oauth2server_OAuth2_TokenFactory
{
    private $timeToLive;

    public function __construct($timeToLive)
    {
        $this->timeToLive = $timeToLive;
    }

    public function createCode($clientId, $redirectUri, $scopes, $attributes)
    {
        return array(
            'id' => SimpleSAML_Utilities::generateID(),
            'clientId' => $clientId,
            'redirectUri' => $redirectUri,
            'scopes' => $scopes,
            'expire' => time() + $this->timeToLive,
            'attributes' => $attributes);
    }
}