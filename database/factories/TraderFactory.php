<?php

namespace Database\Factories;

use App\Models\Trader;
use Illuminate\Database\Eloquent\Factories\Factory;

class TraderFactory extends Factory
{
    protected $model = Trader::class;

    public function definition(): array
    {
        return array(
            'name' => $this->faker->name(),
            'phone' => '010' . $this->faker->numerify('########'),
            'whatsapp' => '010' . $this->faker->numerify('########'),
            'notes' => null,
            'status' => Trader::STATUS_ACTIVE,
            'opening_balance' => 0,
            'opening_balance_date' => null,
        );
    }

    public function withOpeningBalance(float $amount, string $date = '2026-01-01'): static
    {
        return $this->state(fn () => array(
            'opening_balance' => $amount,
            'opening_balance_date' => $date,
        ));
    }
}
