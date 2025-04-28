<?php

namespace Database\Factories;

use App\Models\Bonus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BonusFactory extends Factory
{
    protected $model = Bonus::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'type' => $this->faker->randomElement(['regular', 'promotional']),
            'purchase_amount' => $this->faker->randomFloat(2, 100, 10000),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+3 months')
        ];
    }
}