<?php

namespace Tests\Feature\User;

use App\Models\User;
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
}
