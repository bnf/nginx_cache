<?php

return [
    'frontend' => [
        'bnf/nginx-cache/cache-interceptor' => [
            'target' => \Bnf\NginxCache\Middleware\CacheInterceptor::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect',
            ],
            'before' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
];
