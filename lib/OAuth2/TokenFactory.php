<?php

class sspmod_oauth2server_OAuth2_TokenFactory
{
    private $authorizationCodeTimeToLive;
    private $accessTimeToLive;

    public function __construct($authorizationCodeTimeToLive, $accessTokenTimeToLive)
    {
        $this->authorizationCodeTimeToLive = $authorizationCodeTimeToLive;
        $this->accessTimeToLive = $accessTokenTimeToLive;
    }

    public function createCode($clientId, $redirectUri, $scopes, $attributes)
    {
        return array(
            'id' => SimpleSAML_Utilities::generateID(),
            'type' => 'AccessToken',
            'clientId' => $clientId,
            'redirectUri' => $redirectUri,
            'scopes' => $scopes,
            'expire' => time() + $this->authorizationCodeTimeToLive,
            'attributes' => $attributes);
    }

    public function createRefreshToken($clientId, $redirectUri, $scopes, $attributes)
    {
        return array(
            'id' => SimpleSAML_Utilities::generateID(),
            'type' => 'RefreshToken',
            'clientId' => $clientId,
            'redirectUri' => $redirectUri,
            'scopes' => $scopes,
            'expire' => time() + $this->authorizationCodeTimeToLive,
            'attributes' => $attributes);
    }

    public function createBearerAccessToken($clientId, $scopes, $attributes) {
        return array(
            'id' => SimpleSAML_Utilities::generateID(),
            'type' => 'Bearer',
            'clientId' => $clientId,
            'scopes' => $scopes,
            'expire' => time() + $this->accessTimeToLive,
            'attributes' => $attributes);
    }
}