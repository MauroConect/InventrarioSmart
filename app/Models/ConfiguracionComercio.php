<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ConfiguracionComercio extends Model
{
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
        return Cache::remember('configuracion_comercio', 300, function () {
            return static::first() ?? new static([
                'nombre_comercio' => 'Mi Comercio',
                'color_primario' => '#1e40af',
                'color_sidebar' => '#1f2937',
            ]);
        });
    }

    public static function limpiarCache(): void
    {
        Cache::forget('configuracion_comercio');
    }
}
