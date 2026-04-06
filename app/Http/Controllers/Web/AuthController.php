<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $users = User::query()
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);

        return view('auth.login', compact('users'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => 'required',
        ]);

        $user = User::find($request->user_id);

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'user_id' => 'Las credenciales proporcionadas son incorrectas.',
            ])->withInput($request->only('user_id'));
        }

        Auth::login($user, $request->remember ?? false);

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
