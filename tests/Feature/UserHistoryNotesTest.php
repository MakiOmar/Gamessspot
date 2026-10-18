<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Per-user history notes behind the manage-user-history permission.
 */
class UserHistoryNotesTest extends TestCase
{
    use DatabaseTransactions;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['phone' => '0106' . random_int(1000000, 9999999)]);
    }

    private function staffWith(array $capabilities): User
    {
        $role = Role::create(['name' => 'test-role-' . uniqid(), 'capabilities' => array_merge(['access-dashboard'], $capabilities)]);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'admin']);
        if (! $role->hasCapability('manage-user-history')) {
            $role->capabilities = array_merge($role->capabilities, ['manage-user-history']);
            $role->save();
        }
        $admin->roles()->attach($role->id);

        return $admin;
    }

    public function test_without_permission_history_is_forbidden(): void
    {
        $staff = $this->staffWith(['view-sell-log']);

        $this->actingAs($staff, 'admin')
            ->get(route('manager.users.history.index', $this->customer))
            ->assertForbidden();
    }

    public function test_add_and_list_notes(): void
    {
        $staff = $this->staffWith(['manage-user-history']);

        $this->actingAs($staff, 'admin')
            ->get(route('manager.users.history.index', $this->customer))
            ->assertOk()
            ->assertSee('No history notes yet');

        $this->actingAs($staff, 'admin')
            ->post(route('manager.users.history.store', $this->customer), ['note' => '  Prefers WhatsApp <b>contact</b> '])
            ->assertRedirect(route('manager.users.history.index', $this->customer));

        $this->assertDatabaseHas('user_notes', [
            'user_id'   => $this->customer->id,
            'author_id' => $staff->id,
            'note'      => 'Prefers WhatsApp contact',
        ]);

        $this->actingAs($staff, 'admin')
            ->get(route('manager.users.history.index', $this->customer))
            ->assertOk()
            ->assertSee('Prefers WhatsApp contact');
    }

    public function test_empty_note_is_rejected(): void
    {
        $staff = $this->staffWith(['manage-user-history']);

        $this->actingAs($staff, 'admin')
            ->post(route('manager.users.history.store', $this->customer), ['note' => '   '])
            ->assertSessionHasErrors('note');
    }

    public function test_only_author_or_admin_can_delete(): void
    {
        $author = $this->staffWith(['manage-user-history']);
        $other = $this->staffWith(['manage-user-history']);
        $note = UserNote::create(['user_id' => $this->customer->id, 'author_id' => $author->id, 'note' => 'Side info']);
        $url = route('manager.users.history.destroy', [$this->customer, $note]);

        $this->actingAs($other, 'admin')->deleteJson($url)->assertForbidden();
        $this->assertNotSoftDeleted($note);

        $this->actingAs($author, 'admin')->deleteJson($url)->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted($note);

        $second = UserNote::create(['user_id' => $this->customer->id, 'author_id' => $author->id, 'note' => 'Another']);
        $this->actingAs($this->admin(), 'admin')
            ->deleteJson(route('manager.users.history.destroy', [$this->customer, $second]))
            ->assertOk();
        $this->assertSoftDeleted($second);
    }

    public function test_note_must_belong_to_the_user_in_the_url(): void
    {
        $staff = $this->staffWith(['manage-user-history']);
        $otherCustomer = User::factory()->create();
        $note = UserNote::create(['user_id' => $otherCustomer->id, 'author_id' => $staff->id, 'note' => 'X']);

        $this->actingAs($staff, 'admin')
            ->deleteJson(route('manager.users.history.destroy', [$this->customer, $note]))
            ->assertNotFound();
    }

    public function test_quick_search_shows_history_button_for_matched_customer(): void
    {
        $staff = $this->staffWith(['view-sell-log', 'search-customer-orders', 'manage-user-history']);

        $this->actingAs($staff, 'admin')
            ->get(route('manager.orders.qsearch', ['search' => $this->customer->phone]))
            ->assertOk()
            ->assertSee(route('manager.users.history.index', $this->customer->id), false);
    }
}
