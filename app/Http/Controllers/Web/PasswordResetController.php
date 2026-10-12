<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function requestForm() { return view('auth.forgot-password'); }

    public function send(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        try {
            Password::sendResetLink($data);
        } catch (\Throwable $e) {
            Log::warning('No se pudo procesar la recuperación de acceso.', ['exception_type' => $e::class]);
        }
        return back()->with('status', 'Si esa dirección tiene una cuenta, recibirás un enlace para recuperar el acceso.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()],
        ]);
        $status = DB::transaction(function () use ($data) {
            DB::table(config('auth.passwords.users.table'))->where('email', $data['email'])->lockForUpdate()->first();
            return Password::reset($data, function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete();
                if (Schema::hasTable('sessions')) { DB::table('sessions')->where('user_id', $user->id)->delete(); }
                event(new PasswordReset($user));
            });
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'El enlace es inválido o venció. Solicitá uno nuevo.'])->onlyInput('email');
        }
        return redirect()->route('login')->with('status', 'Contraseña actualizada. Ya podés ingresar.');
    }
}
