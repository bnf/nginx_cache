<?php
declare(strict_types=1);

namespace Bnf\NginxCache\Cache\Backend;

/**
 * nginx_cache – NGINX Cache Manager for TYPO3
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

use TYPO3\CMS\Core\Cache\Backend\TransientBackendInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class NginxCacheBackend implements TransientBackendInterface
{
    private Typo3DatabaseBackend $backend;

    public function __construct()
    {
        $this->backend = new Typo3DatabaseBackend();
    }

    public function set(string $entryIdentifier, mixed $data, array $tags = [], ?int $lifetime = null): void
    {
        $this->backend->set($entryIdentifier, $data, $tags, $lifetime);
    }

    public function remove(string $entryIdentifier): bool
    {
        $url = $this->backend->get($entryIdentifier);
        if ($url === false) {
            /* The key is not available. Do nothing. */
            return false;
        }

        $this->purge($url);

        return $this->backend->remove($entryIdentifier);
    }

    public function flush(): void
    {
        /* FIXME: this won't work for cli requests. We could try do derive the site_url from
         * existing cache entries (using findIdentifierByTag?).
         * Or introduce a configure option to set the flushAll URL. */
        if (Environment::isCli()) {
            return;
        }

        $url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . '*';
        $this->purge($url);

        $this->backend->flush();
    }

    public function flushByTag(string $tag): void
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    /**
     * @param list<string> $tasgs
     */
    public function flushByTags(array $tags): void
    {
        array_walk($tags, [$this, 'flushByTag']);
    }

    protected function purge(string $url): string
    {
        $content = '';

        try {
            $requestOptions = [
                'timeout' => 5,
                'connect_timeout' => 5,
            ];
            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
            $response = $requestFactory->request($url, 'PURGE', $requestOptions);

            if ($response->getStatusCode() === 200) {
                if ($response->getHeader('Content-Type') === 'text/plain') {
                    $content = $response->getBody()->getContents();
                }
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            error_log("request for url '" . $url . "' failed with 40x.");
            error_log($e->getMessage());
            throw $e;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log("request for url '" . $url . "' failed with 50x.");
            error_log($e->getMessage());
            throw $e;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            error_log("request for url '" . $url . "' timed out after 5 seconds");
            error_log($e->getMessage());
            throw $e;
        }

        return $content;
    }

    public function get(string $entryIdentifier): mixed
    {
        return $this->backend->get($entryIdentifier);
    }

    public function has(string $entryIdentifier): bool
    {
        return $this->backend->has($entryIdentifier);
    }

    public function collectGarbage(): void
    {
        $this->backend->collectGarbage();
    }

    public function setCache(FrontendInterface $cache): void
    {
        $this->backend->setCache($cache);
    }

    public function getTableDefinitions(): string
    {
        return $this->backend->getTableDefinitions();
    }
}
