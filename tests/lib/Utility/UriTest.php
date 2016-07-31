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

namespace SimpleSAML\Oauth2Server\Utility;

class sspmod_oauth2server_Utility_UriTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     * @group utility
     */
    public function testAddQueryParametersToSimpleUrl()
    {
        $url = 'http://example.com';
        $params = array('a' => '1', 'b' => '2');

        $result = \sspmod_oauth2server_Utility_Uri::addQueryParametersToUrl($url, $params);

        $this->assertEquals('http://example.com?a=1&b=2', $result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testAddEmptyQueryParameterArrayToSimpleUrl()
    {
        $url = 'http://example.com';
        $params = array();

        $result = \sspmod_oauth2server_Utility_Uri::addQueryParametersToUrl($url, $params);

        $this->assertEquals('http://example.com', $result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testAddQueryParametersToUrlWithQueryParameters()
    {
        $url = 'http://example.com?c=3&d=4';
        $params = array('a' => '1', 'b' => '2');

        $result = \sspmod_oauth2server_Utility_Uri::addQueryParametersToUrl($url, $params);

        $this->assertEquals('http://example.com?c=3&d=4&a=1&b=2', $result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testAddQueryParametersToUrlWithQueryParametersAndFragment()
    {
        $url = 'http://example.com?c=3&d=4#t=10,20';
        $params = array('a' => '1', 'b' => '2');

        $result = \sspmod_oauth2server_Utility_Uri::addQueryParametersToUrl($url, $params);

        $this->assertEquals('http://example.com?c=3&d=4&a=1&b=2#t=10,20', $result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testCalculateScopingForClientWithoutAnIdPList()
    {
        $client = array();

        $result = \sspmod_oauth2server_Utility_Uri::calculateScopingParameters($client);

        $this->assertEmpty($result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testCalculateScopingForClientWithEmptyIdPList()
    {
        $client = array('IDPList' => array());

        $result = \sspmod_oauth2server_Utility_Uri::calculateScopingParameters($client);

        $this->assertEmpty($result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testCalculateScopingForClientWithSingleEntryInIdPList()
    {
        $client = array('IDPList' => array('entityId1'));

        $result = \sspmod_oauth2server_Utility_Uri::calculateScopingParameters($client);

        $this->assertSame(array('saml:idp' => 'entityId1'), $result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testCalculateScopingForClientWithSeveralEntriesInIdPList()
    {
        $client = array('IDPList' => array('entityId1', 'entityId2', 'entityId3'));

        $result = \sspmod_oauth2server_Utility_Uri::calculateScopingParameters($client);

        $this->assertSame(array('saml:IDPList' => array('entityId1', 'entityId2', 'entityId3')), $result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testValidateNullRedirectUriForClientWithoutDefinedRedirectUri()
    {
        $client = array();

        $result = \sspmod_oauth2server_Utility_Uri::validateRedirectUri(null, $client);

        $this->assertFalse($result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testValidateRedirectUriForClientWithoutDefinedRedirectUri()
    {
        $client = array();

        $result = \sspmod_oauth2server_Utility_Uri::validateRedirectUri('http://example.com', $client);

        $this->assertFalse($result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testValidateRedirectUriForClientWithEmptyRedirectUriList()
    {
        $client = array('redirect_uri' => array());

        $result = \sspmod_oauth2server_Utility_Uri::validateRedirectUri('http://example.com', $client);

        $this->assertFalse($result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testValidateUnregisteredRedirectUriForClientWithRedirectUriList()
    {
        $client = array('redirect_uri' => array('intent://example.com'));

        $result = \sspmod_oauth2server_Utility_Uri::validateRedirectUri('http://example.com', $client);

        $this->assertFalse($result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testValidateRegisteredRedirectUriForClientWithRedirectUriList()
    {
        $client = array('redirect_uri' => array('http://example.com'));

        $result = \sspmod_oauth2server_Utility_Uri::validateRedirectUri('http://example.com', $client);

        $this->assertTrue($result);
    }

    /**
     * @group unit
     * @group utility
     */
    public function testValidateRegisteredRedirectUriWithFragmentForClientWithRedirectUriList()
    {
        $client = array('redirect_uri' => array('http://example.com#test'));

        $result = \sspmod_oauth2server_Utility_Uri::validateRedirectUri('http://example.com#test', $client);

        $this->assertFalse($result);
    }
}