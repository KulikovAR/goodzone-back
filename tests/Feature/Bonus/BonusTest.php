<?php

namespace Tests\Feature\Bonus;

use App\Enums\BonusLevel;
use App\Enums\NotificationType;
use App\Models\Bonus;
use App\Models\User;
use App\Services\BonusService;
use App\Services\ExpoNotificationService;
use Mockery;
use Tests\TestCase;

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
        $user  = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонусы
        $user->bonuses()->create([
            'amount'          => 100,
            'purchase_amount' => 1000,
            'type'            => 'regular',
            'created_at'      => now()->subDays(2),
        ]);

        $user->bonuses()->create([
            'amount'     => 200,
            'type'       => 'promotional',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDay(),
        ]);

        $user->bonus_amount = 300;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok'   => true,
                'data' => [
                    'bonus_amount'             => 300,
                    'bonus_amount_without'     => 300,
                    'promotional_bonus_amount' => 0,
                    'level'                    => 'bronze',
                    'cashback_percent'         => 5,
                    'total_purchase_amount'    => 1000,
                    'next_level'               => 'silver',
                    'next_level_min_amount'    => 10000,
                    'progress_to_next_level'   => 10,
                ],
            ]);
    }

    public function test_user_can_get_bonus_info_with_promo_amount()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $user->bonuses()->create([
            'amount'          => 100,
            'purchase_amount' => 5000,
            'type'            => 'regular',
        ]);

        $user->bonuses()->create([
            'amount'     => 200,
            'type'       => 'promotional',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDay(),
        ]);

        $user->bonuses()->create([
            'amount'     => 300,
            'type'       => 'promotional',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDay(),
        ]);

        $user->bonus_amount = 600;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok'   => true,
                'data' => [
                    'bonus_amount'             => 600,
                    'bonus_amount_without'     => 600,
                    'promotional_bonus_amount' => 0,
                    'level'                    => BonusLevel::BRONZE->value,
                    'cashback_percent'         => BonusLevel::BRONZE->getCashbackPercent(),
                    'total_purchase_amount'    => 5000,
                    'next_level'               => BonusLevel::SILVER->value,
                    'next_level_min_amount'    => BonusLevel::SILVER->getMinPurchaseAmount(),
                ],
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(50, $responseData['progress_to_next_level']);
    }

    public function test_user_can_get_bonus_info_with_silver_level()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонусы для серебряного уровня
        $user->bonuses()->create([
            'amount'          => 1000,
            'purchase_amount' => 15000,
            'type'            => 'regular',
        ]);

        $user->bonus_amount = 1000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok'   => true,
                'data' => [
                    'bonus_amount'          => 1000,
                    'level'                 => BonusLevel::SILVER->value,
                    'cashback_percent'      => BonusLevel::SILVER->getCashbackPercent(),
                    'total_purchase_amount' => 15000,
                    'next_level'            => BonusLevel::GOLD->value,
                    'next_level_min_amount' => BonusLevel::GOLD->getMinPurchaseAmount(),
                ],
            ]);

        // Проверяем, что прогресс к следующему уровню корректный
        $responseData = $response->json('data');
        $this->assertEquals(25, $responseData['progress_to_next_level']);
    }

    public function test_user_can_get_bonus_info_with_gold_level()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонусы для золотого уровня
        $user->bonuses()->create([
            'amount'          => 3000,
            'purchase_amount' => 35000,
            'type'            => 'regular',
        ]);

        $user->bonus_amount = 3000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJson([
                'ok'   => true,
                'data' => [
                    'bonus_amount'          => 3000,
                    'level'                 => BonusLevel::GOLD->value,
                    'cashback_percent'      => BonusLevel::GOLD->getCashbackPercent(),
                    'total_purchase_amount' => 35000,
                    'next_level'            => null,
                    'next_level_min_amount' => null,
                ],
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
        $user     = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token    = $oneCUser->createToken('test-token')->plainTextToken;

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_CREDIT,
                [
                    'amount'          => 50,
                    'purchase_amount' => 1000,
                    'phone'           => $user->phone,
                ]
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/credit', [
            'phone'           => $user->phone,
            'purchase_amount' => 1000,
            'bonus_amount'    => 50,
        ]);

        $response->assertOk()
            ->assertJson([
                'ok'      => true,
                'message' => 'Бонусы начислены',
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id'         => $user->id,
            'amount'          => 50,
            'purchase_amount' => 1000,
            'type'            => 'regular',
        ]);

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_user_can_debit_bonus()
    {
        $user     = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token    = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем бонусы
        Bonus::create([
            'user_id'         => $user->id,
            'amount'          => 1000,
            'purchase_amount' => 1000,
            'type'            => 'regular',
            'status'          => 'show-and-calc',
            'created_at'      => now()->subDays(2),
        ]);

        $user->bonus_amount = 1000;
        $user->save();

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->withArgs(function ($user, $type, $data) {
                return $type === NotificationType::BONUS_DEBIT &&
                    $data['debit_amount'] === 30 &&
                    $data['remaining_bonus'] === 970 &&
                    $data['phone'] === $user->phone;
            });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', [
            'phone'           => $user->phone,
            'debit_amount'    => 30,
            'purchase_amount' => 1000,
        ]);

        $response->assertOk()
            ->assertJson([
                'ok'      => true,
                'message' => 'Бонусы списаны',
            ]);

        $user->refresh();
        $this->assertEquals(970, $user->bonus_amount);
    }

    public function test_user_cannot_debit_more_than_available()
    {
        $user     = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token    = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем бонус
        $user->bonuses()->create([
            'amount'          => 50,
            'purchase_amount' => 1000,
            'type'            => 'regular',
        ]);

        $user->bonus_amount = 50;
        $user->save();

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);

        $this->mockPushService->shouldReceive('send')->never();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', [
            'phone'           => $user->phone,
            'debit_amount'    => 100,
            'purchase_amount' => 50,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'ok'      => false,
                'message' => 'Недостаточно бонусов',
            ]);

        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);
    }

    public function test_user_can_receive_promotional_bonus()
    {
        $user       = User::factory()->create();
        $oneCUser   = User::factory()->oneC()->create();
        $token      = $oneCUser->createToken('test-token')->plainTextToken;
        $expiryDate = now()->addYear();

        $this->mockPushService->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(User::class),
                NotificationType::BONUS_PROMOTION,
                [
                    'bonus_amount' => 100,
                    'expiry_date'  => $expiryDate->format('d.m.Y H:i'),
                    'phone'        => $user->phone,
                ]
            );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/promotion', [
            'phone'        => $user->phone,
            'bonus_amount' => 100,
            'expiry_date'  => $expiryDate->format('Y-m-d\TH:i:s'),
        ]);

        $response->assertOk()
            ->assertJson([
                'ok'      => true,
                'message' => 'Акционные бонусы начислены',
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id'    => $user->id,
            'amount'     => 100,
            'type'       => 'promotional',
            'expires_at' => $expiryDate,
        ]);

        $user->refresh();
        $this->assertEquals(100, $user->bonus_amount);
    }

    public function test_unauthorized_user_cannot_access_bonus_endpoints()
    {
        $response = $this->postJson('/api/bonus/credit', [
            'phone'           => '+79991234567',
            'purchase_amount' => 1000,
            'bonus_amount'    => 50,
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_can_get_bonus_history()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем бонусы
        $user->bonuses()->create([
            'amount'          => 100,
            'purchase_amount' => 1000,
            'type'            => 'regular',
            'created_at'      => now()->subDays(2),
        ]);

        $user->bonuses()->create([
            'amount'     => 200,
            'type'       => 'promotional',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDay(),
        ]);

        $user->bonuses()->create([
            'amount'     => -50,
            'type'       => 'debit',
            'created_at' => now(),
        ]);

        $user->bonus_amount = 250;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/bonus/history');

        $response->assertOk()
            ->assertJson([
                'ok'   => true,
                'data' => [
                    'total_count' => 0,
                    'history'     => [],
                ],
            ]);
    }

    public function test_unauthorized_user_cannot_get_bonus_history()
    {
        $response = $this->getJson('/api/bonus/history');
        $response->assertUnauthorized();
    }

    public function test_user_can_get_bonus_levels()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/bonus-level');

        $response->assertOk()
            ->assertJson([
                'ok'   => true,
                'data' => [
                    [
                        'name'                => 'bronze',
                        'cashback_percent'    => 5,
                        'min_purchase_amount' => 0,
                    ],
                    [
                        'name'                => 'silver',
                        'cashback_percent'    => 10,
                        'min_purchase_amount' => 10000,
                    ],
                    [
                        'name'                => 'gold',
                        'cashback_percent'    => 15,
                        'min_purchase_amount' => 30000,
                    ],
                ],
            ]);
    }

    public function test_unauthorized_user_cannot_get_bonus_levels()
    {
        $response = $this->getJson('/api/bonus-level');
        $response->assertStatus(200)
            ->assertJson([
                'ok'   => true,
                'data' => [
                    [
                        'name'                => 'bronze',
                        'cashback_percent'    => 5,
                        'min_purchase_amount' => 0,
                    ],
                    [
                        'name'                => 'silver',
                        'cashback_percent'    => 10,
                        'min_purchase_amount' => 10000,
                    ],
                    [
                        'name'                => 'gold',
                        'cashback_percent'    => 15,
                        'min_purchase_amount' => 30000,
                    ],
                ],
            ]);
    }
}
