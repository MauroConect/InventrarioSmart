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
        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                return;
            }

            try {
                $req = request();
                if (! $req) {
                    return;
                }

                $stateful = config('sanctum.stateful', []);
                if (! is_array($stateful)) {
                    return;
                }

                $hostsToAdd = array_filter([
                    $req->getHost(),
                    $req->getHttpHost(),
                    parse_url((string) config('app.url'), PHP_URL_HOST),
                ]);

                foreach ($hostsToAdd as $h) {
                    if ($h !== '' && ! in_array($h, $stateful, true)) {
                        $stateful[] = $h;
                    }
                }

                config(['sanctum.stateful' => array_values(array_unique($stateful))]);
            } catch (\Throwable) {
                // ignorar si no hay request válido
            }
        });

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
