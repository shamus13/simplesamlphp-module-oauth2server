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

class sspmod_oauth2server_OAuth2_TokenFactory
{
    private $codeTTL;
    private $accessTTL;
    private $refreshTTL;

    public function __construct($codeTTL, $accessTTL, $refreshTTL)
    {
        $this->codeTTL = $codeTTL;
        $this->accessTTL = $accessTTL;
        $this->refreshTTL = $refreshTTL;
    }

    public function createAuthorizationCode($clientId, $redirectUri, $scopes, $userId)
    {
        $token = $this->createProtoToken($clientId, $scopes, $userId, 'AC', 'AuthorizationCode',
            $this->codeTTL, $redirectUri);

        return $token;
    }

    public function createRefreshToken($clientId, $redirectUri, $scopes, $userId)
    {
        $token = $this->createProtoToken($clientId, $scopes, $userId, 'RE', 'RefreshToken',
            $this->refreshTTL, $redirectUri);

        return $token;
    }

    public function createBearerAccessToken($clientId, $scopes, $userId)
    {
        $token = $this->createProtoToken($clientId, $scopes, $userId, 'BA', 'Bearer',
            $this->accessTTL, '');

        unset($token['redirectUri']);

        return $token;
    }

    private function createProtoToken(
        $clientId,
        $scopes,
        $userId,
        $idPrefix,
        $type,
        $ttl,
        $redirectUri
    ) {
        return array(
            'id' => $this->generateID($idPrefix),
            'type' => $type,
            'clientId' => $clientId,
            'scopes' => $scopes,
            'redirectUri' => $redirectUri,
            'expire' => time() + $ttl,
            'authorizationCodeTTL' => $this->codeTTL,
            'refreshTokenTTL' => $this->refreshTTL,
            'accessTokenTTL' => $this->accessTTL,
            'userId' => $userId
        );
    }

    private function generateID($prefix)
    {
        return $prefix . substr(SimpleSAML\Utils\Random::generateID(), 1);
    }
}