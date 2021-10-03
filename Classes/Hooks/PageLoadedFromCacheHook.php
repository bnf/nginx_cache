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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;


class PageLoadedFromCacheHook
{
    use RequestAwareTrait;

    private FrontendInterface $nginxCache;

    public function __construct(FrontendInterface $nginxCache)
    {
        $this->nginxCache = $nginxCache;
    }

    public function loadedFromCache(array &$params, TypoScriptFrontendController $tsfe): void
    {
        $row = $params['cache_pages_row'] ?? [];

        $nginxCacheTags = $row['tx_nginx_cache_tags'] ?? null;
        if ($nginxCacheTags === null) {
            return;
        }

        /* We populate config into $TSFE as we do need it for the isAdminPanelVisisble check.
         * $TSFE does the same after this hook (pageLoadedFromCache). So it should be safe.  */
        $tsfe->config = $row['cache_data'] ?? '';

        $request = $this->getServerRequest();
        $uri = $this->getUri($request);

        $cachable = (
            $tsfe->isStaticCacheble() &&
            $tsfe->doWorkspacePreview() === false &&
            strpos($uri, '?') === false &&
            $this->isAdminPanelVisible() === false &&
            $this->isFrontendEditingActive($tsfe) === false &&
            $request->getMethod() === 'GET'
        );

        if (!$cachable) {
            return;
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $lifetime = $row['expires'] - $context->getPropertyFromAspect('date', 'timestamp');
        $this->nginxCache->set(md5($uri), $uri, $nginxCacheTags, $lifetime);
    }
}
