<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpecialPrice;

class SpecialPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert 20 special prices using the factory
        SpecialPrice::factory()->count(20)->create();
    }
}
