<?php
declare(strict_types=1);

namespace Qbus\NginxCache\Hooks;

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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

class SetPageCacheHook
{
    use RequestAwareTrait;

    public function __construct(
        private ?ContainerInterface $container = null,
    ) {
    }

    public function set(array $params, FrontendInterface $frontend): void
    {
        /* We're only intrested in the page cache */
        if ($frontend->getIdentifier() !== 'pages') {
            return;
        }

        // Ignore cached 404 page
        if (in_array('errorPage', $params['tags'], true)) {
            return;
        }

        // EXT:redirects adds none-page content to cache_pages, ignore this.
        $ignoredIdentifiers = [
            'redirects',
        ];
        foreach ($ignoredIdentifiers as $ignored) {
            if (str_contains($params['entryIdentifier'], $ignored)) {
                return;
            }
        }

        $data = $params['variable'];
        $tags = $params['tags'];
        $lifetime = $params['lifetime'];

        $request = $this->getServerRequest();
        $uri = $this->getUri($request);

        $temp_content = (isset($data['temp_content']) && $data['temp_content']);
        $tsfe = $this->getTypoScriptFrontendController();

        $isLaterCachable = (
            $temp_content === false &&
            $tsfe !== null &&
            $tsfe->isStaticCacheble() &&
            $tsfe->doWorkspacePreview() === false &&
            $this->isFrontendEditingActive($tsfe) === false &&
            in_array('nginx_cache_ignore', $tags, true) === false
        );

        $cachable = (
            $isLaterCachable &&
            !str_contains($uri, '?') &&
            $this->isAdminPanelVisible() === false &&
            $request->getMethod() === 'GET'
        );

        if ($cachable && $this->container !== null) {
            // @var FrontendInterface $nginxCache
            $nginxCache = $this->container->get('cache.nginx');
            $nginxCache->set(md5($uri), $uri, $tags, $lifetime);
        }

        if ($isLaterCachable) {
            /* We store these values here, in case we ever loose the cache in nginx.
             * In that case TYPO3 is requested and retrieves the cached content from cache_pages.
             * For that case we install a PageLoadedFromCache Hook, that uses these data
             * to decide a) whether data should be cached and b) which tags have to be assigned.
             * (As the tags are not available in that hook)
             */
            $params['variable']['tx_nginx_cache_tags'] = $tags;
        }
    }
}
