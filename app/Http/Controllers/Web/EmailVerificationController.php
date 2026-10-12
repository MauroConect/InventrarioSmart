<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TenantOnboarding;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('onboarding.show');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        $request->session()->regenerate();

        TenantOnboarding::query()->updateOrCreate(
            ['user_id' => $request->user()->getKey()],
            ['step' => 'questionnaire', 'status' => 'questionnaire_pending']
        );

        return redirect()->route('onboarding.show')->with('success', 'Email verificado correctamente.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('onboarding.show');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Te enviamos un nuevo enlace de verificación.');
    }
}
