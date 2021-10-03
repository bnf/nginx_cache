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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SetPageCacheHook
{
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

        $uri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $temp_content = (isset($data['temp_content']) && $data['temp_content']);
        $tsfe = $this->getTypoScriptFrontendController();

        $isLaterCachable = (
            $temp_content === false &&
            $tsfe &&
            $tsfe->isStaticCacheble() &&
            $tsfe->doWorkspacePreview() === false &&
            in_array('nginx_cache_ignore', $tags, true) === false
        );

        $cachable = (
            $isLaterCachable &&
            !str_contains($uri, '?') &&
            $this->isAdminPanelVisible() === false &&
            $this->getServerRequestMethod() === 'GET'
        );

        if ($cachable) {
            $this->getCacheManager()->getCache('nginx_cache')->set(md5($uri), $uri, $tags, $lifetime);
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

    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : null;
    }

    protected function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
