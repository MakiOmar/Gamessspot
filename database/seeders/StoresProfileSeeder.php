<?php

namespace Database\Seeders;

use App\Models\StoresProfile;
use Illuminate\Database\Seeder;

class StoresProfileSeeder extends Seeder
{
    public function run()
    {
        StoresProfile::factory()->count(5)->create();
    }
}