<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nginx_cache'] = [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend' => \Qbus\NginxCache\Cache\Backend\NginxCacheBackend::class,
    'groups' => [
        'pages',
        'all'
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_cache_frontend_variablefrontend.php']['set']['nginx_cache'] =
    \Qbus\NginxCache\Hooks\SetPageCacheHook::class . '->set';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache']['nginx_cache'] =
    \Qbus\NginxCache\Hooks\PageLoadedFromCacheHook::class . '->loadedFromCache';
