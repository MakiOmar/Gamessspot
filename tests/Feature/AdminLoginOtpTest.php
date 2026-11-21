<?php

namespace Tests\Feature;

use App\Mail\AdminLoginOtpMail;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminLoginOtpService;
use App\Services\OneTimeCodeAttempts;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
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

    public function test_request_otp_sends_mail_for_active_staff_without_revealing_existence(): void
    {
        $user = $this->staff();

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertSent(AdminLoginOtpMail::class, function (AdminLoginOtpMail $mail) use ($user) {
            return $mail->user->is($user) && strlen($mail->code) === 6;
        });

        $user->refresh();
        $this->assertNotEmpty($user->login_otp_hash);
        $this->assertNotNull($user->login_otp_expires_at);
    }

    public function test_request_otp_for_unknown_email_returns_same_message_and_sends_nothing(): void
    {
        $this->post(route('manager.login.otp.request'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('manager.login', ['step' => 'code']))
            ->assertSessionHas('status');

        Mail::assertNothingSent();
    }

    public function test_request_otp_skips_inactive_users(): void
    {
        $user = $this->staff(['is_active' => 0]);

        $this->post(route('manager.login.otp.request'), ['email' => $user->email])
            ->assertRedirect(route('manager.login', ['step' => 'code']));

        Mail::assertNothingSent();
        $this->assertNull($user->fresh()->login_otp_hash);
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

    public function test_login_page_is_email_otp_only(): void
    {
        $this->get(route('manager.login'))
            ->assertOk()
            ->assertSee('Send login code', false)
            ->assertSee(route('manager.login.otp.request'), false)
            ->assertDontSee('Sign in with phone', false)
            ->assertDontSee('method=password', false);
    }
}
