<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_success(): void
    {
        $response = $this->json(
            'post',
            route('auth.login'),
            [
                'phone' => User::factory()->raw()['phone'],
            ]
        );

        $response->assertJsonStructure(
            [
                'ok',
                'message',
                'data' => ['code'],
            ]
        );

        $response->assertStatus(200);
    }

    public function test_login_validation_error(): void
    {
        $response = $this->json(
            'post',
            route('auth.login'),
            [
                'phone' => null,
            ]
        );

        $response->assertJsonStructure(
            [
                'message',
                'errors' => ['phone'],
            ]
        );

        $response->assertStatus(422);
    }

    public function test_verify_success(): void
    {
        $code = rand(10000, 99999);
        $user = User::factory()->create([
            'code' => md5($code),
        ]);

        $response = $this->json(
            'post',
            route('auth.verify'),
            [
                'phone' => $user->phone,
                'code' => $code,
            ]
        );

        $response->assertJsonStructure(
            [
                'ok',
                'message',
                'data' => [
                    'refreshToken',
                    'expiredRefresh',
                    'token',
                    'expired',
                ],
            ]
        );

        $response->assertStatus(200);
    }
}
