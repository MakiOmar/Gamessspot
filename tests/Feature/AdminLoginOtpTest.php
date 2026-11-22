<?php

namespace Tests\Feature;

use App\Mail\AdminLoginOtpMail;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminLoginOtpService;
use App\Services\OneTimeCodeAttempts;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminLoginOtpTest extends TestCase
{
    use DatabaseTransactions;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Event::fake([MessageLogged::class]);
        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
    }

    /**
     * @param  callable(MessageLogged): bool  $callback
     */
    private function assertOtpLogged(callable $callback): void
    {
        Event::assertDispatched(MessageLogged::class, $callback);
    }

    private function staff(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'email' => 'staff_'.uniqid().'@example.com',
            'is_active' => 1,
            'password' => Hash::make('password'),
        ], $overrides));
        $user->roles()->attach($this->adminRole->id);

        return $user;
    }

    private function customer(): User
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create([
            'email' => 'customer_'.uniqid().'@example.com',
            'is_active' => 1,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_request_otp_sends_mail_for_active_staff_without_revealing_existence(): void
    {
        $user = $this->staff();

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertSent(AdminLoginOtpMail::class, function (AdminLoginOtpMail $mail) use ($user) {
            return $mail->user->is($user) && strlen($mail->code) === 6;
        });

        $this->assertOtpLogged(fn (MessageLogged $log) => $log->level === 'info'
            && $log->message === 'Admin login OTP: send_attempt'
            && ($log->context['user_id'] ?? null) === $user->id
            && ($log->context['email'] ?? null) === $user->email);
        $this->assertOtpLogged(fn (MessageLogged $log) => $log->level === 'info'
            && $log->message === 'Admin login OTP: send_ok'
            && ($log->context['user_id'] ?? null) === $user->id
            && array_key_exists('duration_ms', $log->context));

        $user->refresh();
        $this->assertNotEmpty($user->login_otp_hash);
        $this->assertNotNull($user->login_otp_expires_at);
    }

    public function test_request_otp_within_cooldown_does_not_send_again(): void
    {
        $user = $this->staff();

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']))
            ->assertSessionHas('status')
            ->assertSessionHas('status_level', 'warning');

        Mail::assertSent(AdminLoginOtpMail::class, 1);

        $this->assertOtpLogged(fn (MessageLogged $log) => $log->level === 'info'
            && $log->message === 'Admin login OTP: request'
            && ($log->context['reason'] ?? null) === AdminLoginOtpService::OUTCOME_COOLDOWN
            && ($log->context['user_id'] ?? null) === $user->id);
    }

    public function test_request_otp_after_cooldown_resends_same_code(): void
    {
        config(['auth_otp.resend_cooldown_seconds' => 60]);
        $user = $this->staff();

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        $firstCode = null;
        Mail::assertSent(AdminLoginOtpMail::class, function (AdminLoginOtpMail $mail) use (&$firstCode) {
            $firstCode = $mail->code;

            return true;
        });
        $this->assertNotNull($firstCode);

        $this->travel(61)->seconds();

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']))
            ->assertSessionHas('status_level', 'success');

        Mail::assertSent(AdminLoginOtpMail::class, 2);
        Mail::assertSent(AdminLoginOtpMail::class, function (AdminLoginOtpMail $mail) use ($firstCode) {
            return $mail->code === $firstCode;
        });

        $this->post(route('manager.login.otp.verify'), [
            'email' => $user->email,
            'code' => $firstCode,
        ])->assertRedirect(route('manager.dashboard'));

        $this->assertAuthenticatedAs($user, 'admin');
    }

    public function test_request_otp_for_unknown_email_returns_same_message_and_sends_nothing(): void
    {
        $this->post(route('manager.login.otp.request'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('manager.login', ['step' => 'code']))
            ->assertSessionHas('status');

        Mail::assertNothingSent();

        $this->assertOtpLogged(fn (MessageLogged $log) => $log->level === 'info'
            && $log->message === 'Admin login OTP: request'
            && ($log->context['reason'] ?? null) === 'ineligible');
    }

    public function test_request_otp_skips_inactive_users(): void
    {
        $user = $this->staff(['is_active' => 0]);

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertNothingSent();
        $this->assertNull($user->fresh()->login_otp_hash);

        $this->assertOtpLogged(fn (MessageLogged $log) => $log->level === 'info'
            && $log->message === 'Admin login OTP: request'
            && ($log->context['reason'] ?? null) === 'ineligible');
    }

    public function test_verify_otp_logs_in_and_clears_code(): void
    {
        $user = $this->staff();
        $code = '123456';
        $user->login_otp_hash = Hash::make($code);
        $user->login_otp_expires_at = now()->addMinutes(10);
        $user->save();

        $this->post(route('manager.login.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('manager.dashboard'));

        $this->assertAuthenticatedAs($user, 'admin');
        $user->refresh();
        $this->assertNull($user->login_otp_hash);
        $this->assertNull($user->login_otp_expires_at);

        $this->get(route('manager.dashboard'))->assertOk();

        $this->assertOtpLogged(fn (MessageLogged $log) => $log->level === 'info'
            && $log->message === 'Admin login OTP: verify'
            && ($log->context['reason'] ?? null) === 'accepted'
            && ($log->context['user_id'] ?? null) === $user->id);
    }

    public function test_verify_otp_rejects_wrong_code(): void
    {
        $user = $this->staff();
        $user->login_otp_hash = Hash::make('123456');
        $user->login_otp_expires_at = now()->addMinutes(10);
        $user->save();

        $this->from(route('manager.login', ['step' => 'code']))
            ->post(route('manager.login.otp.verify'), [
                'email' => $user->email,
                'code' => '000000',
            ])
            ->assertRedirect(route('manager.login', ['step' => 'code']))
            ->assertSessionHasErrors('code');

        $this->assertGuest('admin');
        $this->assertNotNull($user->fresh()->login_otp_hash);

        $this->assertOtpLogged(fn (MessageLogged $log) => $log->level === 'warning'
            && $log->message === 'Admin login OTP: verify'
            && ($log->context['reason'] ?? null) === 'bad_code'
            && ($log->context['user_id'] ?? null) === $user->id);
    }

    public function test_verify_otp_rejects_expired_code(): void
    {
        $user = $this->staff();
        $user->login_otp_hash = Hash::make('123456');
        $user->login_otp_expires_at = now()->subMinute();
        $user->save();

        $this->post(route('manager.login.otp.verify'), [
            'email' => $user->email,
            'code' => '123456',
        ])->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    public function test_too_many_wrong_guesses_burn_the_code(): void
    {
        $user = $this->staff();
        $user->login_otp_hash = Hash::make('123456');
        $user->login_otp_expires_at = now()->addMinutes(10);
        $user->save();

        $service = app(AdminLoginOtpService::class);

        for ($i = 0; $i < OneTimeCodeAttempts::MAX_FAILURES; $i++) {
            try {
                $service->verifyCode($user->email, '000000');
                $this->fail('Expected ValidationException');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('code', $e->errors());
            }
        }

        $this->assertNull($user->fresh()->login_otp_hash);
    }

    public function test_password_login_route_is_gone(): void
    {
        $this->post('/manager/login', [
            'phone' => '01012345678',
            'password' => 'password',
        ])->assertMethodNotAllowed();
    }

    public function test_customer_email_does_not_receive_a_manager_code(): void
    {
        $user = $this->customer();

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertNothingSent();
        $this->assertNull($user->fresh()->login_otp_hash);
    }

    public function test_whitelist_allows_matching_id_and_email(): void
    {
        $email = 'otp_ok_'.uniqid().'@example.com';
        $user = $this->staff(['email' => $email]);
        config(['auth_otp.whitelist' => [(int) $user->id => strtolower($email)]]);

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertSent(AdminLoginOtpMail::class);
        $this->assertNotEmpty($user->fresh()->login_otp_hash);
    }

    public function test_whitelist_blocks_listed_id_with_wrong_email(): void
    {
        // Same id as a whitelist entry, but DB email was swapped — no OTP.
        $user = $this->staff(['email' => 'attacker_'.uniqid().'@example.com']);
        config(['auth_otp.whitelist' => [
            (int) $user->id => 'pinned-owner_'.uniqid().'@example.com',
            999999 => 'other@example.com',
        ]]);

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertNothingSent();
        $this->assertNull($user->fresh()->login_otp_hash);
    }

    public function test_whitelist_blocks_staff_not_on_the_list(): void
    {
        $allowed = $this->staff(['email' => 'allowed@example.com']);
        $other = $this->staff(['email' => 'other-staff@example.com']);
        config(['auth_otp.whitelist' => [(int) $allowed->id => 'allowed@example.com']]);

        $this->post(route('manager.login.otp.request'), ['email' => $other->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertNothingSent();
        $this->assertNull($other->fresh()->login_otp_hash);
    }

    public function test_whitelist_blocks_verify_when_email_no_longer_matches(): void
    {
        $user = $this->staff(['email' => 'attacker_'.uniqid().'@example.com']);
        $user->login_otp_hash = Hash::make('123456');
        $user->login_otp_expires_at = now()->addMinutes(10);
        $user->save();
        config(['auth_otp.whitelist' => [(int) $user->id => 'pinned-owner_'.uniqid().'@example.com']]);

        $this->post(route('manager.login.otp.verify'), [
            'email' => $user->email,
            'code' => '123456',
        ])->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    public function test_customer_cannot_verify_a_manager_code(): void
    {
        $user = $this->customer();
        $user->login_otp_hash = Hash::make('123456');
        $user->login_otp_expires_at = now()->addMinutes(10);
        $user->save();

        $this->post(route('manager.login.otp.verify'), [
            'email' => $user->email,
            'code' => '123456',
        ])->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    public function test_customer_session_is_sent_back_to_login_instead_of_403(): void
    {
        $this->actingAs($this->customer(), 'admin')
            ->get(route('manager.dashboard'))
            ->assertRedirect(route('manager.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_login_page_is_email_otp_only(): void
    {
        $this->get(route('manager.login'))
            ->assertOk()
            ->assertSee('Send login code', false)
            ->assertSee(route('manager.login.otp.request'), false)
            ->assertDontSee('Sign in with phone', false)
            ->assertDontSee('method=password', false)
            ->assertDontSee('name="remember"', false);
    }

    public function test_code_step_shows_inbox_hint_and_resend_control(): void
    {
        $user = $this->staff();

        $this->withSession(['login_otp_email' => $user->email])
            ->get(route('manager.login', ['step' => 'code']))
            ->assertOk()
            ->assertSee('Check your inbox and spam', false)
            ->assertSee('Resend code', false)
            ->assertDontSee('name="remember"', false)
            ->assertDontSee(__('adminlte::adminlte.remember_me'), false);
    }
}
