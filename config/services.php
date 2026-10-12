<?php

return [
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 20),
        'site_url' => env('OPENROUTER_SITE_URL', env('APP_URL')),
        'app_name' => env('OPENROUTER_APP_NAME', env('APP_NAME', 'InventarioSmart')),
    ],
];
