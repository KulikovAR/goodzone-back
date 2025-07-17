<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Bonus;
use Tests\TestCase;
use App\Services\ExpoNotificationService;
use App\Services\BonusService;
use Carbon\Carbon;

class BonusHistoryTest extends TestCase
{
    protected $mockPushService;
    protected $bonusService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPushService = $this->createMock(ExpoNotificationService::class);
        $this->app->instance(ExpoNotificationService::class, $this->mockPushService);
        $this->bonusService = new BonusService($this->mockPushService);
        $this->app->instance(BonusService::class, $this->bonusService);
    }

    public function test_spent_promotional_bonuses_not_shown_in_history()
    {
        // Создаем пользователя
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем активный акционный бонус
        $activePromoBonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'promotional',
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'show-and-calc'
        ]);

        // Создаем списанный акционный бонус (должен НЕ показываться в истории)
        $spentPromoBonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => 300,
            'type' => 'promotional',
            'expires_at' => Carbon::now()->addDays(15),
            'status' => 'show-not-calc' // Списанный
        ]);

        // Создаем обычный бонус (должен показываться)
        $regularBonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'type' => 'regular',
            'status' => 'show-and-calc'
        ]);

        // Создаем списанный обычный бонус (должен показываться)
        $spentRegularBonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => -500,
            'type' => 'regular',
            'status' => 'show-not-calc'
        ]);

        // Получаем историю бонусов
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/bonus/history');

        $response->assertOk();

        $history = $response->json('data.history');
        
        // Проверяем, что в истории есть только нужные записи
        $bonusIds = collect($history)->pluck('id')->toArray();
        
        // Должны быть в истории:
        $this->assertContains($activePromoBonus->id, $bonusIds); // Активный акционный
        $this->assertContains($regularBonus->id, $bonusIds); // Обычный
        $this->assertContains($spentRegularBonus->id, $bonusIds); // Списанный обычный
        
        // НЕ должны быть в истории:
        $this->assertNotContains($spentPromoBonus->id, $bonusIds); // Списанный акционный
    }

    public function test_promotional_history_shows_only_active_bonuses()
    {
        // Создаем пользователя
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Создаем активный акционный бонус
        $activePromoBonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'promotional',
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'show-and-calc'
        ]);

        // Создаем списанный акционный бонус
        $spentPromoBonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => 300,
            'type' => 'promotional',
            'expires_at' => Carbon::now()->addDays(15),
            'status' => 'show-not-calc'
        ]);

        // Получаем историю акционных бонусов
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/bonus/promotional-history');

        $response->assertOk();

        $history = $response->json('data.history');
        
        // Проверяем, что показываются только активные акционные бонусы
        $bonusIds = collect($history)->pluck('id')->toArray();
        
        $this->assertContains($activePromoBonus->id, $bonusIds); // Активный
        $this->assertNotContains($spentPromoBonus->id, $bonusIds); // Списанный
    }

    #[Test]
    public function test_promotional_bonus_display_after_debit_scenario()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // 1. Делаем покупку на 1000₽ (начисляется 50 бонусов при 5%)
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        $user->refresh();
        
        $this->assertEquals(50, $user->bonus_amount);
        
        // 2. Делаем 3 списания по 10 бонусов каждое (с parent_id_sell)
        $this->bonusService->debitBonus($user, 10, 'DEBIT_1', 'RECEIPT_1');
        $this->bonusService->debitBonus($user, 10, 'DEBIT_2', 'RECEIPT_1');
        $this->bonusService->debitBonus($user, 10, 'DEBIT_3', 'RECEIPT_1');
        $user->refresh();
        
        $this->assertEquals(20, $user->bonus_amount); // 50 - 30 = 20
        
        // 3. Начисляем акционные бонусы 100₽
        $this->bonusService->creditPromotionalBonus($user, 100, now()->addDays(30));
        $user->refresh();
        
        $this->assertEquals(120, $user->bonus_amount); // 20 + 100 = 120
        
        // 4. Списываем полную покупку (1000₽ = 50 бонусов)
        $this->bonusService->debitBonus($user, 50, 'DEBIT_4', 'RECEIPT_1');
        $user->refresh();
        
        $this->assertEquals(70, $user->bonus_amount); // 120 - 50 = 70
        
        // 5. Проверяем информацию о бонусах
        $bonusInfo = $this->bonusService->getBonusInfo($user);
        
        $this->assertEquals(70, $bonusInfo['bonus_amount']);
        $this->assertEquals(50, $bonusInfo['promotional_bonus_amount']); // Должно быть 50
        $this->assertEquals(20, $bonusInfo['bonus_amount_without']);
        
        // 6. Проверяем историю бонусов
        $history = $this->bonusService->getBonusHistory($user);
        $promotionalHistory = $this->bonusService->getPromotional($user);
        
        // В истории не должно быть списанных акционных бонусов
        $this->assertCount(1, $promotionalHistory); // Только одна активная запись на 50₽
        $this->assertEquals(50, $promotionalHistory->sum('amount'));
    }

    #[Test]
    public function test_partial_promotional_bonus_debit()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем исходный чек покупки для списания
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        $user->refresh();

        // Начисляем акционные бонусы 100₽
        $this->bonusService->creditPromotionalBonus($user, 100, now()->addDays(30));
        $user->refresh();
        $this->assertEquals(150, $user->bonus_amount); // 50 + 100

        // Списываем только 40₽ (частичное списание)
        $this->bonusService->debitBonus($user, 40, 'DEBIT_PARTIAL_PROMO', 'RECEIPT_1');
        $user->refresh();
        $this->assertEquals(110, $user->bonus_amount); // 150 - 40

        // Проверяем, что активный промо-бонус теперь 60
        $bonusInfo = $this->bonusService->getBonusInfo($user);
        $this->assertEquals(60, $bonusInfo['promotional_bonus_amount']);
        $this->assertEquals(110, $bonusInfo['bonus_amount']);
        $this->assertEquals(50, $bonusInfo['bonus_amount_without']);

        // В истории промо-бонусов только одна запись на 60
        $promotionalHistory = $this->bonusService->getPromotional($user);
        $this->assertCount(1, $promotionalHistory);
        $this->assertEquals(60, $promotionalHistory->sum('amount'));
    }

    #[Test]
    public function test_refund_after_promotional_bonus_debit()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // 1. Покупка на 1000₽ (50 обычных бонусов)
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        $user->refresh();
        $this->assertEquals(50, $user->bonus_amount);

        // 2. Начисляем промо 100₽
        $this->bonusService->creditPromotionalBonus($user, 100, now()->addDays(30));
        $user->refresh();
        $this->assertEquals(150, $user->bonus_amount);

        // 3. Списываем 120₽ (спишется 100 промо + 20 обычных)
        $this->bonusService->debitBonus($user, 120, 'DEBIT_1', 'RECEIPT_1');
        $user->refresh();
        $this->assertEquals(30, $user->bonus_amount); // 50 - 20 = 30 обычных осталось

        // 4. Делаем возврат по чеку на 500₽ (должно вернуть пропорцию от списанных обычных бонусов)
        $result = $this->bonusService->refundBonusByReceipt($user, 'REFUND_1', 'RECEIPT_1', 500);
        $user->refresh();

        // Проверяем правильную логику возврата
        $bonusInfo = $this->bonusService->getBonusInfo($user);
        
        // Правильная логика возврата:
        // 1. Начислено по чеку: 50₽ обычных бонусов
        // 2. Возврат 500₽ из 1000₽ = 50%
        // 3. Списывается: 50 * 0.5 = 25₽ обычных бонусов
        // 4. Возвращается списанных: 20 * 0.5 = 10₽ (из технической записи -20₽)
        // 5. Итого: 30 (остаток) - 25 + 10 = 15₽
        $this->assertEquals(15, $bonusInfo['bonus_amount']); 
        $this->assertEquals(0, $bonusInfo['promotional_bonus_amount']); // Промо все списались
        $this->assertEquals(15, $bonusInfo['bonus_amount_without']); // Только обычные бонусы
    }

    #[Test]
    public function test_purchase_amount_decreases_on_refund()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // 1. Покупка на 4000₽
        $this->bonusService->creditBonus($user, 4000, 'RECEIPT_1');
        $user->refresh();
        $this->assertEquals(4000, $user->purchase_amount);
        $this->assertEquals(200, $user->bonus_amount); // 5% от 4000

        // 2. Начисляем 200 акционных бонусов
        $this->bonusService->creditPromotionalBonus($user, 200, now()->addDays(30));
        $user->refresh();
        $this->assertEquals(400, $user->bonus_amount); // 200 + 200

        // 3. Начисляем еще 50 акционных бонусов
        $this->bonusService->creditPromotionalBonus($user, 50, now()->addDays(30));
        $user->refresh();
        $this->assertEquals(450, $user->bonus_amount); // 200 + 200 + 50

        // 4. Списываем 200 бонусов по тому же чеку
        $this->bonusService->debitBonus($user, 200, 'DEBIT_1', 'RECEIPT_1');
        $user->refresh();
        $this->assertEquals(250, $user->bonus_amount); // 450 - 200
        $this->assertEquals(4000, $user->purchase_amount); // Сумма покупок не изменилась

        // 5. Возвращаем 2000₽ за этот чек
        $result = $this->bonusService->refundBonusByReceipt($user, 'REFUND_1', 'RECEIPT_1', 2000);
        $user->refresh();

        // Проверяем, что сумма покупок уменьшилась
        $this->assertEquals(2000, $user->purchase_amount); // 4000 - 2000 = 2000
        
        // Проверяем информацию о бонусах
        $bonusInfo = $this->bonusService->getBonusInfo($user);
        $this->assertEquals(2000, $bonusInfo['total_purchase_amount']); // Должно быть 2000
    }

    #[Test]
    public function test_refund_finds_original_receipt()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // 1. Покупка на 4000₽ с id_sell = "R1"
        $this->bonusService->creditBonus($user, 4000, 'R1');
        $user->refresh();
        $this->assertEquals(4000, $user->purchase_amount);

        // 2. Проверяем, что запись создалась с правильным id_sell
        $purchaseBonus = Bonus::where('user_id', $user->id)
            ->where('id_sell', 'R1')
            ->where('type', 'regular')
            ->where('amount', '>', 0)
            ->first();
        
        $this->assertNotNull($purchaseBonus, 'Запись покупки с id_sell = "R1" должна существовать');
        $this->assertEquals(200, $purchaseBonus->amount); // 5% от 4000

        // 3. Пытаемся сделать возврат с тем же parent_id_sell = "R1"
        $result = $this->bonusService->refundBonusByReceipt($user, 'REFUND_1', 'R1', 2000);
        $user->refresh();

        // 4. Проверяем, что возврат прошел успешно
        $this->assertEquals(2000, $user->purchase_amount); // 4000 - 2000 = 2000
        
        // 5. Проверяем, что создалась запись возврата
        $refundBonus = Bonus::where('user_id', $user->id)
            ->where('id_sell', 'REFUND_1')
            ->where('type', 'refund')
            ->first();
        
        $this->assertNotNull($refundBonus, 'Запись возврата должна быть создана');
        $this->assertEquals(-100, $refundBonus->amount); // 5% от 2000 = 100, но отрицательная
    }
} 