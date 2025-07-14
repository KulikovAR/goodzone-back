<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use App\Enums\BonusLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestBonusLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:bonus-levels {phone?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестировать уровни бонусов (бронза/серебро/золото)';

    /**
     * Execute the console command.
     */
    public function handle(BonusService $bonusService)
    {
        $phone = $this->argument('phone');
        
        if ($phone) {
            // Тестируем конкретного пользователя
            $this->testSpecificUser($bonusService, $phone);
        } else {
            // Тестируем с созданием тестовых пользователей
            $this->testWithNewUsers($bonusService);
        }
        
        return 0;
    }
    
    private function testSpecificUser(BonusService $bonusService, string $phone): void
    {
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            $this->error("Пользователь с номером {$phone} не найден");
            return;
        }

        $this->info("=== ТЕСТ УРОВНЕЙ БОНУСОВ ДЛЯ ПОЛЬЗОВАТЕЛЯ ===");
        $this->info("Пользователь: {$user->phone}");
        $this->info("Сумма покупок: " . (string)$user->purchase_amount . "₽");
        $this->line('');

        $userLevel = $bonusService->getUserLevel($user);
        $bonusInfo = $bonusService->getBonusInfo($user);

        $this->info("📊 ТЕКУЩИЙ УРОВЕНЬ:");
        $this->info("   Уровень: {$userLevel->value}");
        $this->info("   Кэшбэк: {$userLevel->getCashbackPercent()}%");
        $this->info("   Минимальная сумма: {$userLevel->getMinPurchaseAmount()}₽");
        $this->line('');

        $this->info("📈 ПРОГРЕСС:");
        if ($userLevel->getNextLevel()) {
            $this->info("   Следующий уровень: {$userLevel->getNextLevel()->value}");
            $this->info("   Сумма для следующего уровня: {$userLevel->getNextLevelMinAmount()}₽");
            $this->info("   Прогресс: {$userLevel->getProgressToNextLevel($user->purchase_amount)}%");
            
            $remaining = $userLevel->getNextLevelMinAmount() - $user->purchase_amount;
            $this->info("   Осталось до следующего уровня: {$remaining}₽");
        } else {
            $this->info("   🏆 Максимальный уровень достигнут!");
        }
        $this->line('');

        $this->info("💰 ИНФОРМАЦИЯ О БОНУСАХ:");
        $this->line(json_encode($bonusInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function testWithNewUsers(BonusService $bonusService): void
    {
        $this->info("=== ТЕСТ УРОВНЕЙ БОНУСОВ С ТЕСТОВЫМИ ПОЛЬЗОВАТЕЛЯМИ ===");
        $this->line('');

        // Создаем in-memory базу для тестирования
        $this->setupTestDatabase();
        
        // Тест 1: Бронзовый уровень
        $this->info("🥉 ТЕСТ 1: Бронзовый уровень (0-9999₽)");
        $bronzeUser = $this->createTestUser('bronze');
        $bronzeLevel = $bonusService->getUserLevel($bronzeUser);
        $this->info("   Уровень: {$bronzeLevel->value} ({$bronzeLevel->getCashbackPercent()}%)");
        $this->info("   Следующий уровень: " . ($bronzeLevel->getNextLevel() ? $bronzeLevel->getNextLevel()->value : 'нет'));
        $this->line('');

        // Тест 2: Серебряный уровень
        $this->info("🥈 ТЕСТ 2: Серебряный уровень (10000-29999₽)");
        $silverUser = $this->createTestUser('silver');
        $silverLevel = $bonusService->getUserLevel($silverUser);
        $this->info("   Уровень: {$silverLevel->value} ({$silverLevel->getCashbackPercent()}%)");
        $this->info("   Следующий уровень: " . ($silverLevel->getNextLevel() ? $silverLevel->getNextLevel()->value : 'нет'));
        $this->line('');

        // Тест 3: Золотой уровень
        $this->info("🥇 ТЕСТ 3: Золотой уровень (30000+₽)");
        $goldUser = $this->createTestUser('gold');
        $goldLevel = $bonusService->getUserLevel($goldUser);
        $this->info("   Уровень: {$goldLevel->value} ({$goldLevel->getCashbackPercent()}%)");
        $this->info("   Следующий уровень: " . ($goldLevel->getNextLevel() ? $goldLevel->getNextLevel()->value : 'нет'));
        $this->line('');

        // Тест 4: Изменение уровня после покупки
        $this->info("🔄 ТЕСТ 4: Изменение уровня после покупки");
        $testUser = $this->createTestUser('bronze');
        $this->info("   Начальный уровень: " . $bonusService->getUserLevel($testUser)->value);
        
        $bonusService->creditBonus($testUser, 12000, 'LEVEL_TEST_' . time());
        $testUser->refresh();
        $this->info("   После покупки 12000₽: " . $bonusService->getUserLevel($testUser)->value);
        
        $bonusService->creditBonus($testUser, 20000, 'LEVEL_TEST_2_' . time());
        $testUser->refresh();
        $this->info("   После покупки еще 20000₽: " . $bonusService->getUserLevel($testUser)->value);
        $this->line('');

        // Тест 5: Понижение уровня после возврата
        $this->info("📉 ТЕСТ 5: Понижение уровня после возврата");
        $this->info("   Текущий уровень: " . $bonusService->getUserLevel($testUser)->value);
        
        $bonusService->refundBonusByReceipt($testUser, 'REFUND_1_' . time(), 'LEVEL_TEST_2_' . time(), 15000);
        $testUser->refresh();
        $this->info("   После возврата 15000₽: " . $bonusService->getUserLevel($testUser)->value);
        
        $bonusService->refundBonusByReceipt($testUser, 'REFUND_2_' . time(), 'LEVEL_TEST_' . time(), 12000);
        $testUser->refresh();
        $this->info("   После возврата еще 12000₽: " . $bonusService->getUserLevel($testUser)->value);
        $this->line('');

        // Очистка
        $bronzeUser->delete();
        $silverUser->delete();
        $goldUser->delete();
        $testUser->bonuses()->delete();
        $testUser->delete();
        
        $this->info("✅ Тест уровней бонусов завершен!");
    }
    
    private function setupTestDatabase(): void
    {
        config([
            'database.default' => 'sqlite_testing',
            'database.connections.sqlite_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]
        ]);
        
        DB::statement('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255),
            phone VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE,
            gender VARCHAR(255),
            city VARCHAR(255),
            birthday DATE,
            children VARCHAR(255),
            marital_status VARCHAR(255),
            purchase_amount DECIMAL(15,2) DEFAULT 0,
            bonus_amount INTEGER DEFAULT 0,
            role VARCHAR(255) DEFAULT "user",
            profile_completed_bonus BOOLEAN DEFAULT 0,
            deleted_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME
        )');
        
        DB::statement('CREATE TABLE bonuses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            purchase_amount DECIMAL(10,2),
            type VARCHAR(255) NOT NULL,
            status VARCHAR(255) NOT NULL,
            expires_at DATETIME,
            id_sell VARCHAR(255),
            parent_id_sell VARCHAR(255),
            deleted_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )');
    }
    
    private function createTestUser(string $level): User
    {
        $purchaseAmount = match($level) {
            'bronze' => 5000,
            'silver' => 15000,
            'gold' => 35000,
            default => 0
        };
        
        $timestamp = time() . rand(1000, 9999);
        
        return User::create([
            'phone' => '+7999' . $timestamp,
            'name' => "Test User {$level}",
            'email' => "test{$level}{$timestamp}@example.com",
            'gender' => 'male',
            'city' => 'Moscow',
            'birthday' => '1990-01-01',
            'children' => 'none',
            'marital_status' => 'single',
            'purchase_amount' => $purchaseAmount,
            'bonus_amount' => 0,
            'role' => 'user',
            'profile_completed_bonus' => true
        ]);
    }
} 