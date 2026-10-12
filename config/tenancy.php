<?php

return [
    'session_key' => env('TENANCY_SESSION_KEY', 'tenant_id'),
    'header' => env('TENANCY_HEADER', 'X-Tenant-Id'),

    /*
     * Permite que instalaciones existentes continúen operando sin membresías
     * mientras sus datos se migran de forma atómica a tenant_id.
     */
    'legacy_mode' => (bool) env('TENANCY_LEGACY_MODE', true),

    'cache_prefix' => env('TENANCY_CACHE_PREFIX', 'tenant'),
    'storage_root' => env('TENANCY_STORAGE_ROOT', 'tenants'),
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'inventariosmart.local'),
    'legacy_login_enabled' => (bool) env('TENANCY_LEGACY_LOGIN_ENABLED', false),
    'reserved_slugs' => ['www', 'app', 'api', 'admin', 'platform', 'soporte', 'support'],

    'active_status' => 'active',
];
