<?php

defined('TYPO3') or die();

use Bnf\NginxCache\Cache\Backend\NginxCacheBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nginx'] = [
    'frontend' => VariableFrontend::class,
    'backend' => NginxCacheBackend::class,
    'groups' => [
        'pages',
        'all'
    ],
];
