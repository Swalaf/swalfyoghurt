<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Support\Settings;
use App\Support\Totp;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_landing_page_shows_headline_and_live_plans(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Build anything with AI.')
            ->assertSee('Professional')
            ->assertDontSee('Enterprise'); // hidden plan
    }

    public function test_sign_up_puts_user_on_free_plan_then_onboarding_then_studio(): void
    {
        Settings::set('require_verification', false);
        $this->get('/register?idea=A+booking+system+for+my+salon')->assertOk()->assertSee('booking system');

        $this->post('/register', ['name' => 'Ben Carter', 'email' => 'ben@example.com', 'password' => 'Secret123!', 'terms' => '1'])
            ->assertRedirect(route('onboarding'));

        $user = User::whereEmail('ben@example.com')->first();
        $this->assertSame('starter', $user->plan->slug);
        $this->assertSame(500, $user->credits);

        $this->get('/studio')->assertRedirect(route('onboarding'));
        $this->post('/onboarding', ['level' => 2])->assertRedirect(route('studio.new', ['idea' => 'A booking system for my salon']));
        $this->assertSame('developer', $user->fresh()->experience);
        $this->get('/studio')->assertOk()->assertSee('Good');
    }

    public function test_email_verification_code_flow(): void
    {
        Notification::fake();
        Settings::set('require_verification', true);
        $this->post('/register', ['name' => 'Cee', 'email' => 'cee@example.com', 'password' => 'Secret123!', 'terms' => '1'])
            ->assertRedirect(route('verification.notice'));

        $user = User::whereEmail('cee@example.com')->first();
        $code = null;
        Notification::assertSentTo($user, EmailVerificationCode::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        $this->get('/studio')->assertRedirect(route('verification.notice'));
        $this->post('/verify-email', ['code' => $code === '000000' ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post('/verify-email', ['code' => $code])->assertRedirect(route('onboarding'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_sign_ups_can_be_closed(): void
    {
        Settings::set('allow_signups', false);
        $this->get('/register')->assertOk()->assertSee('Sign-ups are closed');
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'Secret123!', 'terms' => '1'])->assertForbidden();
    }

    public function test_login_rejects_wrong_password_and_suspended_accounts(): void
    {
        $user = User::factory()->create(['email' => 'dan@example.com']);
        $this->post('/login', ['email' => 'dan@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->update(['status' => 'suspended']);
        $this->post('/login', ['email' => 'dan@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->update(['status' => 'active']);
        $this->post('/login', ['email' => 'dan@example.com', 'password' => 'password'])->assertRedirect(route('studio.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_challenge(): void
    {
        $secret = Totp::generateSecret();
        $user = User::factory()->create(['email' => 'eve@example.com']);
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

        $this->post('/login', ['email' => 'eve@example.com', 'password' => 'password'])->assertRedirect(route('two-factor'));
        $this->assertGuest();
        $this->post('/two-factor', ['code' => '12345'])->assertSessionHasErrors('code');
        $this->post('/two-factor', ['code' => Totp::code($secret)])->assertRedirect(route('studio.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_enable_two_factor_from_account_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/studio/account/two-factor')->assertRedirect();
        $secret = $user->fresh()->two_factor_secret;
        $this->get('/studio/account')->assertOk()->assertSee('Scan this');
        $this->post('/studio/account/two-factor/confirm', ['code' => Totp::code($secret)])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasTwoFactor());
    }

    public function test_password_reset_link_and_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'fay@example.com']);
        $this->post('/forgot-password', ['email' => 'fay@example.com'])->assertRedirect(route('password.sent'));
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertRedirect(route('password.sent'));

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->get('/reset-password/'.$token.'?email=fay@example.com')->assertOk();
        $this->post('/reset-password', ['token' => $token, 'email' => 'fay@example.com', 'password' => 'NewSecret1!', 'password_confirmation' => 'NewSecret1!'])
            ->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'fay@example.com', 'password' => 'NewSecret1!'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
