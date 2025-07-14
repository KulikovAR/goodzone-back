<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Bonus;
use Illuminate\Console\Command;

class MigrateOldPurchaseAmounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:purchase-amounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Мигрировать старые данные о покупках в новую логику';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== МИГРАЦИЯ СУММ ПОКУПОК ===");
        
        $users = User::all();
        $this->info("Найдено пользователей: {$users->count()}");
        $this->line('');
        
        foreach ($users as $user) {
            $this->info("Обрабатываем пользователя: {$user->phone}");
            
            // Рассчитываем чистую сумму покупок (покупки минус возвраты) 
            $purchases = Bonus::where('user_id', $user->id)
                ->where('type', 'regular')
                ->sum('purchase_amount');
                
            $refunds = Bonus::where('user_id', $user->id)
                ->where('type', 'refund')
                ->sum('purchase_amount');
                
            $netPurchaseAmount = max(0, $purchases - $refunds);
            
                            $this->line("  Старая сумма в поле: " . (string)$user->purchase_amount);
            $this->line("  Покупки из бонусов: {$purchases}");
            $this->line("  Возвраты из бонусов: {$refunds}");
            $this->line("  Новая чистая сумма: {$netPurchaseAmount}");
            
            // Обновляем поле purchase_amount
            $user->update(['purchase_amount' => $netPurchaseAmount]);
            
            $this->info("  ✅ Обновлено");
            $this->line('');
        }
        
        $this->info("Миграция завершена!");
        
        return 0;
    }
}
