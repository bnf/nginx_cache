<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Nginx Cache Manager',
    'description' => 'Add cache control header and purge nginx cache',
    'category' => 'cache',
    'author' => 'Benjamin Franzke',
    'author_email' => 'bfr@qbus.de',
    'author_company' => 'Qbus GmbH',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'beta',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'version' => '2.0.2',
    'constraints' => array(
        'depends' => array(
            'typo3' => '7.6.13-9.5.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
    'autoload' => array(
        'psr-4' => array('Qbus\\NginxCache\\' => 'Classes')
    ),
);
