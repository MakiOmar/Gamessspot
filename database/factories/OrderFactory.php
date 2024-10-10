<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'seller_id' => User::factory(),  // Assuming a user factory exists for the seller
            'account_id' => Account::factory(),  // Assuming an account factory exists
            'buyer_phone' => $this->faker->phoneNumber,
            'buyer_name' => $this->faker->name,
            'price' => $this->faker->randomFloat(2, 50, 500),  // Random price between 50 and 500
            'notes' => $this->faker->sentence,
            'sold_item' => 'ps4_offline_stock'  // Set sold_item to 'ps4_offline_stock'
        ];
    }
}
