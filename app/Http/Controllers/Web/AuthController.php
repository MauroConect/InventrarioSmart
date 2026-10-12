<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (! Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
            if (Auth::user()->isPlatformSuperadmin()) {
                return redirect()->route('platform.dashboard');
            }
            if (Auth::user()->onboarding && ! Auth::user()->onboarding->completed_at) {
                return redirect()->route('onboarding.show');
            }

            $sessionKey = config('tenancy.session_key', 'tenant_id');
            $hasMemberships = Auth::user()->tenants()
                ->wherePivot('status', config('tenancy.active_status', 'active'))
                ->where('tenants.status', config('tenancy.active_status', 'active'))
                ->exists();

            if ($hasMemberships && ! session()->has($sessionKey)) {
                return redirect()->route('tenant.select');
            }

            return redirect()->route('home');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas son incorrectas.',
            ])->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->isPlatformSuperadmin()) {
            return redirect()->route('platform.dashboard');
        }

        if ($user->onboarding && ! $user->onboarding->completed_at) {
            return redirect()->route('onboarding.show');
        }

        $tenants = $user->tenants()
            ->wherePivot('status', config('tenancy.active_status', 'active'))
            ->where('tenants.status', config('tenancy.active_status', 'active'))
            ->orderByDesc('tenant_user.is_default')
            ->get();

        if ($tenants->count() === 1) {
            $request->session()->put(config('tenancy.session_key', 'tenant_id'), $tenants->first()->getKey());
        } elseif ($tenants->count() > 1) {
            return redirect()->route('tenant.select');
        } elseif (! config('tenancy.legacy_mode', true) || $user->onboarding) {
            return redirect()->route('onboarding.show');
        }

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget(config('tenancy.session_key', 'tenant_id'));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
