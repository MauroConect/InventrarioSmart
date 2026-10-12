<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->context->hasTenant()) {
            abort_unless(
                $this->context->tenant()->onboarding_completed_at,
                409,
                'El comercio todavía no completó su configuración.'
            );

            return $next($request);
        }

        $user = $request->user();
        if ($user?->isPlatformSuperadmin()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'El superadmin no opera comercios desde este plano.'], 403)
                : redirect()->route('platform.dashboard');
        }

        $onboarding = $user?->onboarding;
        if ($onboarding && ! $onboarding->completed_at) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Tu comercio todavía está en onboarding asistido.'], 409)
                : redirect()->route('onboarding.show');
        }

        if (config('tenancy.legacy_mode', true)) {
            return $next($request);
        }

        return redirect()->route('onboarding.show');
    }
}
