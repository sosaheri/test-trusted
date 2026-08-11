<?php

return [
    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        // Usado por phpunit.xml (CACHE_DRIVER=array) para que los tests no
        // dependan de Redis.
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'truster_catalog_cache'),
];
