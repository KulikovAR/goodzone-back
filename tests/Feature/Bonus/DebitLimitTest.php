<?php

namespace Tests\Feature\Bonus;

use Tests\TestCase;
use App\Models\User;
use App\Services\BonusService;
use App\Services\ExpoNotificationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class DebitLimitTest extends TestCase
{
    use DatabaseMigrations;

    private BonusService $bonusService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mockPushService = Mockery::mock(ExpoNotificationService::class);
        $mockPushService->shouldReceive('send')->andReturn(true);
        
        $this->bonusService = new BonusService($mockPushService);
    }

    #[Test]
    public function test_debit_within_limit_succeeds()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем покупку на 1000₽ (50 бонусов при 5%)
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        
        // Начисляем дополнительные бонусы для списания
        $this->bonusService->creditPromotionalBonus($user, 500, now()->addDays(30));
        $user->refresh();

        // Пытаемся списать 200 бонусов (20% от чека) - должно пройти
        $this->bonusService->debitBonus($user, 200, 'DEBIT_1', 'RECEIPT_1');
        $user->refresh();

        // Проверяем, что списание прошло успешно
        $this->assertEquals(350, $user->bonus_amount); // 50 + 500 - 200 = 350
    }

    #[Test]
    public function test_debit_exceeds_limit_throws_exception()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем покупку на 1000₽ (50 бонусов при 5%)
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        
        // Начисляем дополнительные бонусы для списания
        $this->bonusService->creditPromotionalBonus($user, 500, now()->addDays(30));
        $user->refresh();

        // Пытаемся списать 400 бонусов (40% от чека) - должно выбросить исключение
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Сумма списания превышает максимально допустимую (30% от стоимости чека). Максимум: 300 бонусов');
        
        $this->bonusService->debitBonus($user, 400, 'DEBIT_1', 'RECEIPT_1');
    }

    #[Test]
    public function test_debit_exactly_at_limit_succeeds()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем покупку на 1000₽ (50 бонусов при 5%)
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        
        // Начисляем дополнительные бонусы для списания
        $this->bonusService->creditPromotionalBonus($user, 500, now()->addDays(30));
        $user->refresh();

        // Пытаемся списать 300 бонусов (ровно 30% от чека) - должно пройти
        $this->bonusService->debitBonus($user, 300, 'DEBIT_1', 'RECEIPT_1');
        $user->refresh();

        // Проверяем, что списание прошло успешно
        $this->assertEquals(250, $user->bonus_amount); // 50 + 500 - 300 = 250
    }

    #[Test]
    public function test_debit_without_parent_id_sell_throws_exception()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем покупку на 1000₽
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        
        // Начисляем дополнительные бонусы для списания
        $this->bonusService->creditPromotionalBonus($user, 500, now()->addDays(30));
        $user->refresh();

        // Пытаемся списать БЕЗ указания parent_id_sell - должно выбросить исключение
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Необходимо указать parent_id_sell для списания бонусов');
        
        $this->bonusService->debitBonus($user, 200, 'DEBIT_1');
    }

    #[Test]
    public function test_debit_with_nonexistent_parent_id_sell_throws_exception()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем покупку на 1000₽
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1');
        
        // Начисляем дополнительные бонусы для списания
        $this->bonusService->creditPromotionalBonus($user, 500, now()->addDays(30));
        $user->refresh();

        // Пытаемся списать с несуществующим parent_id_sell - должно выбросить исключение
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Исходный чек покупки с ID NONEXISTENT_RECEIPT не найден');
        
        $this->bonusService->debitBonus($user, 200, 'DEBIT_1', 'NONEXISTENT_RECEIPT');
    }

    #[Test]
    public function test_debit_limit_calculation_is_correct()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем покупку на 5000₽ (250 бонусов при 5%)
        $this->bonusService->creditBonus($user, 5000, 'RECEIPT_1');
        
        // Начисляем дополнительные бонусы для списания
        $this->bonusService->creditPromotionalBonus($user, 2000, now()->addDays(30));
        $user->refresh();

        // Максимум можно списать: 5000 * 0.3 = 1500 бонусов
        // Пытаемся списать 1600 бонусов - должно выбросить исключение
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Сумма списания превышает максимально допустимую (30% от стоимости чека). Максимум: 1500 бонусов');
        
        $this->bonusService->debitBonus($user, 1600, 'DEBIT_1', 'RECEIPT_1');
    }

    #[Test]
    public function test_multiple_debits_respect_individual_limits()
    {
        $user = User::factory()->create([
            'bonus_amount' => 0,
            'purchase_amount' => 0
        ]);

        // Создаем две покупки
        $this->bonusService->creditBonus($user, 1000, 'RECEIPT_1'); // Лимит: 300 бонусов
        $this->bonusService->creditBonus($user, 2000, 'RECEIPT_2'); // Лимит: 600 бонусов
        
        // Начисляем дополнительные бонусы для списания
        $this->bonusService->creditPromotionalBonus($user, 1000, now()->addDays(30));
        $user->refresh();

        // Списываем по лимиту с каждого чека
        $this->bonusService->debitBonus($user, 300, 'DEBIT_1', 'RECEIPT_1');
        $this->bonusService->debitBonus($user, 600, 'DEBIT_2', 'RECEIPT_2');
        $user->refresh();

        // Проверяем, что оба списания прошли успешно
        // 50 + 100 + 1000 - 300 - 600 = 250
        $this->assertEquals(250, $user->bonus_amount);
    }
} 