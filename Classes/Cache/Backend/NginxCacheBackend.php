<?php
namespace Qbus\NginxCache\Cache\Backend;

/**
 * nginx_cache – TYPO3 extension to manage the nginx cache
 * Copyright (C) 2016 Qbus GmbH
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * NginxCacheBackend
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class NginxCacheBackend extends \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend
{
    const CMD = 'typo3conf/ext/nginx_cache/Resources/Private/nginx_purge/nginx_purge';

    /**
     * Saves data in a cache file.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data to be stored is not a string.
     */
    public function set($entryIdentifier, $data, array $tags = array(), $lifetime = null)
    {
        parent::set($entryIdentifier, $data, $tags, $lifetime);

        if ($lifetime === 0) {
            // unlimited is not supported by nginx
            $lifetime = 24*60*60;
        }

        header('X-Accel-Expires: ' . $lifetime);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier)
    {
        $levels = '1:2';
        $levels = explode(':', $levels);

        $file = '';
        $i = 0;
        foreach ($levels as $level) {
            $level = (int) $level;

            $file .= substr($entryIdentifier, -($level + $i), $level) . '/';
            $i += $level;
        }

        $file .= $entryIdentifier;
        exec(PATH_site . self::CMD . ' ' . escapeshellarg($file));

        return parent::remove($entryIdentifier);
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     */
    public function flush()
    {
        exec(PATH_site . self::CMD);

        parent::flush();
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return void
     */
    public function flushByTag($tag)
    {
        $this->findIdentifiersByTag($tag);

        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

}
