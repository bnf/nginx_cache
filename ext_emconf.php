<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Nginx Cache Manager',
    'description' => 'Add cache control header and purge nginx cache',
    'category' => 'cache',
    'author' => 'Benjamin Franzke',
    'author_email' => 'ben@bnf.dev',
    'state' => 'stable',
    'version' => '3.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '14.2.0-14.3.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
