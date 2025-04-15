<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\VerificationCode;

class RegistrationTest extends TestCase
{
    public function test_user_can_request_verification_code(): void
    {
        $response = $this->postJson('/api/login', [
            'phone' => '+79991234567'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Код отправлен на телефон'
            ]);

        $this->assertDatabaseHas('verification_codes', [
            'phone' => '+79991234567'
        ]);
    }

    public function test_user_can_verify_code(): void
    {
        $phone = '+79991234567';
        VerificationCode::create([
            'phone' => $phone,
            'code' => '1234',
            'expires_at' => now()->addMinutes(5)
        ]);

        $response = $this->postJson('/api/check', [
            'phone' => $phone,
            'code' => '1234'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Вход прошел успешно'
            ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'phone' => '+79991234567',
            'already_in_app' => true
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Регистрация успешна'
            ]);
    }
}