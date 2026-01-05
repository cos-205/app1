<?php

return [
    'autoload' => false,
    'hooks' => [
        'config_init' => [
            'nkeditor',
            'cus',
        ],
        'upgrade' => [
            'cus',
        ],
        'app_init' => [
            'cus',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
