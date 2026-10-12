<?php

namespace App\Support\Tenancy;

use Closure;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class TenantRuntime
{
    public function __construct(private readonly TenantContext $context) {}

    public function cacheKey(string $key): string
    {
        if (! preg_match('/^[a-zA-Z0-9_.:-]+$/', $key)) { throw new InvalidArgumentException('Clave de cache inválida.'); }
        return config('tenancy.cache_prefix', 'tenant').':'.$this->context->id().':'.$key;
    }

    public function remember(string $key, int $seconds, Closure $callback): mixed
    {
        return Cache::remember($this->cacheKey($key), $seconds, $callback);
    }

    public function lock(string $key, int $seconds = 60)
    {
        return Cache::lock($this->cacheKey($key), $seconds);
    }

    public function privatePath(string $suffix): string
    {
        if ($suffix === '' || str_contains($suffix, '..') || str_contains($suffix, '\\') || str_contains($suffix, "\0") || str_starts_with($suffix, '/') || str_contains($suffix, ':')) {
            throw new InvalidArgumentException('Ruta de archivo inválida.');
        }
        return 'tenants/'.$this->context->tenant()->uuid.'/'.$suffix;
    }
}
