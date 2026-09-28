<?php

namespace Database\Factories;

use App\Models\Trader;
use App\Models\TraderPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TraderPaymentFactory extends Factory
{
    protected $model = TraderPayment::class;

    public function definition(): array
    {
        return array(
            'trader_id' => Trader::factory(),
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'reference_number' => null,
            'notes' => null,
            'status' => TraderPayment::STATUS_ACTIVE,
        );
    }
}
