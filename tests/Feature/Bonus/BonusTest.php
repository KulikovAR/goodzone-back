<?php

namespace Tests\Feature\Bonus;

use App\Models\User;
use App\Services\BonusService;
use App\Services\PushNotificationService;
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

        $this->mockPushService = Mockery::mock(PushNotificationService::class);
        $this->app->instance(PushNotificationService::class, $this->mockPushService);

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
        $token = $user->createToken('test-token')->plainTextToken;

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
        $token = $user->createToken('test-token')->plainTextToken;

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
                    'remaining_bonus' => '70.00',
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
        $token = $user->createToken('test-token')->plainTextToken;

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
        $token = $user->createToken('test-token')->plainTextToken;
        $expiryDate = now()->addYear();

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_PROMOTION,
                [
                    'bonus_amount' => 100,
                    'expiry_date' => $expiryDate->format('Y-m-d\TH:i:s'),
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
}
