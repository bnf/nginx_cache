<?php
namespace Qbus\NginxCache\Hooks;

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
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SetPageCacheHook
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class SetPageCacheHook
{
    /**
     * @param array             $params
     * @param FrontendInterface $frontend
     */
    public function set($params, $frontend)
    {
        /* We're only intrested in the page cache */
        if ($frontend->getIdentifier() !== 'cache_pages') {
            return;
        }

        $data = $params['variable'];
        $tags = $params['tags'];
        $lifetime = $params['lifetime'];

        $uri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $temp_content = (isset($data['temp_content']) && $data['temp_content']);
        $tsfe = $this->getTypoScriptFrontendController();

        $isLaterCachable = (
            $temp_content === false &&
            $tsfe->isStaticCacheble() &&
            $tsfe->doWorkspacePreview() === false &&
            in_array('nginx_cache_ignore', $tags) === false
        );

        $cachable = (
            $isLaterCachable &&
            strpos($uri, '?') === false &&
            $this->isAdminPanelVisible() === false &&
            $this->getEnvironmentService()->getServerRequestMethod() === 'GET'
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

    /**
     * @return bool
     */
    protected function isAdminPanelVisible()
    {
        return (
            $this->getTypoScriptFrontendController()->isBackendUserLoggedIn() &&
            $GLOBALS['BE_USER'] instanceof \TYPO3\CMS\Backend\FrontendBackendUserAuthentication &&
            $GLOBALS['BE_USER']->isAdminPanelVisible()
        );
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\CacheManager;
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected function getEnvironmentService()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Service\EnvironmentService::class);
    }
}
