<?php

return [
    'autoload' => false,
    'hooks' => [
        'config_init' => [
            'nkeditor',
            'shopro',
        ],
        'upgrade' => [
            'shopro',
        ],
        'app_init' => [
            'shopro',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
