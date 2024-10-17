<?php
declare(strict_types=1);

namespace Bnf\NginxCache\Hooks;

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
use TYPO3\CMS\Frontend\Event\AfterTypoScriptDeterminedEvent;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PageLoadedFromCacheHook
{
    use RequestAwareTrait;

    private FrontendInterface $nginxCache;

    public function __construct(FrontendInterface $nginxCache)
    {
        $this->nginxCache = $nginxCache;
    }

    /**
     * v13.0 event
     */
    public function handleEvent(AfterTypoScriptDeterminedEvent $event): void
    {
        $tsfe = $this->getTypoScriptFrontendController();
        // @todo  $tsfe->pageContentWasLoadedFromCache is protected and $tsfe->isGeneratePage() is internal
        if ($tsfe->isGeneratePage()) {
            return;
        }

        $pageCacheTags = $tsfe->getPageCacheTags();
        if (!in_array('nginx-cache-later-cacheable', $pageCacheTags, true)) {
            return;
        }

        $request = $this->getServerRequest();
        $uri = $this->getUri($request);

        $context = GeneralUtility::makeInstance(Context::class);

        $frontendTypoScript = $event->getFrontendTypoScript();
        $cachable = (
            $tsfe->isStaticCacheble($request) &&
            $context->getPropertyFromAspect('workspace', 'isOffline', false) === false &&
            strpos($uri, '?') === false &&
            $this->isAdminPanelVisible($frontendTypoScript) === false &&
            $request->getMethod() === 'GET'
        );

        if (!$cachable) {
            return;
        }

        $cacheCollector = $request->getAttribute('frontend.cache.collector');
        // => 13.3
        if ($cacheCollector !== null) {
            $lifetime = $cacheCollector->resolveLifetime();
        } else {
            // @todo This is ugly because $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'] was removed in v13.0 without replacement,
            // and TSFE does not provide public access to current cache expiry timestamp
            $cacheExpires = \Closure::bind(static fn() => $tsfe->cacheExpires, null, TypoScriptFrontendController::class);
            $lifetime = $cacheExpires() - $context->getPropertyFromAspect('date', 'timestamp');
        }

        $this->nginxCache->set(md5($uri), $uri, $pageCacheTags, $lifetime);
    }

    /**
     * v12 hook
     */
    public function loadedFromCache(array &$params, TypoScriptFrontendController $tsfe): void
    {
        $row = $params['cache_pages_row'] ?? [];
        $pageCacheTags = $row['cacheTags'] ?? [];
        if (!in_array('nginx-cache-later-cacheable', $pageCacheTags, true)) {
            return;
        }

        /* We populate config into $TSFE as we do need it for the isAdminPanelVisisble check.
         * $TSFE does the same after this hook (pageLoadedFromCache). So it should be safe.  */
        $tsfe->config = $row['cache_data'] ?? '';

        $request = $this->getServerRequest();
        $uri = $this->getUri($request);

        $context = GeneralUtility::makeInstance(Context::class);
        $cachable = (
            $tsfe->isStaticCacheble() &&
            $context->getPropertyFromAspect('workspace', 'isOffline', false) === false &&
            strpos($uri, '?') === false &&
            $this->isAdminPanelVisible() === false &&
            $request->getMethod() === 'GET'
        );

        if (!$cachable) {
            return;
        }

        $lifetime = $row['expires'] - $context->getPropertyFromAspect('date', 'timestamp');
        $this->nginxCache->set(md5($uri), $uri, $pageCacheTags, $lifetime);
    }
}
