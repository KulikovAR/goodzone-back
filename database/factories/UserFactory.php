<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Enums\UserRole;
use App\Models\User;

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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('7##########'),
            'phone_verified_at' => now(),
            'password' => Hash::make('test'),
            'remember_token' => Str::random(10),
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

    public function withDeviceToken(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->deviceTokens()->create([
                'device_token' => 'ExponentPushToken[' . Str::random(22) . ']',
                'platform' => fake()->randomElement(['ios', 'android'])
            ]);
        });
    }
}
