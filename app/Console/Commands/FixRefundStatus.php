<?php

namespace App\Console\Commands;

use App\Models\Bonus;
use Illuminate\Console\Command;

class FixRefundStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:fix-refund-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Исправить статус записей возврата на show-and-calc';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $refunds = Bonus::where('type', 'refund')
            ->where('status', 'show-not-calc')
            ->get();
            
        if ($refunds->isEmpty()) {
            $this->info('Нет записей возврата для исправления');
            return 0;
        }

        $this->info("Найдено {$refunds->count()} записей возврата для исправления:");
        
        foreach ($refunds as $refund) {
            $this->line("ID: {$refund->id}, User: {$refund->user_id}, Amount: " . (string)$refund->amount . ", Status: {$refund->status}");
            
            $refund->update(['status' => 'show-and-calc']);
            
            $this->info("✅ Обновлен статус записи ID: {$refund->id}");
        }
        
        $this->info("Все записи возврата исправлены!");
        
        return 0;
    }
}
