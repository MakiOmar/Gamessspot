<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * POST api/customers: find-or-create used by the POS add-customer signup.
 */
class ApiCreateCustomerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(
            User::factory()->create(['is_active' => true]),
            ['customers:write']
        );
    }

    public function test_creates_customer_when_phone_is_new(): void
    {
        $phone = '+2010'.random_int(10000000, 99999999);
        $email = 'new_'.uniqid().'@example.com';

        $this->postJson('/api/customers', [
            'name' => 'New Customer',
            'email' => $email,
            'phone' => $phone,
            'update_existing' => false,
        ])->assertCreated()->assertJsonPath('data.phone', $phone);

        $this->assertDatabaseHas('users', ['phone' => $phone, 'email' => $email]);
    }

    public function test_matches_existing_local_format_phone_without_duplicating(): void
    {
        $national = '10'.random_int(10000000, 99999999);
        $existing = User::factory()->create(['phone' => '0'.$national, 'email' => 'old_'.uniqid().'@example.com']);

        $this->postJson('/api/customers', [
            'name' => 'Same Person',
            'email' => 'new_'.uniqid().'@example.com',
            'phone' => '+20'.$national,
            'update_existing' => false,
        ])->assertOk()->assertJsonPath('data.id', $existing->id);

        $this->assertSame($existing->email, $existing->fresh()->email);
        $this->assertDatabaseMissing('users', ['phone' => '+20'.$national]);
    }

    public function test_does_not_update_email_by_default(): void
    {
        $phone = '010'.random_int(10000000, 99999999);
        $existing = User::factory()->create(['phone' => $phone]);
        $original = $existing->email;
        $email = 'updated_'.uniqid().'@example.com';

        $this->postJson('/api/customers', [
            'name' => 'Same Person',
            'email' => $email,
            'phone' => $phone,
        ])->assertOk();

        $this->assertSame($original, $existing->fresh()->email);
    }

    public function test_updates_email_when_explicitly_requested_for_customers(): void
    {
        $phone = '010'.random_int(10000000, 99999999);
        $existing = User::factory()->create(['phone' => $phone]);
        $email = 'updated_'.uniqid().'@example.com';

        $this->postJson('/api/customers', [
            'name' => 'Same Person',
            'email' => $email,
            'phone' => $phone,
            'update_existing' => true,
        ])->assertOk();

        $this->assertSame($email, $existing->fresh()->email);
    }

    public function test_never_changes_staff_email_even_with_update_existing(): void
    {
        $phone = '010'.random_int(10000000, 99999999);
        $staff = User::factory()->create(['phone' => $phone, 'email' => 'staff_'.uniqid().'@example.com']);
        $role = Role::firstOrCreate(['name' => 'admin']);
        $staff->roles()->syncWithoutDetaching([$role->id]);
        $original = $staff->email;

        $this->postJson('/api/customers', [
            'name' => 'Attacker',
            'email' => 'attacker_'.uniqid().'@example.com',
            'phone' => $phone,
            'update_existing' => true,
        ])->assertOk();

        $this->assertSame($original, $staff->fresh()->email);
    }

    public function test_rejects_token_without_customers_write_ability(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_active' => true]), ['orders:receive']);

        $this->postJson('/api/customers', [
            'name' => 'X',
            'email' => 'x_'.uniqid().'@example.com',
            'phone' => '+2010'.random_int(10000000, 99999999),
        ])->assertForbidden();
    }
}
