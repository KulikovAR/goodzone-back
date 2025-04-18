<?php

namespace Tests\Feature\Bonus;

use App\Models\User;
use Tests\TestCase;
use Carbon\Carbon;

class BonusTest extends TestCase
{
    public function test_user_can_receive_bonus_for_purchase()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/credit', [
            'phone' => $user->phone,
            'purchase_amount' => 1000,
            'bonus_amount' => 50
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы начислены'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);
    }

    public function test_user_can_debit_bonus()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Сначала создаем бонус напрямую в БД
        $bonus = $user->bonuses()->create([
            'amount' => 100,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/debit', [
            'phone' => $user->phone,
            'debit_amount' => 30,
            'remaining_bonus' => 70
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы списаны'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 70
        ]);
    }

    public function test_user_can_receive_promotional_bonus()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/promotion', [
            'phone' => $user->phone,
            'bonus_amount' => 100,
            'expiry_date' => now()->addYear()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Акционные бонусы начислены'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional'
        ]);
    }

    public function test_unauthorized_user_cannot_access_bonus_endpoints()
    {
        $response = $this->postJson('/api/bonus/credit', [
            'phone' => '+79991234567',
            'purchase_amount' => 1000,
            'bonus_amount' => 50
        ]);

        $response->assertUnauthorized();
    }
}