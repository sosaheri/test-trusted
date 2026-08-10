<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        // Disco privado dedicado a los CSV subidos por el usuario. NUNCA usar
        // el disco "public": el archivo comercial no puede quedar accesible
        // por URL sin autenticación (descalificador §7.6 / fragmento D-21).
        'imports' => [
            'driver' => 'local',
            'root' => storage_path('app/imports'),
            'visibility' => 'private',
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
