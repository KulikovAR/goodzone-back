<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use Illuminate\Console\Command;

class TestDuplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:duplication {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестировать защиту от дублирования id_sell';

    /**
     * Execute the console command.
     */
    public function handle(BonusService $bonusService)
    {
        $phone = $this->argument('phone');
        
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            $this->error("Пользователь с номером {$phone} не найден");
            return 1;
        }

        $this->info("=== ТЕСТ ЗАЩИТЫ ОТ ДУБЛИРОВАНИЯ ===");
        $this->info("Пользователь: {$user->phone}");
        $this->info("Баланс ДО теста: " . (float)$user->bonus_amount);
        $this->line('');

        // Тестируем дублирование начисления
        $testReceiptId = 'TEST_RECEIPT_' . time();
        
        $this->info("Первый вызов creditBonus с id_sell: {$testReceiptId}");
        try {
            $bonus1 = $bonusService->creditBonus($user, 1000, $testReceiptId);
            $this->info("✅ Успешно создан бонус ID: {$bonus1->id}, Amount: " . (string)$bonus1->amount);
        } catch (\Exception $e) {
            $this->error("❌ Ошибка: " . $e->getMessage());
        }
        
        $user->refresh();
        $this->info("Баланс после первого вызова: " . (float)$user->bonus_amount);
        $this->line('');
        
        $this->info("Второй вызов creditBonus с тем же id_sell: {$testReceiptId}");
        try {
            $bonus2 = $bonusService->creditBonus($user, 1000, $testReceiptId);
            $this->info("✅ Возвращен существующий бонус ID: {$bonus2->id}, Amount: " . (string)$bonus2->amount);
            
            if ($bonus1->id === $bonus2->id) {
                $this->info("✅ Правильно: возвращена та же запись");
            } else {
                $this->error("❌ Ошибка: возвращена другая запись!");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка: " . $e->getMessage());
        }
        
        $user->refresh();
        $this->info("Баланс после второго вызова: " . (float)$user->bonus_amount);
        
        return 0;
    }
}
