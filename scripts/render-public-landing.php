<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Render the marketing views without authenticating, reading tenants or exporting app routes.
Illuminate\Support\Facades\URL::forceRootUrl('https://landing-export.invalid');
Illuminate\Support\Facades\URL::forceScheme('https');
$view = app(App\Http\Controllers\Web\LandingController::class)();
$html = $view->with('publicLanding', true)->render();
$html = str_replace('https://landing-export.invalid', '', $html);
if (preg_match('/landing-export\.invalid|localhost|127\.0\.0\.1|csrf-token|href="\/(login|register|app|platform)/i', $html)) {
    throw new RuntimeException('The public landing contains an application-only URL or session metadata.');
}
echo $html;
