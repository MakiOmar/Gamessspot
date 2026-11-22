<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ADMIN_EMAIL_LOCK blocks changing email on staff/admin users in the manager UI.
 */
class StaffEmailLockTest extends TestCase
{
    use DatabaseTransactions;

    private Role $adminRole;

    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['capabilities' => ['access-dashboard', 'manage-users']]
        );
        $this->customerRole = Role::firstOrCreate(['name' => 'customer']);
        config(['auth_otp.email_lock' => true]);
    }

    private function actor(): User
    {
        $user = User::factory()->create([
            'email' => 'actor_'.uniqid().'@example.com',
            'is_active' => 1,
            'password' => Hash::make('password'),
        ]);
        $user->roles()->attach($this->adminRole->id);

        return $user;
    }

    private function staffUser(): User
    {
        $user = User::factory()->create([
            'email' => 'staff_'.uniqid().'@example.com',
            'is_active' => 1,
        ]);
        $user->roles()->attach($this->adminRole->id);

        return $user;
    }

    private function customerUser(): User
    {
        $user = User::factory()->create([
            'email' => 'customer_'.uniqid().'@example.com',
            'is_active' => 1,
        ]);
        $user->roles()->attach($this->customerRole->id);

        return $user;
    }

    public function test_cannot_change_staff_email_when_lock_enabled(): void
    {
        $staff = $this->staffUser();
        $original = $staff->email;

        $this->actingAs($this->actor(), 'admin')
            ->putJson(route('manager.users.update', $staff->id), [
                'name' => $staff->name,
                'email' => 'attacker_'.uniqid().'@example.com',
                'phone' => $staff->phone,
                'roles' => [$this->adminRole->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertSame($original, $staff->fresh()->email);
    }

    public function test_can_change_customer_email_when_lock_enabled(): void
    {
        $customer = $this->customerUser();
        $newEmail = 'customer_new_'.uniqid().'@example.com';

        $this->actingAs($this->actor(), 'admin')
            ->putJson(route('manager.users.update', $customer->id), [
                'name' => $customer->name,
                'email' => $newEmail,
                'phone' => $customer->phone,
                'roles' => [$this->customerRole->id],
            ])
            ->assertOk();

        $this->assertSame($newEmail, $customer->fresh()->email);
    }

    public function test_can_change_staff_email_when_lock_disabled(): void
    {
        config(['auth_otp.email_lock' => false]);
        $staff = $this->staffUser();
        $newEmail = 'staff_new_'.uniqid().'@example.com';

        $this->actingAs($this->actor(), 'admin')
            ->putJson(route('manager.users.update', $staff->id), [
                'name' => $staff->name,
                'email' => $newEmail,
                'phone' => $staff->phone,
                'roles' => [$this->adminRole->id],
            ])
            ->assertOk();

        $this->assertSame($newEmail, $staff->fresh()->email);
    }

    public function test_edit_json_flags_staff_email_locked(): void
    {
        $staff = $this->staffUser();

        $this->actingAs($this->actor(), 'admin')
            ->getJson(route('manager.users.edit', $staff->id))
            ->assertOk()
            ->assertJsonPath('email_locked', true);
    }
}
