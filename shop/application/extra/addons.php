<?php

return [
    'autoload' => false,
    'hooks' => [
        'upgrade' => [
            'cus',
        ],
        'app_init' => [
            'cus',
        ],
        'config_init' => [
            'cus',
            'nkeditor',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
