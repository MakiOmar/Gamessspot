<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create 10 accounts, each associated with a game
        Game::factory()->count(50)->create();
    }
}
