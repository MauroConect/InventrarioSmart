<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'array' => ['driver' => 'array', 'serialize' => false],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/'.(env('APP_ENV') === 'testing' ? 'testing/' : '').'data'), 'lock_path' => storage_path('framework/cache/'.(env('APP_ENV') === 'testing' ? 'testing/' : '').'data')],
        'null' => ['driver' => 'null'],
    ],
    'prefix' => env('CACHE_PREFIX', 'inventariosmart'),
];
