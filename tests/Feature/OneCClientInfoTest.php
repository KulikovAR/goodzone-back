<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;
use App\Services\ExpoNotificationService;
use App\Services\BonusService;

class OneCClientInfoTest extends TestCase
{
    protected $mockPushService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPushService = $this->createMock(ExpoNotificationService::class);
        $this->app->instance(ExpoNotificationService::class, $this->mockPushService);
        $this->app->instance(BonusService::class, new BonusService($this->mockPushService));
    }

    public function test_1c_user_can_get_client_info()
    {
        // Создаем 1C пользователя
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем обычного пользователя с бонусами
        $regularUser = User::factory()->create([
            'phone' => '+79991234567',
            'bonus_amount' => 1500,
            'purchase_amount' => 50000
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/client-info', [
            'phones' => [$regularUser->phone]
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'message',
                'data' => [
                    'clients' => [
                        '*' => [
                            'phone',
                            'is_registered',
                            'cashback_percent',
                            'bonus_amount',
                            'level',
                            'total_purchase_amount'
                        ]
                    ],
                    'total_count',
                    'registered_count',
                    'unregistered_count'
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(1, $responseData['total_count']);
        $this->assertEquals(1, $responseData['registered_count']);
        $this->assertEquals(0, $responseData['unregistered_count']);

        $client = $responseData['clients'][0];
        $this->assertEquals($regularUser->phone, $client['phone']);
        $this->assertTrue($client['is_registered']);
        $this->assertEquals(1500, $client['bonus_amount']);
        $this->assertEquals(50000, $client['total_purchase_amount']);
    }

    public function test_1c_user_can_get_info_for_multiple_clients()
    {
        // Создаем 1C пользователя
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем зарегистрированных пользователей
        $user1 = User::factory()->create([
            'phone' => '+79991234567',
            'bonus_amount' => 1500,
            'purchase_amount' => 50000
        ]);

        $user2 = User::factory()->create([
            'phone' => '+79991234568',
            'bonus_amount' => 2500,
            'purchase_amount' => 100000
        ]);

        // Номера телефонов: один зарегистрирован, один нет
        $phones = [$user1->phone, $user2->phone, '+79991234569'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/client-info', [
            'phones' => $phones
        ]);

        $response->assertOk();

        $responseData = $response->json('data');
        $this->assertEquals(3, $responseData['total_count']);
        $this->assertEquals(2, $responseData['registered_count']);
        $this->assertEquals(1, $responseData['unregistered_count']);

        $clients = $responseData['clients'];
        
        // Проверяем первого пользователя
        $this->assertEquals($user1->phone, $clients[0]['phone']);
        $this->assertTrue($clients[0]['is_registered']);
        $this->assertEquals(1500, $clients[0]['bonus_amount']);

        // Проверяем второго пользователя
        $this->assertEquals($user2->phone, $clients[1]['phone']);
        $this->assertTrue($clients[1]['is_registered']);
        $this->assertEquals(2500, $clients[1]['bonus_amount']);

        // Проверяем незарегистрированного пользователя
        $this->assertEquals('+79991234569', $clients[2]['phone']);
        $this->assertFalse($clients[2]['is_registered']);
        $this->assertNull($clients[2]['bonus_amount']);
        $this->assertNull($clients[2]['cashback_percent']);
        $this->assertNull($clients[2]['level']);
        $this->assertNull($clients[2]['total_purchase_amount']);
    }

    public function test_regular_user_cannot_access_client_info_endpoint()
    {
        $regularUser = User::factory()->create();
        $regularToken = $regularUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $regularToken
        ])->postJson('/api/1c/client-info', [
            'phones' => ['+79991234567']
        ]);

        $response->assertStatus(403);
    }

    public function test_validation_requires_phones_array()
    {
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/client-info', [
            'phones' => 'not_an_array'
        ]);

        $response->assertStatus(422);
    }

    public function test_validation_requires_valid_phone_format()
    {
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/client-info', [
            'phones' => ['invalid_phone', '+79991234567']
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_correct_cashback_percent_based_on_level()
    {
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем пользователя с разными уровнями покупок
        $bronzeUser = User::factory()->create([
            'phone' => '+79991234567',
            'bonus_amount' => 100,
            'purchase_amount' => 5000 // Бронзовый уровень
        ]);

        $silverUser = User::factory()->create([
            'phone' => '+79991234568',
            'bonus_amount' => 500,
            'purchase_amount' => 15000 // Серебряный уровень (10000-29999)
        ]);

        $goldUser = User::factory()->create([
            'phone' => '+79991234569',
            'bonus_amount' => 1000,
            'purchase_amount' => 150000 // Золотой уровень (30000+)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/client-info', [
            'phones' => [$bronzeUser->phone, $silverUser->phone, $goldUser->phone]
        ]);

        $response->assertOk();

        $clients = $response->json('data.clients');
        
        // Проверяем проценты кэшбека для разных уровней
        $this->assertEquals(5, $clients[0]['cashback_percent']); // Бронза
        $this->assertEquals(10, $clients[1]['cashback_percent']); // Серебро
        $this->assertEquals(15, $clients[2]['cashback_percent']); // Золото
    }

    public function test_1c_user_can_get_info_for_single_phone()
    {
        // Создаем 1C пользователя
        $oneCUser = User::factory()->oneC()->create();
        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем обычного пользователя
        $regularUser = User::factory()->create([
            'phone' => '+79991234567',
            'bonus_amount' => 1500,
            'purchase_amount' => 50000
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken
        ])->postJson('/api/1c/client-info', [
            'phones' => [$regularUser->phone]
        ]);

        $response->assertOk();

        $responseData = $response->json('data');
        $this->assertEquals(1, $responseData['total_count']);
        $this->assertEquals(1, $responseData['registered_count']);
        $this->assertEquals(0, $responseData['unregistered_count']);

        $client = $responseData['clients'][0];
        $this->assertEquals($regularUser->phone, $client['phone']);
        $this->assertTrue($client['is_registered']);
        $this->assertEquals(1500, $client['bonus_amount']);
    }
} 