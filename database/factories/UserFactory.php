<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Enums\UserRole;

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
            'device_token' => 'ExponentPushToken[' . Str::random(22) . ']',
            'role' => UserRole::USER->value,
            'birthday' => fake()->date(),
            'children' => fake()->randomElement(['0', '1', '2', '3+']),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
        ];
    }

    public function oneC(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => UserRole::ONE_C,
                'name' => '1C Integration',
                'phone' => '1c-' . fake()->unique()->numerify('##########'),
            ];
        });
    }
}
