<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'database' => ['driver' => 'database', 'table' => 'cache', 'lock_table' => 'cache_locks', 'connection' => null],
        'array' => ['driver' => 'array', 'serialize' => false],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/'.(env('APP_ENV') === 'testing' ? 'testing/' : '').'data'), 'lock_path' => storage_path('framework/cache/'.(env('APP_ENV') === 'testing' ? 'testing/' : '').'data')],
        'null' => ['driver' => 'null'],
    ],
    'prefix' => env('CACHE_PREFIX', 'inventariosmart'),
];
