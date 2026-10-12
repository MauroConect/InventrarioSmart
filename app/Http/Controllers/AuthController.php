<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\TransientToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $tenants = $user->tenants()
            ->wherePivot('status', config('tenancy.active_status', 'active'))
            ->where('tenants.status', config('tenancy.active_status', 'active'))
            ->orderByDesc('tenant_user.is_default')
            ->get(['tenants.id', 'tenants.uuid', 'tenants.slug', 'tenants.name', 'tenants.rubro_profile']);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'tenants' => $tenants,
            'email_verified' => $user->hasVerifiedEmail(),
            'onboarding_required' => ! $user->onboarding?->completed_at,
            'requires_tenant_selection' => $tenants->count() > 1,
            'default_tenant' => $tenants->count() === 1 ? $tenants->first() : null,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($request->hasSession()) {
            $request->session()->forget(config('tenancy.session_key', 'tenant_id'));
        }

        if ($user) {
            $token = $user->currentAccessToken();
            if ($token && ! $token instanceof TransientToken) {
                $token->delete();
            }
        }

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
