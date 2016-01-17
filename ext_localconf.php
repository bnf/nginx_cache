<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nginx_cache'] = [
    'frontend' =>  \TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class,
    'backend' => \Qbus\NginxCache\Cache\Backend\NginxCacheBackend::class,
    'groups' => [
        'pages',
        'all'
    ],
];
