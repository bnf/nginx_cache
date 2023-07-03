<?php
return [
    'frontend' => [
        'bnf/cache-status/cache-hit' => [
            'target' => \Bnf\CacheStatus\CacheHit::class,
            'before' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
