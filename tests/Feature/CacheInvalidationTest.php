<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Role;
use App\Models\SpecialPrice;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\CacheManager;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use DatabaseTransactions;

    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(array('name' => $roleName));
        $matrix = app(RolePermissionService::class)->defaultMatrix();
        $role->capabilities = $matrix[$roleName] ?? array();
        $role->save();

        $user = User::factory()->create();
        $user->roles()->sync(array($role->id));

        return $user->fresh()->load('roles');
    }

    protected function primeGameListing(): string
    {
        CacheManager::getGameListing('ps4', 1, fn () => 'cached-games');
        $key = CacheManager::getGameListingKey('ps4', 1);
        $this->assertSame('cached-games', CacheManager::get($key));

        return $key;
    }

    public function test_group_invalidation_is_deferred_until_commit(): void
    {
        $key = $this->primeGameListing();

        DB::transaction(function () use ($key) {
            CacheManager::invalidateGames();
            $this->assertSame('cached-games', CacheManager::get($key));
        });

        $this->assertNull(CacheManager::get($key));
    }

    public function test_rolled_back_transaction_keeps_cache(): void
    {
        $key = $this->primeGameListing();

        try {
            DB::transaction(function () {
                CacheManager::invalidateGames();
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame('cached-games', CacheManager::get($key));
    }

    public function test_new_account_invalidates_game_listings(): void
    {
        $game = Game::factory()->create();
        $key = $this->primeGameListing();

        Account::factory()->create(array('game_id' => $game->id));

        $this->assertNull(CacheManager::get($key));
    }

    public function test_special_price_change_invalidates_game_listings(): void
    {
        $game = Game::factory()->create();
        $store = StoresProfile::factory()->create();
        $key = $this->primeGameListing();

        SpecialPrice::factory()->create(array('game_id' => $game->id, 'store_profile_id' => $store->id));

        $this->assertNull(CacheManager::get($key));
    }

    public function test_invalidating_one_group_leaves_other_groups_cached(): void
    {
        $gamesKey = $this->primeGameListing();
        CacheManager::getAccountListing(1, fn () => 'cached-accounts');
        $accountsKey = CacheManager::getAccountListingKey(1);

        CacheManager::invalidateAccounts();

        $this->assertNull(CacheManager::get($accountsKey));
        $this->assertSame('cached-games', CacheManager::get($gamesKey));
    }

    public function test_clear_key_requires_authentication(): void
    {
        $this->post('/cache/clear-key', array('key' => 'games:list:ps4:page:1'))
            ->assertRedirect();
    }

    public function test_clear_key_rejects_non_listing_keys(): void
    {
        $user = $this->createUserWithRole('sales');

        $this->actingAs($user, 'admin')
            ->postJson('/cache/clear-key', array('key' => 'dashboard:stats'))
            ->assertStatus(403);

        $this->actingAs($user, 'admin')
            ->postJson('/cache/clear-key', array('key' => ''))
            ->assertStatus(400);
    }

    public function test_clear_key_clears_listing_key_for_staff(): void
    {
        $user = $this->createUserWithRole('sales');
        $key = $this->primeGameListing();

        $this->actingAs($user, 'admin')
            ->postJson('/cache/clear-key', array('key' => $key))
            ->assertOk()
            ->assertJson(array('success' => true));

        $this->assertNull(CacheManager::get($key));
    }
}
