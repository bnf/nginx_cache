<?php

defined('TYPO3') or die();

use Qbus\NginxCache\Cache\Backend\NginxCacheBackend;
use Qbus\NginxCache\Hooks\PageLoadedFromCacheHook;
use Qbus\NginxCache\Hooks\SetPageCacheHook;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

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

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache']['nginx_cache'] =
    PageLoadedFromCacheHook::class . '->loadedFromCache';
