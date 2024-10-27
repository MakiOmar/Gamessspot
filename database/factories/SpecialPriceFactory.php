<?php

namespace Database\Factories;

use App\Models\SpecialPrice;
use App\Models\Game;
use App\Models\StoresProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialPriceFactory extends Factory
{
    // Define the model that the factory is for
    protected $model = SpecialPrice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'game_id' => Game::inRandomOrder()->first()->id,  // Create a new Game or use an existing one
            'store_profile_id' => StoresProfile::inRandomOrder()->first()->id,  // Create a new StoreProfile or use an existing one
            'ps4_primary_price' => $this->faker->randomFloat(2, 10, 100), // Random price between 10 and 100
            'ps4_secondary_price' => $this->faker->randomFloat(2, 10, 100),
            'ps4_offline_price' => $this->faker->randomFloat(2, 10, 100),
            'ps5_primary_price' => $this->faker->randomFloat(2, 10, 100),
            'ps5_secondary_price' => $this->faker->randomFloat(2, 10, 100),
            'ps5_offline_price' => $this->faker->randomFloat(2, 10, 100),
        ];
    }
}
