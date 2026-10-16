<?php

return [
    'tenant_slug' => env('DEDICATED_TENANT_SLUG'),
    'tenant_name' => env('DEDICATED_TENANT_NAME'),
    'admin_email' => env('DEDICATED_ADMIN_EMAIL'),
    'admin_password' => env('DEDICATED_ADMIN_PASSWORD'),
    'upload_storage' => env('UPLOAD_STORAGE', 'local'),
];
