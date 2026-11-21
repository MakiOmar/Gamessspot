<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManagerUserSearchTest extends TestCase
{
    use DatabaseTransactions;

    private function staff(): User
    {
        $role = Role::create([
            'name' => 'test-role-' . uniqid(),
            'capabilities' => ['access-dashboard', 'manage-users'],
        ]);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_users_search_matches_email(): void
    {
        $email = 'finder-' . uniqid() . '@example.com';
        $target = User::factory()->create([
            'name' => 'Email Search Target',
            'email' => $email,
            'phone' => '0109' . random_int(1000000, 9999999),
        ]);
        $other = User::factory()->create([
            'name' => 'Someone Else',
            'email' => 'other-' . uniqid() . '@example.com',
            'phone' => '0108' . random_int(1000000, 9999999),
        ]);

        $response = $this->actingAs($this->staff(), 'admin')
            ->getJson(route('manager.users.search', ['search' => $email]));

        $response->assertOk();
        $rows = $response->json('rows');
        $this->assertStringContainsString($target->name, $rows);
        $this->assertStringNotContainsString($other->name, $rows);
    }

    public function test_users_search_email_respects_role_filter(): void
    {
        $email = 'role-filter-' . uniqid() . '@example.com';
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $target = User::factory()->create([
            'name' => 'Role Filtered Customer',
            'email' => $email,
            'phone' => '0107' . random_int(1000000, 9999999),
        ]);
        $target->roles()->attach($customerRole->id);

        $matched = $this->actingAs($this->staff(), 'admin')
            ->getJson(route('manager.users.search', ['role' => $customerRole->id, 'search' => $email]));

        $matched->assertOk();
        $this->assertStringContainsString($target->name, $matched->json('rows'));

        $missingRoleId = Role::max('id') + 1;

        $this->actingAs($this->staff(), 'admin')
            ->getJson(route('manager.users.search', ['role' => $missingRoleId, 'search' => $email]))
            ->assertOk()
            ->assertJsonPath('rows', '');
    }
}
