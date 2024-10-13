<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoresProfile>
 */
class StoresProfileFactory extends Factory
{
    protected $model = \App\Models\StoresProfile::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company, // Generates a fake company name
            'phone_number' => $this->faker->phoneNumber, // Generates a fake phone number
        ];
    }
}

