<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TenantOnboarding;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $email = mb_strtolower($validated['email']);
        if (User::query()->where('email', $email)->exists()) {
            return back()->with('status', 'Si el email puede registrarse, recibirás las instrucciones para continuar.');
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => $validated['password'],
            'role' => User::ROLE_ADMIN,
        ]);

        TenantOnboarding::query()->create([
            'user_id' => $user->getKey(),
            'step' => 'verify_email',
            'status' => 'registered',
        ]);

        event(new Registered($user));
        $user->sendEmailVerificationNotification();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
