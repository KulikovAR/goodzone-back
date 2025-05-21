<?php

namespace Tests\Feature\Bonus;

use App\Models\User;
use App\Services\BonusService;
use App\Services\ExpoNotificationService;
use App\Enums\NotificationType;
use App\Enums\BonusLevel;
use Tests\TestCase;
use Carbon\Carbon;
use Mockery;

class BonusTest extends TestCase
{
    private $mockPushService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPushService = Mockery::mock(ExpoNotificationService::class);
        $this->app->instance(ExpoNotificationService::class, $this->mockPushService);

        $this->app->instance(BonusService::class, new BonusService($this->mockPushService));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_can_get_bonus_info()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонусы для разных уровней
        $user->bonuses()->create([
            'amount' => 100,
            'purchase_amount' => 5000,
            'type' => 'regular'
        ]);

        $user->bonus_amount = 100;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'bonus_amount' => 100,
                    'level' => BonusLevel::BRONZE->value,
                    'cashback_percent' => BonusLevel::BRONZE->getCashbackPercent(),
                    'total_purchase_amount' => 5000,
                    'next_level' => BonusLevel::SILVER->value,
                    'next_level_min_amount' => BonusLevel::SILVER->getMinPurchaseAmount(),
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(50, $responseData['progress_to_next_level']);
    }

    public function test_user_can_get_bonus_info_with_promo_amount()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $user->bonuses()->create([
            'amount' => 100,
            'purchase_amount' => 5000,
            'type' => 'regular'
        ]);

        $user->bonuses()->create([
            'amount' => 200,
            'type' => 'promotional',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDay()
        ]);

        $user->bonuses()->create([
            'amount' => 300,
            'type' => 'promotional',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDay()
        ]);

        $user->bonus_amount = 600;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'bonus_amount' => 600,
                    'promotional_bonus_amount' => 500,
                    'level' => BonusLevel::BRONZE->value,
                    'cashback_percent' => BonusLevel::BRONZE->getCashbackPercent(),
                    'total_purchase_amount' => 5000,
                    'next_level' => BonusLevel::SILVER->value,
                    'next_level_min_amount' => BonusLevel::SILVER->getMinPurchaseAmount(),
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(50, $responseData['progress_to_next_level']);
    }

    public function test_user_can_get_bonus_info_with_silver_level()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонусы для серебряного уровня
        $user->bonuses()->create([
            'amount' => 1000,
            'purchase_amount' => 15000,
            'type' => 'regular'
        ]);

        $user->bonus_amount = 1000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'bonus_amount' => 1000,
                    'level' => BonusLevel::SILVER->value,
                    'cashback_percent' => BonusLevel::SILVER->getCashbackPercent(),
                    'total_purchase_amount' => 15000,
                    'next_level' => BonusLevel::GOLD->value,
                    'next_level_min_amount' => BonusLevel::GOLD->getMinPurchaseAmount(),
                ]
            ]);

        // Проверяем, что прогресс к следующему уровню корректный
        $responseData = $response->json('data');
        $this->assertEquals(25, $responseData['progress_to_next_level']);
    }

    public function test_user_can_get_bonus_info_with_gold_level()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонусы для золотого уровня
        $user->bonuses()->create([
            'amount' => 3000,
            'purchase_amount' => 35000,
            'type' => 'regular'
        ]);

        $user->bonus_amount = 3000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'bonus_amount' => 3000,
                    'level' => BonusLevel::GOLD->value,
                    'cashback_percent' => BonusLevel::GOLD->getCashbackPercent(),
                    'total_purchase_amount' => 35000,
                    'next_level' => null,
                    'next_level_min_amount' => null,
                ]
            ]);

        // Проверяем, что прогресс к следующему уровню 100% (максимальный уровень)
        $responseData = $response->json('data');
        $this->assertEquals(100, $responseData['progress_to_next_level']);
    }

    public function test_unauthorized_user_cannot_get_bonus_info()
    {
        $response = $this->getJson('/api/bonus/info');
        $response->assertUnauthorized();
    }

    public function test_user_can_receive_bonus_for_purchase()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_CREDIT,
                [
                    'amount' => 50,
                    'purchase_amount' => 1000,
                    'phone' => $user->phone
                ]
            );

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

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_user_can_debit_bonus()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $user->bonus_amount = 100;
        $user->save();

        // Создаем бонус
        $user->bonuses()->create([
            'amount' => 100,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);

        $user->refresh();
        $this->assertEquals(100, $user->bonus_amount);

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_DEBIT,
                [
                    'debit_amount' => 30,
                    'remaining_bonus' => '70',
                    'phone' => $user->phone
                ]
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/debit', [
            'phone' => $user->phone,
            'debit_amount' => 30
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы списаны'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -30,
            'type' => 'regular'
        ]);

        $user->refresh();
        $this->assertEquals(70, $user->bonus_amount);
    }

    public function test_user_cannot_debit_more_than_available()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем бонус
        $user->bonuses()->create([
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular'
        ]);

        $user->bonus_amount = 50;
        $user->save();

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);

        $this->mockPushService->shouldReceive('send')->never();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/debit', [
            'phone' => $user->phone,
            'debit_amount' => 100,
            'purchase_amount' => 50,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'ok' => false,
                'message' => 'Недостаточно бонусов'
            ]);

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_user_can_receive_promotional_bonus()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;
        $expiryDate = now()->addYear();

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_PROMOTION,
                [
                    'bonus_amount' => 100,
                    'expiry_date' => $expiryDate->format('d.m.Y H:i'),
                    'phone' => $user->phone
                ]
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/bonus/promotion', [
            'phone' => $user->phone,
            'bonus_amount' => 100,
            'expiry_date' => $expiryDate->format('Y-m-d\TH:i:s')
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Акционные бонусы начислены'
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional',
            'expires_at' => $expiryDate
        ]);

        $user->refresh();
        $this->assertEquals(100, $user->bonus_amount);
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

    public function test_user_can_get_bonus_history()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем несколько бонусов
        $regularBonus = $user->bonuses()->create([
            'amount' => 100,
            'purchase_amount' => 1000,
            'type' => 'regular',
            'created_at' => now()->subDays(2)
        ]);

        $promotionalBonus = $user->bonuses()->create([
            'amount' => 200,
            'type' => 'promotional',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDay()
        ]);

        $debitBonus = $user->bonuses()->create([
            'amount' => -50,
            'type' => 'regular',
            'created_at' => now()
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/bonus/history');

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'history' => [
                        '*' => [
                            'id',
                            'amount',
                            'type',
                            'purchase_amount',
                            'expires_at',
                            'created_at'
                        ]
                    ],
                    'total_count'
                ]
            ]);

        $responseData = $response->json('data');
        
        // Проверяем общее количество
        $this->assertEquals(3, $responseData['total_count']);

        // Проверяем, что все бонусы присутствуют в ответе
        $history = collect($responseData['history']);
        
        $this->assertTrue($history->contains(function ($item) use ($regularBonus) {
            return $item['id'] === $regularBonus->id
                && $item['amount'] === (int) $regularBonus->amount
                && $item['type'] === 'regular'
                && $item['purchase_amount'] === (int) $regularBonus->purchase_amount
                && $item['expires_at'] === null;
        }));

        $this->assertTrue($history->contains(function ($item) use ($promotionalBonus) {
            return $item['id'] === $promotionalBonus->id
                && $item['amount'] === (int) $promotionalBonus->amount
                && $item['type'] === 'promotional'
                && $item['purchase_amount'] === null
                && $item['expires_at'] === $promotionalBonus->expires_at->format('Y-m-d\TH:i:s');
        }));

        $this->assertTrue($history->contains(function ($item) use ($debitBonus) {
            return $item['id'] === $debitBonus->id
                && $item['amount'] === (int) $debitBonus->amount
                && $item['type'] === 'regular'
                && $item['purchase_amount'] === null
                && $item['expires_at'] === null;
        }));

        // Проверяем, что история отсортирована по дате создания в обратном порядке
        $sortedHistory = $history->sortByDesc('created_at')->values();
        $this->assertEquals($sortedHistory->toArray(), $history->toArray());
    }

    public function test_unauthorized_user_cannot_get_bonus_history()
    {
        $response = $this->getJson('/api/bonus/history');
        $response->assertUnauthorized();
    }
}
