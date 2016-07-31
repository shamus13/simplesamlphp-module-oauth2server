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
}