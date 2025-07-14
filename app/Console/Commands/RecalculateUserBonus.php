<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use Illuminate\Console\Command;

class RecalculateUserBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:recalculate {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Пересчитать баланс бонусов пользователя по номеру телефона';

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

        $oldBalance = $user->bonus_amount;
        
        $bonusService->recalculateUserBonus($user);
        
        $newBalance = $user->fresh()->bonus_amount;
        
        $this->info("Пользователь: {$user->phone}");
        $this->info("Баланс ДО пересчета: " . (float)$oldBalance);
        $this->info("Баланс ПОСЛЕ пересчета: " . (float)$newBalance);
        
        return 0;
    }
}
