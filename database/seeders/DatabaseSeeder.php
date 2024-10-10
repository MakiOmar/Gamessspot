<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;

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
        Order::factory()->count(10)->create();  // This will create 10 random orders
    }
}
