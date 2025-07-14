<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Bonus;
use App\Services\OneCService;
use Tests\TestCase;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock OneCService
        $this->mock(OneCService::class, function ($mock) {
            $mock->shouldReceive('updateUser')->andReturn(null);
        });
    }

    public function test_user_can_update_profile_with_new_email()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $uniqueEmail = 'test_' . time() . '@example.com';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/user/update', [
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => $uniqueEmail
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Пользователь успешно обновлен'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => $uniqueEmail
        ]);
    }

    public function test_user_can_update_profile_with_same_email()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/user/update', [
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => $user->email
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Пользователь успешно обновлен'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => $user->email
        ]);
    }

    public function test_user_can_update_profile_with_new_fields()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/user/update', [
            'birthday' => '1990-01-01',
            'children' => '2',
            'marital_status' => 'married'
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Пользователь успешно обновлен'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'birthday' => '1990-01-01 00:00:00',
            'children' => '2',
            'marital_status' => 'married'
        ]);
    }

    public function test_unauthorized_user_cannot_update_profile()
    {
        $response = $this->putJson('/api/user/update', [
            'name' => 'John Doe'
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_can_get_info()
    {
        $uniqueEmail = $this->faker->email();
        
        $user = User::factory()->create([
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => $uniqueEmail
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/user');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Данные пользователя получены',
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'gender' => 'male',
                    'city' => 'Moscow',
                    'email' => $uniqueEmail,
                    'phone' => $user->phone
                ]
            ]);
    }

    public function test_user_gets_bonus_for_completing_profile()
    {
        $user = User::factory()->create([
            'name' => null,
            'gender' => null,
            'city' => null,
            'email' => null,
            'birthday' => null,
            'children' => null,
            'marital_status' => null,
            'profile_completed_bonus_given' => false,
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        // Заполняем ВСЕ поля профиля (включая children и marital_status)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/user/update', [
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => 'john@example.com',
            'birthday' => '1990-01-01',
            'children' => '2',
            'marital_status' => 'married'
        ]);

        $response->assertOk();

        // Проверяем, что бонус начислен
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'regular',
            'status' => 'show-and-calc'
        ]);

        // Проверяем, что флаг установлен
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'profile_completed_bonus_given' => true
        ]);

        $user->refresh();
        $this->assertEquals(500, $user->bonus_amount);
    }

    public function test_user_does_not_get_bonus_twice_for_profile()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => 'john@example.com',
            'birthday' => '1990-01-01',
            'children' => '2',
            'marital_status' => 'married',
            'profile_completed_bonus_given' => true,
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;
        $initialBonusCount = Bonus::where('user_id', $user->id)->count();

        // Обновляем профиль еще раз
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/user/update', [
            'children' => '3'  // изменяем что-то
        ]);

        $response->assertOk();

        // Проверяем, что новых бонусов не добавилось
        $this->assertEquals($initialBonusCount, Bonus::where('user_id', $user->id)->count());
    }

    public function test_user_does_not_get_bonus_for_partial_profile()
    {
        $user = User::factory()->create([
            'name' => null,
            'gender' => null,
            'city' => null,
            'email' => null,
            'birthday' => null,
            'children' => null,
            'marital_status' => null,
            'profile_completed_bonus_given' => false,
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        // Заполняем только часть полей (НЕ все обязательные)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/user/update', [
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'email' => 'john@example.com',
            'birthday' => '1990-01-01'
            // НЕТ children и marital_status
        ]);

        $response->assertOk();

        // Проверяем, что бонус НЕ начислен
        $this->assertDatabaseMissing('bonuses', [
            'user_id' => $user->id,
            'amount' => 500
        ]);

        // Проверяем, что флаг НЕ установлен
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'profile_completed_bonus_given' => false
        ]);
    }

    public function test_user_does_not_get_bonus_without_email()
    {
        $user = User::factory()->create([
            'name' => null,
            'gender' => null,
            'city' => null,
            'email' => null,
            'birthday' => null,
            'children' => null,
            'marital_status' => null,
            'profile_completed_bonus_given' => false,
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        // Заполняем все поля кроме email
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/user/update', [
            'name' => 'John Doe',
            'gender' => 'male',
            'city' => 'Moscow',
            'birthday' => '1990-01-01',
            'children' => '2',
            'marital_status' => 'married'
            // email НЕ заполнен
        ]);

        $response->assertOk();

        // Проверяем, что бонус НЕ начислен
        $this->assertDatabaseMissing('bonuses', [
            'user_id' => $user->id,
            'amount' => 500
        ]);

        // Проверяем, что флаг НЕ установлен
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'profile_completed_bonus_given' => false
        ]);
    }
}
