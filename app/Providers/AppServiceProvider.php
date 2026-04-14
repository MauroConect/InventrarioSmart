<?php

namespace App\Providers;

use App\Models\ConfiguracionComercio;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            try {
                if (Schema::hasTable('configuracion_comercio')) {
                    $view->with('comercio', ConfiguracionComercio::actual());
                } else {
                    $view->with('comercio', new ConfiguracionComercio([
                        'nombre_comercio' => 'Mi Comercio',
                        'color_primario' => '#1e40af',
                        'color_sidebar' => '#1f2937',
                    ]));
                }
            } catch (\Throwable $e) {
                $view->with('comercio', new ConfiguracionComercio([
                    'nombre_comercio' => 'Mi Comercio',
                    'color_primario' => '#1e40af',
                    'color_sidebar' => '#1f2937',
                ]));
            }
        });
    }
}
