<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use Illuminate\Console\Command;
use App\Models\Bonus;
use Illuminate\Support\Facades\DB;

class TestAllNewLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:all-new-logic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестировать всю новую логику после изменений';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=== ТЕСТ ВСЕЙ НОВОЙ ЛОГИКИ ===");
        $this->line('');
        
        // Переключаемся на in-memory SQLite для тестирования
        config([
            'database.default' => 'sqlite_testing',
            'database.connections.sqlite_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]
        ]);
        
        // Убеждаемся что база SQLite существует и имеет нужные таблицы
        $this->info("Подготавливаем in-memory SQLite базу...");
        
        // Создаем только нужные таблицы вручную
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
        
        DB::statement('CREATE TABLE user_device_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_token VARCHAR(255) NOT NULL,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )');
        
        $this->info("Таблицы созданы");
        $this->line('');
        
        $bonusService = app(BonusService::class);
        
        // Создаем тестового пользователя
        $user = User::create([
            'phone' => '+7999' . time(),
            'name' => 'Test User',
            'email' => 'test' . time() . '@example.com',
            'gender' => 'male',
            'city' => 'Moscow',
            'birthday' => '1990-01-01',
            'purchase_amount' => 0,
            'bonus_amount' => 0,
            'role' => 'user'
        ]);
        
        $this->info("Создан тестовый пользователь: {$user->phone}");
        $this->line('');
        
        // Тест 1: Проверка профиля без детей и семейного положения
        $this->info("=== ТЕСТ 1: Проверка isProfileCompleted (без children/marital_status) ===");
        $isComplete = $user->isProfileCompleted();
        $this->info("Профиль заполнен: " . ($isComplete ? 'Да' : 'Нет') . " (ожидаем: НЕТ, нужны children/marital_status)");
        $this->line('');
        
        // Тест 2: API начисления с id_sell
        $this->info("=== ТЕСТ 2: Начисление с id_sell ===");
        $bonus1 = $bonusService->creditBonus($user, 5000, 'RECEIPT_TEST_' . time());
        $user->refresh();
        $this->info("Начислено бонусов: " . (string)$bonus1->amount);
        $this->info("ID чека: {$bonus1->id_sell}");
        $this->info("Баланс: " . (float)$user->bonus_amount);
        $this->line('');
        
        // Тест 3: API списания с id_sell и parent_id_sell
        $this->info("=== ТЕСТ 3: Списание с id_sell и parent_id_sell ===");
        try {
            $bonusService->debitBonus($user, 100, 'DEBIT_TEST_' . time(), 'PARENT_' . time());
            $user->refresh();
            $this->info("✅ Списание прошло успешно");
            $this->info("Новый баланс: " . (float)$user->bonus_amount);
        } catch (\Exception $e) {
            $this->error("❌ Ошибка списания: " . $e->getMessage());
        }
        $this->line('');
        
        // Тест 4: Возврат по новой логике
        $this->info("=== ТЕСТ 4: Тест новой логики возврата ===");
        $this->info("До возврата - Сумма покупок: " . (string)$user->purchase_amount . ", Уровень: {$bonusService->getUserLevel($user)->value}");
        
        try {
            $refundResult = $bonusService->refundBonusByReceipt($user, 'REFUND_TEST_' . time(), $bonus1->id_sell, 2000);
            $user->refresh();
            $this->info("✅ Возврат прошел успешно");
            $this->info("После возврата - Сумма покупок: " . (string)$user->purchase_amount . ", Уровень: {$bonusService->getUserLevel($user)->value}");
            $this->info("Возвращено бонусов: " . abs($refundResult['refund_bonus']->amount));
            $this->info("Возвращено списанных: " . $refundResult['returned_debit_amount']);
            $this->info("Итоговый баланс: " . (float)$user->bonus_amount);
        } catch (\Exception $e) {
            $this->error("❌ Ошибка возврата: " . $e->getMessage());
        }
        $this->line('');
        
        // Тест 5: Информация о бонусах
        $this->info("=== ТЕСТ 5: BonusInfo API ===");
        $bonusInfo = $bonusService->getBonusInfo($user);
        $this->line(json_encode($bonusInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line('');
        
        // Проверяем записи в базе
        $this->info("=== ЗАПИСИ БОНУСОВ В БД ===");
        $bonuses = $user->bonuses()->orderBy('created_at')->get();
        foreach ($bonuses as $bonus) {
            $this->line("ID: {$bonus->id}, Amount: " . (string)$bonus->amount . ", Type: {$bonus->type}, ID_Sell: {$bonus->id_sell}, Parent: {$bonus->parent_id_sell}");
        }
        
        // Тест 7: BonusInfo API
        $this->info("=== ТЕСТ 7: BonusInfo API ===");
        $bonusInfo = $bonusService->getBonusInfo($user);
        $this->info("Баланс: " . (string)$bonusInfo['bonus_amount']);
        $this->info("Уровень: {$bonusInfo['level']}");
        $this->info("Кэшбэк: {$bonusInfo['cashback_percent']}%");
        $this->info("Сумма покупок: " . (string)$bonusInfo['total_purchase_amount']);
        $this->line('');
        
        // Тест 8: Проверка регистрации 1С без автобонуса
        $this->info("=== ТЕСТ 8: Регистрация 1С без автобонуса ===");
        $timestamp2 = time() + 1; // уникальный timestamp
        $newUser = User::create([
            'phone' => '+7998' . $timestamp2,
            'name' => 'Test User 1C',
            'email' => 'test1c' . $timestamp2 . '@example.com',
            'gender' => 'male',
            'city' => 'Moscow',
            'birthday' => '1990-01-01',
            'purchase_amount' => 0,
            'bonus_amount' => 0,
            'role' => 'user'
        ]);
        
        $this->info("Создан пользователь из 1С. Заполнен профиль? " . ($newUser->isProfileCompleted() ? 'Да' : 'Нет'));
        $this->info("Получил автобонус? " . ($newUser->profile_completed_bonus ? 'Да' : 'Нет'));
        $this->line('');

        // *** НОВЫЙ ТЕСТ 9: Возврат списанных бонусов ***
        $this->info("=== ТЕСТ 9: Возврат списанных бонусов ===");
        
        // Создаем нового пользователя для чистого теста
        $timestamp3 = time() + 2; // уникальный timestamp
        $testUser = User::create([
            'phone' => '+7888' . $timestamp3,
            'name' => 'Test User Debit',
            'email' => 'testdebit' . $timestamp3 . '@example.com',
            'gender' => 'male',
            'city' => 'Moscow',
            'birthday' => '1990-01-01',
            'children' => 'none',
            'marital_status' => 'single',
            'purchase_amount' => 0,
            'bonus_amount' => 0,
            'role' => 'user',
            'profile_completed_bonus' => true // для полного профиля
        ]);
        
        // Шаг 1: Начисляем бонусы за покупку
        $receiptId = 'RECEIPT_DEBIT_TEST_' . time();
        $creditBonus = $bonusService->creditBonus($testUser, 2000, $receiptId);
        $testUser->refresh();
        $this->info("1. Начислено за покупку 2000₽: " . (string)$creditBonus->amount . " бонусов");
        $this->info("   Баланс: " . (string)$testUser->bonus_amount);
        
        // Шаг 2: Списываем часть бонусов при оплате
        $debitReceiptId = 'DEBIT_FOR_PURCHASE_' . time();
        $bonusService->debitBonus($testUser, 50, $debitReceiptId, $receiptId);
        $testUser->refresh();
        $this->info("2. Списано 50 бонусов при оплате чека {$receiptId}");
        $this->info("   Баланс после списания: " . (string)$testUser->bonus_amount);
        
        // Шаг 3: Делаем частичный возврат товара (50% от суммы)
        $refundReceiptId = 'REFUND_DEBIT_TEST_' . time();
        $refundAmount = 1000; // 50% от 2000
        
        $this->info("3. Делаем возврат товара на сумму {$refundAmount}₽ (50% от чека)");
        $balanceBeforeRefund = $testUser->bonus_amount;
        
        $refundResult = $bonusService->refundBonusByReceipt($testUser, $refundReceiptId, $receiptId, $refundAmount);
        $testUser->refresh();
        
        $balanceAfterRefund = $testUser->bonus_amount;
        $balanceChange = $balanceAfterRefund - $balanceBeforeRefund;
        
        $this->info("   Баланс до возврата: " . (string)$balanceBeforeRefund);
        $this->info("   Баланс после возврата: " . (string)$balanceAfterRefund);
        $this->info("   Изменение баланса: " . ($balanceChange >= 0 ? '+' : '') . (string)$balanceChange);
        
        // Проверяем логику:
        // - Должно уменьшиться начисление: 100 -> 50 (50% от возврата)
        // - Должно вернуться 50% от списанных: 25 бонусов (50% от 50)
        // - Итого изменение баланса: -50 + 25 = -25
        
        // Проверяем записи в БД
        $debitRefundRecord = Bonus::where('user_id', $testUser->id)
            ->where('id_sell', $refundReceiptId . '_DEBIT_REFUND')
            ->first();
            
        if ($debitRefundRecord) {
            $this->info("✅ Создана запись возврата списанных бонусов: +" . (string)$debitRefundRecord->amount);
        } else {
            $this->error("❌ Запись возврата списанных бонусов НЕ создана");
        }
        
        $expectedReturnedDebit = round(50 * ($refundAmount / 2000), 2); // 50% от 50 списанных
        $userLevel = $bonusService->getUserLevel($testUser);
        $cashbackPercent = $userLevel->getCashbackPercent();
        $expectedBalanceChange = -round(1000 * ($cashbackPercent / 100), 2) + $expectedReturnedDebit; // -50 + 25 = -25
        
        $this->info("   Ожидаемый возврат списанных: " . (string)$expectedReturnedDebit);
        $this->info("   Ожидаемое изменение баланса: " . (string)$expectedBalanceChange);
        
        if (abs($balanceChange - $expectedBalanceChange) < 0.01) {
            $this->info("✅ Логика возврата списанных бонусов работает корректно!");
        } else {
            $this->error("❌ Ошибка в логике возврата списанных бонусов");
        }
        
        // *** НОВЫЙ ТЕСТ 10: Тест уровней бонусов ***
        $this->info("=== ТЕСТ 10: Тест уровней бонусов ===");
        
        // Создаем нового пользователя для теста уровней
        $timestamp4 = time() + 3;
        $levelUser = User::create([
            'phone' => '+7777' . $timestamp4,
            'name' => 'Test User Level',
            'email' => 'testlevel' . $timestamp4 . '@example.com',
            'gender' => 'male',
            'city' => 'Moscow',
            'birthday' => '1990-01-01',
            'children' => 'none',
            'marital_status' => 'single',
            'purchase_amount' => 0,
            'bonus_amount' => 0,
            'role' => 'user',
            'profile_completed_bonus' => true
        ]);
        
        // Проверяем начальный уровень (бронза)
        $initialLevel = $bonusService->getUserLevel($levelUser);
        $this->info("1. Начальный уровень: {$initialLevel->value} ({$initialLevel->getCashbackPercent()}%)");
        
        // Поднимаем до серебра
        $bonusService->creditBonus($levelUser, 12000, 'LEVEL_TEST_1_' . time());
        $levelUser->refresh();
        $silverLevel = $bonusService->getUserLevel($levelUser);
        $this->info("2. После покупки 12000₽: {$silverLevel->value} ({$silverLevel->getCashbackPercent()}%)");
        
        // Поднимаем до золота
        $bonusService->creditBonus($levelUser, 20000, 'LEVEL_TEST_2_' . time());
        $levelUser->refresh();
        $goldLevel = $bonusService->getUserLevel($levelUser);
        $this->info("3. После покупки еще 20000₽: {$goldLevel->value} ({$goldLevel->getCashbackPercent()}%)");
        
        // Проверяем информацию о бонусах
        $bonusInfo = $bonusService->getBonusInfo($levelUser);
        $this->info("4. Информация о бонусах:");
        $this->info("   Уровень: {$bonusInfo['level']}");
        $this->info("   Кэшбэк: {$bonusInfo['cashback_percent']}%");
        $this->info("   Следующий уровень: " . ($bonusInfo['next_level'] ?? 'нет'));
        $this->info("   Прогресс: {$bonusInfo['progress_to_next_level']}%");
        
        // Опускаем обратно до серебра
        $bonusService->refundBonusByReceipt($levelUser, 'LEVEL_REFUND_1_' . time(), 'LEVEL_TEST_2_' . time(), 15000);
        $levelUser->refresh();
        $backToSilverLevel = $bonusService->getUserLevel($levelUser);
        $this->info("5. После возврата 15000₽: {$backToSilverLevel->value} ({$backToSilverLevel->getCashbackPercent()}%)");
        
        // Опускаем до бронзы
        $bonusService->refundBonusByReceipt($levelUser, 'LEVEL_REFUND_2_' . time(), 'LEVEL_TEST_1_' . time(), 12000);
        $levelUser->refresh();
        $backToBronzeLevel = $bonusService->getUserLevel($levelUser);
        $this->info("6. После возврата еще 12000₽: {$backToBronzeLevel->value} ({$backToBronzeLevel->getCashbackPercent()}%)");
        
        $this->info("✅ Тест уровней бонусов завершен!");
        
        // Очистка тестовых данных
        $levelUser->bonuses()->delete();
        $levelUser->delete();
        $this->line('');
        
        // Очистка основного пользователя
        $user->bonuses()->delete();
        $user->delete();
        
        $newUser->bonuses()->delete();
        $newUser->delete();
        
        $testUser->bonuses()->delete();
        $testUser->delete();
        
        $this->info("=== ТЕСТ ЗАВЕРШЕН ===");
        
        return 0;
    }
} 