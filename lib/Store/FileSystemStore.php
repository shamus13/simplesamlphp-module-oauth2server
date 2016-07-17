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

class sspmod_oauth2server_Store_FileSystemStore extends sspmod_oauth2server_Store_Store
{
    private $directory;

    public function __construct($config)
    {
        if (!is_string($config['directory'])) {
            throw new Exception('Invalid directory option in config.');
        }

        $conf = new SimpleSAML_Configuration(array(), '');
        $path = $conf->resolvePath($config['directory']);

        if (!is_dir($path)) {
            throw new Exception('Invalid storage directory [' . $path . '].');
        }

        if (!is_writable($path)) {
            throw new Exception('Storage directory [' . $path . '] is not writable.');
        }

        $this->directory = preg_replace('/\/$/', '', $path) . '/';
    }

    public function removeExpiredObjects()
    {
        $now = time();

        foreach (scandir($this->directory) as $file) {
            if (count($subs = preg_split('/-/', $file)) > 1) {
                if (($expire = intval($subs[0])) != false) {
                    if ($expire < $now) {
                        unlink($this->directory . '/' . $file);
                    }
                }
            }
        }
    }

    public function getObject($identity)
    {
        $filename = $this->resolveFileName($identity);

        if (is_string($filename)) {
            $content = file_get_contents($this->directory . '/' . $filename);

            $object = unserialize($content);

            if ($this->isValid($object)) {
                return $object;
            }
        }

        return null;
    }

    public function addObject($object)
    {
        $filename = $this->directory . $object['expire'] . '-' . $object['id'];
        file_put_contents($filename, serialize($object));
    }

    public function updateObject($object)
    {
        $filename = $this->directory . $object['expire'] . '-' . $object['id'];
        file_put_contents($filename, serialize($object));
    }

    public function removeObject($identity)
    {
        $filename = $this->resolveFileName($identity);

        if (is_string($filename)) {
            $fullPath = $this->directory . '/' . $filename;

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function isValid($object)
    {
        return is_array($object) && array_key_exists('expire', $object) && $object['expire'] >= time();
    }

    private function resolveFileName($identity)
    {
        $files = scandir($this->directory);

        $fileName = null;

        if ($files !== false) {
            foreach ($files as $file) {
                if (count($subs = preg_split('/-/', $file)) > 1) {

                    if (intval($subs[0]) !== 0 && strcmp($subs[1], $identity) === 0) {
                        $fileName = $file;
                    }
                }
            }
        }

        return $fileName;
    }
}
