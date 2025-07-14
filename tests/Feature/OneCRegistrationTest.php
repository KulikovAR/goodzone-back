<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Bonus;
use App\Services\ExpoNotificationService;
use App\Services\BonusService;
use Tests\TestCase;

class OneCRegistrationTest extends TestCase
{
    protected $mockPushService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPushService = $this->createMock(ExpoNotificationService::class);
        $this->app->instance(ExpoNotificationService::class, $this->mockPushService);
        $this->app->instance(BonusService::class, new BonusService($this->mockPushService));
    }

    public function test_1c_user_can_register_new_user_with_complete_profile()
    {
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $userData = [
            'phone' => '+7' . fake()->unique()->numerify('##########'),
            'name' => 'Иван Петров',
            'gender' => 'male',
            'city' => 'Москва',
            'email' => fake()->unique()->safeEmail(),
            'birthday' => '1990-01-15',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/register', $userData);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Пользователь успешно зарегистрирован'
            ])
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'phone',
                    'bonus_awarded'
                ]
            ]);

        // Проверяем, что пользователь создан в базе
        $this->assertDatabaseHas('users', [
            'phone' => $userData['phone'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'come_from_app' => false,
            'profile_completed_bonus_given' => false
        ]);

        // Проверяем, что phone_verified_at установлен (но не точное время)
        $user = User::where('phone', $userData['phone'])->first();
        $this->assertNotNull($user->phone_verified_at);

        // Проверяем, что пользователь создан, но профиль НЕ считается полностью заполненным
        $user = User::where('phone', $userData['phone'])->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool)$user->profile_completed_bonus_given);
        $this->assertFalse($user->isProfileCompleted()); // профиль НЕ заполнен без children/marital_status
        
        // Проверяем, что бонус НЕ начислен
        $this->assertDatabaseMissing('bonuses', [
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'regular'
        ]);
    }

    public function test_regular_user_cannot_register_user_via_1c_endpoint()
    {
        $regularUser = User::factory()->create();
        $regularToken = $regularUser->createToken('test-token')->plainTextToken;

        $userData = [
            'phone' => '+7' . fake()->unique()->numerify('##########'),
            'name' => 'Иван Петров',
            'gender' => 'male',
            'city' => 'Москва',
            'email' => fake()->unique()->safeEmail(),
            'birthday' => '1990-01-15',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $regularToken
        ])->postJson('/api/1c/register', $userData);

        $response->assertStatus(403)
            ->assertJson([
                'ok' => false,
                'message' => 'Доступ запрещен'
            ]);
    }

    public function test_registration_fails_with_duplicate_phone()
    {
        $existingUser = User::factory()->create(['phone' => '+79991234567']);
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $userData = [
            'phone' => $existingUser->phone, // Дублируем телефон
            'name' => 'Иван Петров',
            'gender' => 'male',
            'city' => 'Москва',
            'email' => fake()->unique()->safeEmail(),
            'birthday' => '1990-01-15',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_registration_fails_with_duplicate_email()
    {
        $existingUser = User::factory()->create(['email' => 'test@example.com']);
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $userData = [
            'phone' => '+7' . fake()->unique()->numerify('##########'),
            'name' => 'Иван Петров',
            'gender' => 'male',
            'city' => 'Москва',
            'email' => $existingUser->email, // Дублируем email
            'birthday' => '1990-01-15',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_missing_required_fields()
    {
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $incompleteData = [
            'phone' => '+7' . fake()->unique()->numerify('##########'),
            'name' => 'Иван Петров',
            // Отсутствуют обязательные поля
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/register', $incompleteData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'gender', 'city', 'email', 'birthday'
            ]);
    }

    public function test_registration_fails_with_invalid_gender()
    {
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $userData = [
            'phone' => '+7' . fake()->unique()->numerify('##########'),
            'name' => 'Иван Петров',
            'gender' => 'invalid_gender',
            'city' => 'Москва',
            'email' => fake()->unique()->safeEmail(),
            'birthday' => '1990-01-15',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_registration_fails_with_future_birthday()
    {
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $userData = [
            'phone' => '+7' . fake()->unique()->numerify('##########'),
            'name' => 'Иван Петров',
            'gender' => 'male',
            'city' => 'Москва',
            'email' => fake()->unique()->safeEmail(),
            'birthday' => now()->addYear()->format('Y-m-d'), // Будущая дата
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birthday']);
    }

    public function test_unauthenticated_request_fails()
    {
        $userData = [
            'phone' => '+7' . fake()->unique()->numerify('##########'),
            'name' => 'Иван Петров',
            'gender' => 'male',
            'city' => 'Москва',
            'email' => fake()->unique()->safeEmail(),
            'birthday' => '1990-01-15',
        ];

        $response = $this->postJson('/api/1c/register', $userData);

        $response->assertStatus(401);
    }
} 