<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ConfiguracionComercio extends Model
{
    use BelongsToTenant;

    protected $table = 'configuracion_comercio';

    protected $fillable = [
        'nombre_comercio',
        'slogan',
        'logo_path',
        'direccion',
        'telefono',
        'email',
        'sitio_web',
        'color_primario',
        'color_sidebar',
        'mensaje_ticket',
        'mensaje_footer',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }
        return Storage::disk('public')->url($this->logo_path);
    }

    public static function actual(): self
    {
        $context = app(TenantContext::class);
        $defaults = fn () => new static([
            'nombre_comercio' => $context->hasTenant() ? $context->tenant()->name : 'InventarioSmart',
            'color_primario' => '#2563eb',
            'color_sidebar' => '#111827',
        ]);

        if (! $context->hasTenant()) {
            if (! config('tenancy.legacy_mode', true)) {
                return $defaults();
            }

            return $context->runWithoutScope(
                fn () => static::withoutGlobalScopes()->first() ?? $defaults()
            );
        }

        $key = config('tenancy.cache_prefix', 'tenant').':'.$context->id().':configuracion_comercio';

        return Cache::remember($key, 300, fn () => static::query()->first() ?? $defaults());
    }

    public static function limpiarCache(?int $tenantId = null): void
    {
        $context = app(TenantContext::class);
        $tenantId ??= $context->hasTenant() ? $context->id() : null;

        if ($tenantId) {
            Cache::forget(config('tenancy.cache_prefix', 'tenant').':'.$tenantId.':configuracion_comercio');
        }
    }
}
