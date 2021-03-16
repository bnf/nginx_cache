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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
        if (
            // Cache Identifier as of v10.0
            $frontend->getIdentifier() !== 'pages' &&
            // Cache Identifier until v9.5
            $frontend->getIdentifier() !== 'cache_pages'
        ) {
            return;
        }

        // Ignore TYPO3 v9+ cached 404 page
        if (in_array('errorPage', $params['tags'], true)) {
            return;
        }

        // TYPO3 v9 added none-page content to cache_pages, ignore those.
        $ignoredIdentifiers = [
            'redirects',
            '-titleTag-',
            '-metatag-',
        ];
        foreach ($ignoredIdentifiers as $ignored) {
            if (strpos($params['entryIdentifier'], $ignored) !== false) {
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
            $this->isFrontendEditingActive() === false &&
            in_array('nginx_cache_ignore', $tags) === false
        );

        $cachable = (
            $isLaterCachable &&
            strpos($uri, '?') === false &&
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

    /**
     * @return bool
     */
    protected function isAdminPanelVisible()
    {
        if (version_compare(TYPO3_branch, '9.2', '>=')) {
            return (
                ExtensionManagementUtility::isLoaded('adminpanel') &&
                \TYPO3\CMS\Adminpanel\Utility\StateUtility::isActivatedForUser() &&
                \TYPO3\CMS\Adminpanel\Utility\StateUtility::isActivatedInTypoScript() &&
                \TYPO3\CMS\Adminpanel\Utility\StateUtility::isHiddenForUser() == false
            );
        }

        return (
            $this->getTypoScriptFrontendController()->isBackendUserLoggedIn() &&
            $GLOBALS['BE_USER'] instanceof \TYPO3\CMS\Backend\FrontendBackendUserAuthentication &&
            $GLOBALS['BE_USER']->isAdminPanelVisible()
        );
    }

    /**
     * @return bool
     */
    protected function isFrontendEditingActive()
    {
        return (
            /* Note: we do not use $GLOBALS['BE_USER']->isFrontendEditingActive() as that checks
             * additional for adminPanel->isAdminModuleEnabled('edit'), but that has no influence
             * on cache clearing. */
            $GLOBALS['TSFE']->displayEditIcons == 1 ||
            $GLOBALS['TSFE']->displayFieldEditIcons == 1
        );
    }

    /**
     * @return string
     */
    public function getServerRequestMethod()
    {
        if (
            isset($GLOBALS['TYPO3_REQUEST']) &&
            interface_exists(\Psr\Http\Message\ServerRequestInterface::class, true) &&
            $GLOBALS['TYPO3_REQUEST'] instanceof \Psr\Http\Message\ServerRequestInterface
        ) {
            return $GLOBALS['TYPO3_REQUEST']->getMethod();
        }

        return isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    /**
     * @return TypoScriptFrontendController|null
     */
    protected function getTypoScriptFrontendController()
    {
        return isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : null;
    }

    /**
     * @return CacheManager
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
