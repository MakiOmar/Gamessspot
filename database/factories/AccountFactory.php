<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Region mapping with ISO 3166-1 alpha-2 country codes
        $regions = ['US', 'UK', 'JP', 'EU', 'CA'];

        return [
            'mail' => $this->faker->unique()->safeEmail,
            'password' => '123456',
            'region' => $this->faker->randomElement($regions),
            'ps4_offline_stock' => $this->faker->numberBetween(0, 100),
            'ps4_primary_stock' => $this->faker->numberBetween(0, 100),
            'ps4_secondary_stock' => $this->faker->numberBetween(0, 100),
            'ps5_offline_stock' => $this->faker->numberBetween(0, 100),
            'ps5_primary_stock' => $this->faker->numberBetween(0, 100),
            'ps5_secondary_stock' => $this->faker->numberBetween(0, 100),
            'game_id' => Game::inRandomOrder()->first()->id, // Assign a random existing game
        ];
    }
}
