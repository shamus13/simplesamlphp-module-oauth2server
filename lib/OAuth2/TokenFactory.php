<?php

class sspmod_oauth2server_OAuth2_TokenFactory
{
    private $authorizationCodeTimeToLive;
    private $accessTokenTimeToLive;
    private $refreshTokenTimeToLive;

    public function __construct($authorizationCodeTimeToLive, $accessTokenTimeToLive, $refreshTokenTimeToLive)
    {
        $this->authorizationCodeTimeToLive = $authorizationCodeTimeToLive;
        $this->accessTokenTimeToLive = $accessTokenTimeToLive;
        $this->refreshTokenTimeToLive = $refreshTokenTimeToLive;
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
            'expire' => time() + $this->refreshTokenTimeToLive,
            'attributes' => $attributes);
    }

    public function createBearerAccessToken($clientId, $scopes, $attributes) {
        return array(
            'id' => SimpleSAML_Utilities::generateID(),
            'type' => 'Bearer',
            'clientId' => $clientId,
            'scopes' => $scopes,
            'expire' => time() + $this->accessTokenTimeToLive,
            'attributes' => $attributes);
    }
}