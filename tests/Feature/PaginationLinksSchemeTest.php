<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaginationLinksSchemeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_account_search_pagination_links_follow_forced_https_scheme(): void
    {
        $role = Role::firstOrCreate(array('name' => 'admin'));
        $role->capabilities = app(RolePermissionService::class)->defaultMatrix()['admin'];
        $role->save();
        $admin = User::factory()->create();
        $admin->roles()->sync(array($role->id));

        $game = Game::factory()->create(array('title' => 'Pagination Scheme Game ' . uniqid()));
        Account::factory()->count(21)->create(array('game_id' => $game->id));

        URL::forceScheme('https');

        $pagination = $this->actingAs($admin->fresh(), 'admin')
            ->getJson(route('manager.accounts.search', array('search' => $game->title)))
            ->assertOk()
            ->json('pagination');

        $this->assertStringContainsString('https://localhost/manager/accounts/search?', $pagination);
        $this->assertStringNotContainsString('http://localhost/manager/accounts/search?', $pagination);
    }
}
