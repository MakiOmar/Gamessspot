<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\RolePermissionService;
use App\Services\UserAccessRevoker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Containment: inactive staff, revoked sessions, zero/below-cost gift card sales.
 */
class ManagerAccessContainmentTest extends TestCase
{
    use DatabaseTransactions;

    private string $tag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->tag = uniqid();
    }

    public function test_inactive_staff_cannot_open_manager_pages(): void
    {
        $user = $this->staffUser(['is_active' => 0]);

        $this->actingAs($user, 'admin')
            ->withSession(['admin_auth_at' => now()->getTimestamp()])
            ->get(route('manager.dashboard'))
            ->assertRedirect(route('manager.login'));

        $this->assertGuest('admin');
    }

    public function test_deactivate_revokes_tokens_and_kills_open_session(): void
    {
        $actor = $this->staffUser();
        $target = $this->staffUser();
        $token = $target->createToken('API Token', ['orders:receive'])->plainTextToken;

        $this->actingAs($actor, 'admin')
            ->postJson(route('users.toggleStatus', $target->id), ['action' => 'deactivate'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $target->refresh();
        $this->assertSame(0, (int) $target->is_active);
        $this->assertNotNull($target->auth_revoked_at);
        $this->assertNull($target->remember_token);
        $this->assertSame(0, $target->tokens()->count());

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();

        $this->actingAs($target, 'admin')
            ->withSession(['admin_auth_at' => now()->subMinute()->getTimestamp()])
            ->get(route('manager.dashboard'))
            ->assertRedirect(route('manager.login'));
    }

    public function test_sell_card_rejects_zero_price(): void
    {
        [$seller, $payload] = $this->sellCardFixture(cost: 50);

        $this->actingAs($seller, 'admin')
            ->postJson(route('manager.orders.sell.card'), array_merge($payload, ['price' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_sell_card_rejects_price_below_card_cost(): void
    {
        [$seller, $payload] = $this->sellCardFixture(cost: 80);

        $this->actingAs($seller, 'admin')
            ->postJson(route('manager.orders.sell.card'), array_merge($payload, ['price' => 10]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_sell_card_accepts_price_at_or_above_cost(): void
    {
        [$seller, $payload] = $this->sellCardFixture(cost: 40);

        $this->actingAs($seller, 'admin')
            ->postJson(route('manager.orders.sell.card'), array_merge($payload, ['price' => 40]))
            ->assertCreated()
            ->assertJson(['success' => true]);
    }

    public function test_cards_index_requires_manage_gift_cards(): void
    {
        $role = Role::firstOrCreate(['name' => 'no-cards-'.$this->tag]);
        $role->capabilities = ['access-dashboard', 'view-sell-log', 'manage-sell-log'];
        $role->save();
        $user = User::factory()->create(['is_active' => 1, 'password' => Hash::make('password')]);
        $user->roles()->sync([$role->id]);

        $this->actingAs($user->fresh()->load('roles'), 'admin')
            ->withSession(['admin_auth_at' => now()->getTimestamp()])
            ->get(route('cards.index'))
            ->assertForbidden();
    }

    public function test_api_active_middleware_blocks_deactivated_token_holder(): void
    {
        $user = User::factory()->create(['is_active' => 1]);
        Sanctum::actingAs($user, ['orders:receive']);

        app(UserAccessRevoker::class)->revoke($user);
        $user->forceFill(['is_active' => 0])->save();

        $this->getJson('/api/user')->assertForbidden();
    }

    /**
     * @return array{0: User, 1: array<string, mixed>}
     */
    private function sellCardFixture(float $cost): array
    {
        $store = StoresProfile::factory()->create();
        $category = CardCategory::create([
            'name' => 'Contain '.$this->tag,
            'price' => max(100, $cost + 20),
            'supplier_mode' => CardCategory::MODE_LOCAL_ONLY,
        ]);
        Card::create([
            'code' => 'C-'.$this->tag,
            'cost' => $cost,
            'card_category_id' => $category->id,
            'status' => true,
            'source' => Card::SOURCE_MANUAL,
        ]);

        $seller = $this->staffUser();

        return [$seller, [
            'card_category_id' => $category->id,
            'store_profile_id' => $store->id,
            'buyer_phone' => '0101'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer '.$this->tag,
            'price' => $cost,
        ]];
    }

    private function staffUser(array $overrides = []): User
    {
        $caps = app(RolePermissionService::class)->defaultMatrix()['admin'];
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->capabilities = $caps;
        $role->save();

        $user = User::factory()->create(array_merge([
            'email' => 'staff_'.$this->tag.'_'.uniqid().'@example.com',
            'is_active' => 1,
            'password' => Hash::make('password'),
        ], $overrides));
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }
}
