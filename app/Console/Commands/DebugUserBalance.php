<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DebugUserBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:debug {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Детальный дебаг расчета баланса пользователя';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            $this->error("Пользователь с номером {$phone} не найден");
            return 1;
        }

        $this->info("=== ДЕБАГ БАЛАНСА ===");
        $this->info("Пользователь: {$user->phone}");
        $this->info("Текущий баланс в БД: " . (float)$user->bonus_amount);
        $this->line('');

        // Regular/refund бонусы
        $regularBonuses = $user->bonuses()
            ->whereIn('type', ['regular', 'refund'])
            ->whereIn('status', ['show-and-calc', 'calc-not-show'])
            ->get();
            
        $this->info("=== REGULAR/REFUND БОНУСЫ ===");
        $regularTotal = 0;
        foreach ($regularBonuses as $bonus) {
            $this->line("ID: {$bonus->id}, Amount: " . (string)$bonus->amount . ", Type: {$bonus->type}, Status: {$bonus->status}");
            $regularTotal += $bonus->amount;
        }
        $this->info("Итого regular/refund: {$regularTotal}");
        $this->line('');

        // Promotional бонусы
        $promotionalBonuses = $user->bonuses()
            ->where('type', 'promotional')
            ->whereIn('status', ['show-and-calc', 'calc-not-show'])
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->get();
            
        $this->info("=== PROMOTIONAL БОНУСЫ ===");
        $promotionalTotal = 0;
        foreach ($promotionalBonuses as $bonus) {
            $this->line("ID: {$bonus->id}, Amount: " . (string)$bonus->amount . ", Expires: " . ($bonus->expires_at ? $bonus->expires_at->format('d.m.Y H:i') : 'never'));
            $promotionalTotal += $bonus->amount;
        }
        $this->info("Итого promotional: {$promotionalTotal}");
        $this->line('');

        $expectedTotal = $regularTotal + $promotionalTotal;
        if ($expectedTotal < 0) {
            $expectedTotal = 0;
        }
        
        $this->info("=== ИТОГОВЫЙ РАСЧЕТ ===");
        $this->info("Regular + Refund: {$regularTotal}");
        $this->info("Promotional: {$promotionalTotal}");
        $this->info("Ожидаемый баланс: {$expectedTotal}");
        $this->info("Фактический баланс: " . (float)$user->bonus_amount);
        
        if ($expectedTotal != $user->bonus_amount) {
            $this->error("РАСХОЖДЕНИЕ! Требуется пересчет");
        } else {
            $this->info("✅ Баланс корректный");
        }
        
        return 0;
    }
}
