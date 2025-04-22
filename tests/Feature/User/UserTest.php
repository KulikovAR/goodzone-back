<?php

namespace Tests\Feature\User;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
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

    public function test_unauthorized_user_cannot_update_profile()
    {
        $response = $this->putJson('/api/user/update', [
            'name' => 'John Doe'
        ]);

        $response->assertUnauthorized();
    }
}