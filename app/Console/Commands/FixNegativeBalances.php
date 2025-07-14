<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use Illuminate\Console\Command;

class FixNegativeBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:fix-negative-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Исправляет негативные балансы пользователей, убирает принудительное обнуление';

    /**
     * Execute the console command.
     */
    public function handle(BonusService $bonusService)
    {
        $this->info("=== ИСПРАВЛЕНИЕ НЕГАТИВНЫХ БАЛАНСОВ ===");
        
        $users = User::all();
        $fixed = 0;
        $total = $users->count();
        
        $this->info("Найдено пользователей: {$total}");
        $this->line('');
        
        foreach ($users as $user) {
            $oldBalance = $user->bonus_amount;
            
            // Пересчитываем баланс с новой логикой (без принудительного обнуления)
            $bonusService->recalculateUserBonus($user);
            $user->refresh();
            
            $newBalance = $user->bonus_amount;
            
            if ($oldBalance != $newBalance) {
                $this->line("Пользователь {$user->phone}: " . (float)$oldBalance . " → " . (float)$newBalance);
                $fixed++;
            }
        }
        
        $this->line('');
        $this->info("=== РЕЗУЛЬТАТЫ ===");
        $this->info("Обработано пользователей: {$total}");
        $this->info("Исправлено балансов: {$fixed}");
        
        if ($fixed > 0) {
            $this->info("✅ Исправление завершено успешно");
        } else {
            $this->info("ℹ️ Исправлений не требовалось");
        }
        
        return 0;
    }
} 