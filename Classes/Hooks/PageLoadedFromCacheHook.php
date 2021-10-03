<?php
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
use TYPO3\CMS\Core\Context\Context;

class PageLoadedFromCacheHook
{
    public function loadedFromCache(&$params): void
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $params['pObj'];
        $row = $params['cache_pages_row'];

        if (!isset($row['tx_nginx_cache_tags'])) {
            return;
        }

        /* We populate config into $TSFE as we do needed it for the isAdminPanelVisisble check.
         * $TSFE does the same after this hook (pageLoadedFromCache). So it should be safe.  */
        $tsfe->config = $row['cache_data'];

        $uri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $context = GeneralUtility::makeInstance(Context::class);

        $cachable = (
            $tsfe->isStaticCacheble() &&
            $context->getPropertyFromAspect('workspace', 'isOffline', false) &&
            !str_contains($uri, '?') &&
            $this->isAdminPanelVisible() === false &&
            $this->getServerRequestMethod() === 'GET'
        );

        if (!$cachable) {
            return;
        }

        $lifetime = $row['expires'] - $context->getPropertyFromAspect('date', 'timestamp');
        $tags = $row['tx_nginx_cache_tags'];
        $this->getCacheManager()->getCache('nginx_cache')->set(md5($uri), $uri, $tags, $lifetime);
    }

    protected function isAdminPanelVisible(): bool
    {
        return (
            ExtensionManagementUtility::isLoaded('adminpanel') &&
            StateUtility::isActivatedForUser() &&
            StateUtility::isActivatedInTypoScript() &&
            StateUtility::isHiddenForUser() === false
        );
    }

    public function getServerRequestMethod(): string
    {
        if (isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST']->getMethod();
        }

        return isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    protected function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
