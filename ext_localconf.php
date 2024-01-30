<?php

defined('TYPO3') or die();

use Bnf\NginxCache\Cache\Backend\NginxCacheBackend;
use Bnf\NginxCache\Hooks\PageLoadedFromCacheHook;
use Bnf\NginxCache\Hooks\SetPageCacheHook;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Information\Typo3Version;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nginx'] = [
    'frontend' => VariableFrontend::class,
    'backend' => NginxCacheBackend::class,
    'groups' => [
        'pages',
        'all'
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_cache_frontend_variablefrontend.php']['set']['nginx_cache'] =
    SetPageCacheHook::class . '->set';

if ((new Typo3Version())->getMajorVersion() <= 12) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache']['nginx_cache'] =
        PageLoadedFromCacheHook::class . '->loadedFromCache';
}
