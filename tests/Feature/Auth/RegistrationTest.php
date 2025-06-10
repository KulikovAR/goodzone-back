<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\OneCService;
use Mockery;

class RegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock OneCService
        $this->mock(OneCService::class, function ($mock) {
            $mock->shouldReceive('sendRegister')->andReturn(null);
        });

        // Mock SmsService
        $this->mock(\App\Services\SmsService::class, function ($mock) {
            $mock->shouldReceive('getSessionId')->andReturn('fake-session-id');
            $mock->shouldReceive('sendSms')->andReturn(true);
        });
    }

    public function test_user_can_request_verification_code(): void
    {
        $phone = '+7' . fake()->numerify('##########');

        $response = $this->postJson('/api/login', [
            'phone' => $phone
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'message' => 'Код отправлен на телефон'
            ]);

        $this->assertDatabaseHas('verification_codes', [
            'phone' => $phone
        ]);
    }

    public function test_user_can_verify_code(): void
    {
        $phone = '+7' . fake()->numerify('##########');
        User::create([
            'phone' => $phone
        ]);
        VerificationCode::create([
            'phone' => $phone,
            'code' => '1234',
            'expires_at' => now()->addMinutes(5)
        ]);

        $response = $this->postJson('/api/verify', [
            'phone' => $phone,
            'code' => '1234'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'message' => 'Вход прошел успешно'
            ])
            ->assertJsonStructure([
                'ok',
                'message',
                'data' => ['token']
            ]);

        $this->assertNotNull($response->json('data.token'));
        $this->assertDatabaseHas('users', [
            'phone' => $phone
        ]);
        $user = User::where('phone', $phone)->first();
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_not_user_cannot_verify_code(): void
    {
        $phone = '+7' . fake()->numerify('##########');
        VerificationCode::create([
            'phone' => $phone,
            'code' => '1234',
            'expires_at' => now()->addMinutes(5)
        ]);

        $response = $this->postJson('/api/verify', [
            'phone' => $phone,
            'code' => '1234'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'ok' => false,
                'message' => 'Пользователь не найден'
            ])
            ->assertJsonStructure([
                'ok',
                'message',
            ]);

        $this->assertNull($response->json('data.token'));
    }

    public function test_user_is_created_during_login_if_not_exists(): void
    {
        $phone = '+7' . fake()->numerify('##########');
            
        $this->assertDatabaseMissing('users', ['phone' => $phone]);

        $response = $this->postJson('/api/login', [
            'phone' => $phone
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true
            ]);
        
        $this->assertDatabaseHas('users', [
            'phone' => $phone
        ]);
    }

    public function test_android_user_can_verify_code(): void
    {
        $phone = '+7' . fake()->numerify('##########');
        $deviceToken = 'ExponentPushToken[' . fake()->regexify('[A-Za-z0-9]{22}') . ']';
        
        User::create([
            'phone' => $phone
        ]);
        VerificationCode::create([
            'phone' => $phone,
            'code' => '1234',
            'expires_at' => now()->addMinutes(5)
        ]);

        $response = $this->withHeaders([
            'platform' => 'android'
        ])->postJson('/api/verify', [
            'phone' => $phone,
            'code' => '1234',
            'device_token' => $deviceToken
        ]);

        $response->assertStatus(200);
        $user = User::where('phone', $phone)->first();
        $this->assertEquals(1, $user->come_from_app);
        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'device_token' => $deviceToken,
            'platform' => 'android'
        ]);
    }

    public function test_ios_user_can_verify_code(): void
    {
        $phone = '+7' . fake()->numerify('##########');
        $deviceToken = 'ExponentPushToken[' . fake()->regexify('[A-Za-z0-9]{22}') . ']';
        
        User::create([
            'phone' => $phone
        ]);
        VerificationCode::create([
            'phone' => $phone,
            'code' => '1234',
            'expires_at' => now()->addMinutes(5)
        ]);

        $response = $this->withHeaders([
            'platform' => 'ios'
        ])->postJson('/api/verify', [
            'phone' => $phone,
            'code' => '1234',
            'device_token' => $deviceToken
        ]);

        $response->assertStatus(200);
        $user = User::where('phone', $phone)->first();
        $this->assertEquals(1, $user->come_from_app);
        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'device_token' => $deviceToken,
            'platform' => 'ios'
        ]);
    }

    public function test_web_user_can_verify_code(): void
    {
        $phone = '+7' . fake()->numerify('##########');
        User::create([
            'phone' => $phone
        ]);
        VerificationCode::create([
            'phone' => $phone,
            'code' => '1234',
            'expires_at' => now()->addMinutes(5)
        ]);

        $response = $this->postJson('/api/verify', [
            'phone' => $phone,
            'code' => '1234'
        ]);

        $response->assertStatus(200);
        $user = User::where('phone', $phone)->first();
        $this->assertEquals(0, $user->come_from_app);
    }

    public function test_user_can_logout_with_device_token(): void
    {
        $user = User::factory()->create();
        $deviceToken = 'ExponentPushToken[' . fake()->regexify('[A-Za-z0-9]{22}') . ']';
        
        $user->deviceTokens()->create([
            'device_token' => $deviceToken,
            'platform' => 'ios'
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/logout', [
            'device_token' => $deviceToken
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('user_device_tokens', [
            'user_id' => $user->id,
            'device_token' => $deviceToken
        ]);
    }

    public function test_user_can_logout_all_devices(): void
    {
        $user = User::factory()->create();
        $deviceToken1 = 'ExponentPushToken[' . fake()->regexify('[A-Za-z0-9]{22}') . ']';
        $deviceToken2 = 'ExponentPushToken[' . fake()->regexify('[A-Za-z0-9]{22}') . ']';
        
        $user->deviceTokens()->createMany([
            [
                'device_token' => $deviceToken1,
                'platform' => 'ios'
            ],
            [
                'device_token' => $deviceToken2,
                'platform' => 'android'
            ]
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/logout');

        $response->assertOk();
        $this->assertDatabaseMissing('user_device_tokens', [
            'user_id' => $user->id
        ]);
    }
}