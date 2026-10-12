<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\{DB, Hash, Notification, Password};
use Tests\MySqlTenantTestCase;

class PasswordRecoveryTest extends MySqlTenantTestCase
{
    public function test_request_has_identical_feedback_and_is_rate_limited(): void
    {
        Notification::fake();
        [, $user] = $this->commerce();
        $known = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])->assertRedirect()->assertSessionHas('status');
        $message = session('status');
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertRedirect()->assertSessionHas('status', $message);
        Notification::assertSentTo($user, ResetPassword::class);
        $this->get('/forgot-password')->assertOk();
        for ($i = 0; $i < 3; $i++) { $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertRedirect(); }
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertStatus(429);
    }

    public function test_token_changes_password_once_and_revokes_existing_api_tokens(): void
    {
        [, $user] = $this->commerce();
        $user->createToken('test-only');
        $token = Password::createToken($user);
        $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))->assertOk();
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NuevoAcceso-12345', 'password_confirmation' => 'NuevoAcceso-12345'];
        $this->post('/reset-password', $payload)->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->from('/reset-password/'.$token)->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    public function test_expired_invalid_and_mismatched_tokens_do_not_change_credentials(): void
    {
        [, $user] = $this->commerce();
        $before = $user->password;
        $token = Password::createToken($user);
        DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subMinutes(61)]);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NuevoAcceso-12345', 'password_confirmation' => 'NuevoAcceso-12345'];
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->post('/reset-password', array_replace($payload, ['token' => 'invalid']))->assertSessionHasErrors('email');
        $valid = Password::createToken($user);
        $this->post('/reset-password', array_replace($payload, ['token' => $valid, 'email' => 'missing@example.test']))->assertSessionHasErrors('email');
        $this->assertSame($before, $user->fresh()->password);
    }
}
