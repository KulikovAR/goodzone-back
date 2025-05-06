<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(), // Changed from $this->faker->name()
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('7##########'),
            'phone_verified_at' => now(),
            'password' => Hash::make('test'),
            'remember_token' => Str::random(10),
            'device_token' => 'device_token_mock_value'.fake()->numerify('7##########'),
        ];
    }
}
