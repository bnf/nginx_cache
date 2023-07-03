<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Nginx Cache Manager',
    'description' => 'Add cache control header and purge nginx cache',
    'category' => 'cache',
    'author' => 'Benjamin Franzke',
    'author_email' => 'bfr@qbus.de',
    'author_company' => 'Qbus Internetagentur GmbH',
    'state' => 'stable',
    'version' => '3.0.0-dev',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
