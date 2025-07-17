<?php

namespace Tests\Feature\Bonus;

use App\Enums\NotificationType;
use App\Models\Bonus;
use App\Models\User;
use App\Services\BonusService;
use App\Services\ExpoNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BonusTest extends TestCase
{
    use RefreshDatabase;

    private $mockPushService;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем мок для push уведомлений
        $this->mockPushService = Mockery::mock(ExpoNotificationService::class);
        $this->app->instance(ExpoNotificationService::class, $this->mockPushService);

        // Разрешаем любые вызовы send по умолчанию
        $this->mockPushService->shouldReceive('send')->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_bonus_endpoints()
    {
        $response = $this->getJson('/api/bonus/info');
        $response->assertStatus(401);

        $response = $this->postJson('/api/bonus/credit', []);
        $response->assertStatus(401);

        $response = $this->postJson('/api/bonus/debit', []);
        $response->assertStatus(401);

        $response = $this->postJson('/api/bonus/refund', []);
        $response->assertStatus(401);
    }

    #[Test]
    public function regular_user_cannot_access_1c_protected_endpoints()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/credit', [
            'phone' => $user->phone,
            'purchase_amount' => 1000,
            'id_sell' => 'TEST_RECEIPT_' . time(),
        ]);

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', [
            'phone' => $user->phone,
            'debit_amount' => 100,
            'id_sell' => 'TEST_DEBIT_' . time(),
        ]);

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [
            'phone' => $user->phone,
            'refund_amount' => 100,
            'id_sell' => 'REFUND_123',
            'parent_id_sell' => 'PARENT_123',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function one_c_user_can_access_protected_endpoints()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/credit', [[
            'phone' => $user->phone,
            'purchase_amount' => 1000,
            'id_sell' => 'TEST_RECEIPT_' . time(),
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertOk();
    }

    #[Test]
    public function user_can_get_bonus_info()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/bonus/info');

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'bonus_amount',
                    'bonus_amount_without',
                    'promotional_bonus_amount',
                    'level',
                    'cashback_percent',
                    'total_purchase_amount',
                    'next_level',
                    'next_level_min_amount',
                    'progress_to_next_level',
                ],
            ]);
    }

    #[Test]
    public function one_c_user_can_credit_bonus_to_user()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/credit', [[
            'phone' => $user->phone,
            'purchase_amount' => 1000,
            'id_sell' => 'TEST_RECEIPT_' . time(),
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы начислены',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'success',
                        'data' => [
                            'calculated_bonus_amount',
                            'user_level',
                            'cashback_percent',
                        ]
                    ]
                ],
            ]);

        // Проверяем, что бонус был создан в базе данных
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'type' => 'regular',
            'purchase_amount' => 1000,
        ]);
    }

    #[Test]
    public function one_c_user_can_debit_bonus_from_user()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        // Создаем исходный чек покупки
        Bonus::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => 'PARENT_RECEIPT_123',
            'purchase_amount' => 1000,
        ]);

        $user->bonus_amount = 1000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', [[
            'phone' => $user->phone,
            'debit_amount' => 300, // 30% от 1000 (purchase_amount)
            'id_sell' => 'TEST_DEBIT_' . time(),
            'parent_id_sell' => 'TEST_DEBIT_PARENT_' . time(),
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы списаны',
            ]);

        // Проверяем, что запись о списании была создана
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -300,
            'type' => 'regular',
            'status' => 'show-not-calc',
            'parent_id_sell' => 'PARENT_RECEIPT_123',
        ]);
    }

    #[Test]
    public function one_c_user_can_refund_bonus_to_user()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $parentReceiptId = 'PARENT_RECEIPT_123';
        $refundReceiptId = 'REFUND_RECEIPT_456';

        // Создаем исходную покупку
        Bonus::create([
            'user_id' => $user->id,
            'amount' => 50, // 5% от 1000
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId,
        ]);

        $user->bonus_amount = 50;
        $user->purchase_amount = 1000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [[
            'phone' => $user->phone,
            'refund_amount' => 500,
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId,
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы возвращены (возврат товара)',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'success',
                        'data' => [
                            'refunded_bonus_amount',
                            'returned_debit_amount',
                        ]
                    ]
                ],
            ]);

        // Проверяем, что запись о возврате была создана
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -25, // 5% от 500
            'type' => 'refund',
            'purchase_amount' => 500,
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId,
        ]);

        // Проверяем, что сумма покупок уменьшилась
        $user->refresh();
        $this->assertEquals(500, $user->purchase_amount);
    }

    #[Test]
    public function one_c_user_can_credit_promotional_bonus()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $payload = [[
            'phone' => $user->phone,
            'bonus_amount' => 300,
            'expiry_date' => now()->addDays(10)->toDateString(),
            'id_sell' => 'PROMO_1_' . time(),
            'timestamp' => now()->toDateTimeString(),
        ]];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/promotion', $payload);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Акционные бонусы начислены',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'success',
                        'data',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 300,
            'type' => 'promotional',
        ]);
    }

    #[Test]
    public function refund_returns_proportional_debited_bonuses()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $parentReceiptId = 'PARENT_RECEIPT_123';
        $refundReceiptId = 'REFUND_RECEIPT_456';

        // Создаем исходную покупку
        Bonus::create([
            'user_id' => $user->id,
            'amount' => 50, // 5% от 1000
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId,
        ]);

        // Создаем техническую запись списания (было списано 25 бонусов)
        Bonus::create([
            'user_id' => $user->id,
            'amount' => -25,
            'type' => 'regular',
            'status' => 'calc-not-show',
            'parent_id_sell' => $parentReceiptId,
        ]);

        $user->bonus_amount = 25; // 50 - 25
        $user->purchase_amount = 1000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [[
            'phone' => $user->phone,
            'refund_amount' => 500, // возвращаем 50% от покупки
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId,
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertOk();

        // Проверяем, что создалась запись возврата начисленных бонусов
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -25, // 5% от 500
            'type' => 'refund',
            'purchase_amount' => 500,
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId,
        ]);

        // Проверяем, что создалась запись возврата списанных бонусов
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 12.5, // 50% от списанных 25
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $refundReceiptId . '_DEBIT_REFUND',
            'parent_id_sell' => $parentReceiptId,
        ]);
    }

    #[Test]
    public function refund_does_not_return_promotional_bonuses()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $parentReceiptId = 'PARENT_RECEIPT_123';
        $refundReceiptId = 'REFUND_RECEIPT_456';

        // Создаем исходную покупку
        Bonus::create([
            'user_id' => $user->id,
            'amount' => 50, // 5% от 1000
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId,
        ]);

        // Создаем промо-бонус (который НЕ должен возвращаться)
        Bonus::create([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional',
            'status' => 'show-and-calc',
            'expires_at' => now()->addDays(30),
        ]);

        $user->bonus_amount = 150; // 50 + 100
        $user->purchase_amount = 1000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [[
            'phone' => $user->phone,
            'refund_amount' => 500,
            'id_sell' => $refundReceiptId,
            'parent_id_sell' => $parentReceiptId,
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertOk();

        // Проверяем, что промо-бонус остался нетронутым
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'promotional',
        ]);

        // Проверяем, что создалась только запись возврата обычных бонусов
        $this->assertDatabaseHas('bonuses', [
            'user_id' => $user->id,
            'amount' => -25, // 5% от 500
            'type' => 'refund',
            'purchase_amount' => 500,
        ]);

        $user->refresh();
        $this->assertEquals(125, $user->bonus_amount); // 150 - 25
    }

    #[Test]
    public function cannot_debit_more_bonus_than_user_has()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        // Устанавливаем небольшое количество бонусов
        $user->bonus_amount = 100;
        $user->save();

        $idSell = 'TEST_DEBIT_' . time();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', [[
            'phone' => $user->phone,
            'debit_amount' => 500,
            'id_sell' => $idSell,
            'parent_id_sell' => 'TEST_DEBIT_PARENT_' . time(),
            'timestamp' => now()->toDateTimeString()
        ]]);
        
        $response->assertStatus(400)
            ->assertJsonFragment([
                'id' => $idSell,
                'success' => false,
                'error' => 'Недостаточно бонусов',
            ])
            ->assertJson([
                'ok' => false,
                'message' => 'Не удалось списать бонусы',
            ]);
    }

    #[Test]
    public function refund_requires_valid_parent_receipt()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [[
            'phone' => $user->phone,
            'refund_amount' => 500,
            'id_sell' => 'REFUND_123',
            'parent_id_sell' => 'NONEXISTENT_RECEIPT',
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'id' => 'REFUND_123',
                'success' => false,
                'error' => 'Исходный чек продажи с ID NONEXISTENT_RECEIPT не найден для данного пользователя',
            ])
            ->assertJson([
                'ok' => false,
                'message' => 'Не удалось вернуть бонусы',
            ]);
    }

    #[Test]
    public function refund_prevents_excessive_refund_amount()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $parentReceiptId = 'PARENT_RECEIPT_123';

        // Создаем исходную покупку на 1000 рублей
        Bonus::create([
            'user_id' => $user->id,
            'amount' => 50,
            'purchase_amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'id_sell' => $parentReceiptId,
        ]);

        $user->purchase_amount = 1000;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [[
            'phone' => $user->phone,
            'refund_amount' => 1200, // пытаемся вернуть больше чем было куплено
            'id_sell' => 'REFUND_123',
            'parent_id_sell' => $parentReceiptId,
            'timestamp' => now()->toDateTimeString()
        ]]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'id' => 'REFUND_123',
                'success' => false,
                'error' => 'Сумма возврата превышает сумму исходной покупки. Уже возвращено: 0, попытка возврата: 1200, исходная сумма: 1000',
            ])
            ->assertJson([
                'ok' => false,
                'message' => 'Не удалось вернуть бонусы',
            ]);
    }

    #[Test]
    public function credit_bonus_requires_valid_phone()
    {
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/credit', [
            'phone' => '1234567890', // несуществующий телефон
            'purchase_amount' => 1000,
            'id_sell' => 'TEST_RECEIPT_' . time(),
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function debit_bonus_requires_valid_phone()
    {
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', [
            'phone' => '1234567890', // несуществующий телефон
            'debit_amount' => 100,
            'id_sell' => 'TEST_DEBIT_' . time(),
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function refund_bonus_requires_valid_phone()
    {
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [
            'phone' => '1234567890', // несуществующий телефон
            'refund_amount' => 100,
            'id_sell' => 'REFUND_123',
            'parent_id_sell' => 'PARENT_123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function credit_bonus_requires_positive_purchase_amount()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/credit', [
            'phone' => $user->phone,
            'purchase_amount' => -100,
            'id_sell' => 'TEST_RECEIPT_' . time(),
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function debit_bonus_requires_positive_debit_amount()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', [
            'phone' => $user->phone,
            'debit_amount' => -100,
            'id_sell' => 'TEST_DEBIT_' . time(),
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function refund_bonus_requires_positive_refund_amount()
    {
        $user = User::factory()->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', [
            'phone' => $user->phone,
            'refund_amount' => -100,
            'id_sell' => 'REFUND_123',
            'parent_id_sell' => 'PARENT_123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function user_can_get_bonus_history()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем несколько бонусных записей
        Bonus::create([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'regular',
            'status' => 'show-and-calc',
            'created_at' => now()->subDays(2),
        ]);

        Bonus::create([
            'user_id' => $user->id,
            'amount' => -50,
            'type' => 'regular',
            'status' => 'show-not-calc',
            'created_at' => now()->subDay(),
        ]);

        Bonus::create([
            'user_id' => $user->id,
            'amount' => -25,
            'type' => 'refund',
            'status' => 'show-and-calc',
            'created_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
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
                            'created_at',
                        ],
                    ],
                    'total_count',
                ],
            ]);
    }

    #[Test]
    public function one_c_user_can_credit_bonus_to_multiple_users()
    {
        $users = User::factory()->count(3)->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $payload = $users->map(function ($user, $index) {
            return [
                'phone' => $user->phone,
                'purchase_amount' => 1000 + $index * 500, // 1000, 1500
                'id_sell' => 'BATCH_CREDIT_' . $index . '_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ];
        })->toArray();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/credit', $payload);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы начислены',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'success',
                        'data' => [
                            'calculated_bonus_amount',
                            'user_level',
                            'cashback_percent',
                        ]
                    ]
                ],
            ]);

        foreach ($users as $user) {
            $this->assertDatabaseHas('bonuses', [
                'user_id' => $user->id,
                'type' => 'regular',
            ]);
        }
    }

    #[Test]
    public function one_c_user_can_debit_bonus_from_multiple_users()
    {
        $users = User::factory()->count(2)->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        foreach ($users as $user) {
            Bonus::create([
                'user_id' => $user->id,
                'amount' => 1000,
                'type' => 'regular',
                'status' => 'show-and-calc',
            ]);

            $user->bonus_amount = 1000;
            $user->save();
        }

        $payload = $users->map(function ($user, $index) {
            return [
                'phone' => $user->phone,
                'debit_amount' => 500,
                'id_sell' => 'BATCH_DEBIT_' . $index . '_' . time(),
                'parent_id_sell' => 'BATCH_PARENT_' . $index . '_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ];
        })->toArray();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/debit', $payload);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы списаны',
            ]);

        foreach ($users as $user) {
            $this->assertDatabaseHas('bonuses', [
                'user_id' => $user->id,
                'amount' => -500,
                'type' => 'regular',
            ]);
        }
    }

    #[Test]
    public function one_c_user_can_refund_bonus_to_multiple_users()
    {
        $users = User::factory()->count(2)->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $payload = [];

        foreach ($users as $index => $user) {
            $parentId = 'REFUND_PARENT_' . $index . '_' . time();
            $refundId = 'REFUND_ID_' . $index . '_' . time();

            Bonus::create([
                'user_id' => $user->id,
                'amount' => 50,
                'purchase_amount' => 1000,
                'type' => 'regular',
                'status' => 'show-and-calc',
                'id_sell' => $parentId,
            ]);

            $user->bonus_amount = 50;
            $user->purchase_amount = 1000;
            $user->save();

            $payload[] = [
                'phone' => $user->phone,
                'refund_amount' => 500,
                'id_sell' => $refundId,
                'parent_id_sell' => $parentId,
                'timestamp' => now()->toDateTimeString(),
            ];
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/refund', $payload);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Бонусы возвращены (возврат товара)',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'success',
                        'data' => [
                            'refunded_bonus_amount',
                            'returned_debit_amount',
                        ]
                    ]
                ]
            ]);

        foreach ($users as $user) {
            $this->assertDatabaseHas('bonuses', [
                'user_id' => $user->id,
                'amount' => -25,
                'type' => 'refund',
            ]);

            $user->refresh();
            $this->assertEquals(500, $user->purchase_amount);
        }
    }

    #[Test]
    public function one_c_user_can_credit_multiple_promotional_bonuses()
    {
        $users = User::factory()->count(2)->create();
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $payload = $users->map(function ($user, $index) {
            return [
                'phone' => $user->phone,
                'bonus_amount' => 100 + $index * 100, // 100, 200
                'expiry_date' => now()->addDays(30)->toDateString(),
                'id_sell' => 'PROMO_BATCH_' . $index . '_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ];
        })->toArray();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/promotion', $payload);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Акционные бонусы начислены',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'success',
                        'data',
                    ]
                ]
            ]);

        foreach ($users as $index => $user) {
            $this->assertDatabaseHas('bonuses', [
                'user_id' => $user->id,
                'amount' => 100 + $index * 100,
                'type' => 'promotional',
            ]);
        }
    }

    #[Test]
    public function promotion_batch_returns_206_when_some_operations_fail()
    {
        $users = User::factory()->count(2)->create();
        $phone1 = $users[0]->phone;
        $phone2 = $users[1]->phone;

        $users[1]->delete(); // вторая операция сломается

        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        // Один валидный, один несуществующий телефон
        $payload = [
            [
                'phone' => $phone1,
                'bonus_amount' => 100,
                'expiry_date' => now()->addDays(30)->toDateString(),
                'id_sell' => 'PROMO_OK_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ],
            [
                'phone' => $phone2, // несуществующий пользователь
                'bonus_amount' => 100,
                'expiry_date' => now()->addDays(30)->toDateString(),
                'id_sell' => 'PROMO_FAIL_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/promotion', $payload);

        $response->assertStatus(206)
            ->assertJson([
                'ok' => true,
                'message' => 'Часть акционных бонусов начислена',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'success',
                    ],
                ]
            ]);

        $this->assertDatabaseHas('bonuses', [
            'user_id' => $users[0]->id,
            'amount' => 100,
            'type' => 'promotional',
        ]);
    }

    #[Test]
    public function promotion_batch_returns_400_when_all_operations_fail()
    {
        $oneCUser = User::factory()->oneC()->create();
        $token = $oneCUser->createToken('test-token')->plainTextToken;

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $phone1 = $user1->phone;
        $phone2 = $user2->phone;

        $user1->delete();
        $user2->delete();

        $payload = [
            [
                'phone' => $phone1, // несуществующий
                'bonus_amount' => 100,
                'expiry_date' => now()->addDays(30)->toDateString(),
                'id_sell' => 'PROMO_FAIL_1_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ],
            [
                'phone' => $phone2, // тоже не существует
                'bonus_amount' => 200,
                'expiry_date' => now()->addDays(30)->toDateString(),
                'id_sell' => 'PROMO_FAIL_2_' . time(),
                'timestamp' => now()->toDateTimeString(),
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/bonus/promotion', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'ok' => false,
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'success',
                        'error',
                    ]
                ]
            ]);
    }
}
