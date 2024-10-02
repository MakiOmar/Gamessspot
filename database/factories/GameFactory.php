<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $games = [
            'The Last of Us',
            'God of War',
            'Uncharted 4',
            'Horizon Zero Dawn',
            'Spider-Man',
            'Red Dead Redemption 2',
            'Ghost of Tsushima',
            'Assassin’s Creed Valhalla',
            'Cyberpunk 2077',
            'FIFA 23'
        ];

        return [
            'title' => $this->faker->randomElement($games),
            'code' => strtoupper($this->faker->unique()->bothify('GAME-#####')),
            'full_price' => $this->faker->randomFloat(2, 30, 100),
            'ps4_primary_price' => $this->faker->randomFloat(2, 20, 80),
            'ps4_primary_status' => $this->faker->boolean(),
            'ps4_secondary_price' => $this->faker->randomFloat(2, 10, 50),
            'ps4_secondary_status' => $this->faker->boolean(),
            'ps4_offline_price' => $this->faker->randomFloat(2, 5, 40),
            'ps4_offline_status' => $this->faker->boolean(),
            'ps5_primary_price' => $this->faker->randomFloat(2, 20, 80),
            'ps5_primary_status' => $this->faker->boolean(),
            'ps5_offline_price' => $this->faker->randomFloat(2, 10, 50),
            'ps5_offline_status' => $this->faker->boolean(),
            'ps4_image_url' => 'images/default-game.webp',
            'ps5_image_url' => 'images/default-game.webp',
        ];
    }
}
