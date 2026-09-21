<?php

namespace Database\Factories;

use App\Models\Foundation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Foundation>
 */
class FoundationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Yayasan '.fake()->unique()->company(),
            'code' => fake()->unique()->bothify('YYS-####-????'),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }
}
