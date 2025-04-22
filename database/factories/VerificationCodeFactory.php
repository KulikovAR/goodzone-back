<?php

namespace Database\Factories;

use App\Models\VerificationCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerificationCodeFactory extends Factory
{
    protected $model = VerificationCode::class;

    public function definition(): array
    {
        return [
            'phone' => fake()->numerify('+7##########'),
            'code' => fake()->numerify('####'),
            'expires_at' => now()->addMinutes(5),
            'verified_at' => null,
        ];
    }
}