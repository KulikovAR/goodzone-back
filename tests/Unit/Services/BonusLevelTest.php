<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Services\BonusService;
use App\Services\ExpoNotificationService;
use App\Enums\BonusLevel;
use Mockery;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BonusLevelTest extends TestCase
{
    use DatabaseMigrations;

    private ExpoNotificationService $mockPushService;
    private BonusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPushService = Mockery::mock(ExpoNotificationService::class);
        $this->service = new BonusService($this->mockPushService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_debug_levels()
    {
        // Проверяем значения уровней напрямую
        $bronze = BonusLevel::BRONZE;
        $silver = BonusLevel::SILVER;
        $gold = BonusLevel::GOLD;
        
        $this->assertEquals(5, $bronze->getCashbackPercent());
        $this->assertEquals(10, $silver->getCashbackPercent());
        $this->assertEquals(15, $gold->getCashbackPercent());
        
        // Проверяем пользователя с золотым уровнем
        $user = User::factory()->create(['purchase_amount' => 50000]);
        $level = $this->service->getUserLevel($user);
        
        $this->assertEquals(BonusLevel::GOLD, $level);
        $this->assertEquals(15, $level->getCashbackPercent());
        
        // Проверяем расчет бонуса
        $bonusAmount = $this->service->calculateBonusAmount($user, 1000);
        $this->assertEquals(150, $bonusAmount); // 15% от 1000
    }

    public function test_new_user_has_bronze_level()
    {
        $user = User::factory()->create([
            'purchase_amount' => 0,
        ]);

        $level = $this->service->getUserLevel($user);
        
        $this->assertEquals(BonusLevel::BRONZE, $level);
        $this->assertEquals(5, $level->getCashbackPercent());
        $this->assertEquals(0, $level->getMinPurchaseAmount());
    }

    public function test_user_with_5000_purchases_has_bronze_level()
    {
        $user = User::factory()->create([
            'purchase_amount' => 5000,
        ]);

        $level = $this->service->getUserLevel($user);
        
        $this->assertEquals(BonusLevel::BRONZE, $level);
        $this->assertEquals(5, $level->getCashbackPercent());
    }

    public function test_user_with_10000_purchases_has_silver_level()
    {
        $user = User::factory()->create([
            'purchase_amount' => 10000,
        ]);

        $level = $this->service->getUserLevel($user);
        
        $this->assertEquals(BonusLevel::SILVER, $level);
        $this->assertEquals(10, $level->getCashbackPercent());
        $this->assertEquals(10000, $level->getMinPurchaseAmount());
    }

    public function test_user_with_20000_purchases_has_silver_level()
    {
        $user = User::factory()->create([
            'purchase_amount' => 20000,
        ]);

        $level = $this->service->getUserLevel($user);
        
        $this->assertEquals(BonusLevel::SILVER, $level);
        $this->assertEquals(10, $level->getCashbackPercent());
    }

    public function test_user_with_30000_purchases_has_gold_level()
    {
        $this->mockPushService->shouldReceive('send')->byDefault();
        
        $user = User::factory()->create(['purchase_amount' => 0]);

        // Создаём покупки для достижения золотого уровня
        $this->service->creditBonus($user, 30000, 'TEST_RECEIPT_GOLD');
        $user->refresh();

        $level = $this->service->getUserLevel($user);
        
        $this->assertEquals(BonusLevel::GOLD, $level);
        $this->assertEquals(15, $level->getCashbackPercent());
        $this->assertEquals(30000, $level->getMinPurchaseAmount());
    }

    public function test_user_with_50000_purchases_has_gold_level()
    {
        $this->mockPushService->shouldReceive('send')->byDefault();
        
        $user = User::factory()->create(['purchase_amount' => 0]);

        // Создаём покупки для достижения золотого уровня
        $this->service->creditBonus($user, 50000, 'TEST_RECEIPT_GOLD_50K');
        $user->refresh();

        $level = $this->service->getUserLevel($user);
        
        $this->assertEquals(BonusLevel::GOLD, $level);
        $this->assertEquals(15, $level->getCashbackPercent());
    }

    public function test_bonus_calculation_respects_user_level()
    {
        $this->mockPushService->shouldReceive('send')->byDefault();

        // Бронзовый уровень - 5%
        $bronzeUser = User::factory()->create(['purchase_amount' => 0]);
        $this->service->creditBonus($bronzeUser, 5000, 'TEST_RECEIPT_1'); // Создаём бронзовый уровень
        $bronzeBonus = $this->service->creditBonus($bronzeUser, 1000, 'TEST_RECEIPT_1_BONUS');
        $this->assertEquals(50, $bronzeBonus->amount); // 5% от 1000

        // Серебряный уровень - 10%
        $silverUser = User::factory()->create(['purchase_amount' => 0]);
        $this->service->creditBonus($silverUser, 15000, 'TEST_RECEIPT_2'); // Создаём серебряный уровень
        $silverBonus = $this->service->creditBonus($silverUser, 1000, 'TEST_RECEIPT_2_BONUS');
        $this->assertEquals(100, $silverBonus->amount); // 10% от 1000

        // Золотой уровень - 15%
        $goldUser = User::factory()->create(['purchase_amount' => 0]);
        $this->service->creditBonus($goldUser, 35000, 'TEST_RECEIPT_3'); // Создаём золотой уровень
        $goldBonus = $this->service->creditBonus($goldUser, 1000, 'TEST_RECEIPT_3_BONUS');
        $this->assertEquals(150, $goldBonus->amount); // 15% от 1000
    }

    public function test_level_progression_after_purchase()
    {
        $this->mockPushService->shouldReceive('send')->byDefault();

        $user = User::factory()->create(['purchase_amount' => 9500]); // Почти серебро

        // Проверяем начальный уровень
        $initialLevel = $this->service->getUserLevel($user);
        $this->assertEquals(BonusLevel::BRONZE, $initialLevel);

        // Делаем покупку, которая поднимает до серебра
        $bonus = $this->service->creditBonus($user, 1000, 'TEST_RECEIPT_4');
        $user->refresh();

        // Проверяем новый уровень
        $newLevel = $this->service->getUserLevel($user);
        $this->assertEquals(BonusLevel::SILVER, $newLevel);
        $this->assertEquals(10, $newLevel->getCashbackPercent());
    }

    public function test_level_downgrade_after_refund()
    {
        $this->mockPushService->shouldReceive('send')->byDefault();

        $user = User::factory()->create(['purchase_amount' => 5000]); // Бронза

        // Проверяем начальный уровень
        $initialLevel = $this->service->getUserLevel($user);
        $this->assertEquals(BonusLevel::BRONZE, $initialLevel);

        // Создаем покупку, которая поднимает до серебра
        $this->service->creditBonus($user, 10000, 'TEST_RECEIPT_1'); // чек на 10 000
        $user->refresh();

        // Проверяем, что поднялись до серебра
        $silverLevel = $this->service->getUserLevel($user);
        $this->assertEquals(BonusLevel::SILVER, $silverLevel);

        // Делаем полный возврат покупки, что опускает обратно до бронза
        // 5000 + 10000 - 10000 = 5000 (бронза)
        $this->service->refundBonusByReceipt($user, 'REFUND_1', 'TEST_RECEIPT_1', 10000);
        $user->refresh();

        // Проверяем новый уровень
        $newLevel = $this->service->getUserLevel($user);
        $this->assertEquals(BonusLevel::BRONZE, $newLevel);
        $this->assertEquals(5, $newLevel->getCashbackPercent());
    }

    public function test_bonus_info_includes_level_information()
    {
        $user = User::factory()->create(['purchase_amount' => 15000]);

        $bonusInfo = $this->service->getBonusInfo($user);

        $this->assertEquals('silver', $bonusInfo['level']);
        $this->assertEquals(10, $bonusInfo['cashback_percent']);
        $this->assertEquals('gold', $bonusInfo['next_level']);
        $this->assertEquals(30000, $bonusInfo['next_level_min_amount']);
        $this->assertGreaterThan(0, $bonusInfo['progress_to_next_level']);
        $this->assertLessThanOrEqual(100, $bonusInfo['progress_to_next_level']);
    }

    public function test_gold_level_has_no_next_level()
    {
        $this->mockPushService->shouldReceive('send')->byDefault();
        
        $user = User::factory()->create(['purchase_amount' => 0]);

        // Создаём покупки для достижения золотого уровня
        $this->service->creditBonus($user, 50000, 'TEST_RECEIPT_GOLD_MAX');
        $user->refresh();

        $bonusInfo = $this->service->getBonusInfo($user);

        $this->assertEquals('gold', $bonusInfo['level']);
        $this->assertEquals(15, $bonusInfo['cashback_percent']);
        $this->assertNull($bonusInfo['next_level']);
        $this->assertNull($bonusInfo['next_level_min_amount']);
        $this->assertEquals(100, $bonusInfo['progress_to_next_level']);
    }

    public function test_level_progress_calculation()
    {
        $user = User::factory()->create(['purchase_amount' => 20000]); // Серебро, середина пути к золоту

        $bonusInfo = $this->service->getBonusInfo($user);

        $this->assertEquals('silver', $bonusInfo['level']);
        $this->assertEquals('gold', $bonusInfo['next_level']);
        $this->assertEquals(30000, $bonusInfo['next_level_min_amount']);
        
        // Прогресс: (20000 - 10000) / (30000 - 10000) * 100 = 50%
        $expectedProgress = (20000 - 10000) / (30000 - 10000) * 100;
        $this->assertEqualsWithDelta($expectedProgress, $bonusInfo['progress_to_next_level'], 0.1);
    }

    public function test_level_at_specific_date()
    {
        $user = User::factory()->create(['purchase_amount' => 0]);

        // Создаем покупки в разное время
        $this->mockPushService->shouldReceive('send')->byDefault();
        
        $this->service->creditBonus($user, 5000, 'RECEIPT_1'); // 5000
        $this->service->creditBonus($user, 8000, 'RECEIPT_2'); // 13000
        
        // Проверяем уровень на конкретную дату (до второй покупки)
        $levelAtDate = $this->service->getUserLevelAtDate($user, now()->subDay());
        $this->assertEquals(BonusLevel::BRONZE, $levelAtDate);
        
        // Проверяем текущий уровень
        $currentLevel = $this->service->getUserLevel($user);
        $this->assertEquals(BonusLevel::SILVER, $currentLevel);
    }
} 