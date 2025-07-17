<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use Illuminate\Console\Command;

class CheckUserPurchaseAmount extends Command
{
    protected $signature = 'user:check-purchase-amount {phone}';

    protected $description = 'Проверить сумму покупок пользователя и его бонусы';

    public function handle(BonusService $bonusService)
    {
        $phone = $this->argument('phone');
        
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            $this->error("Пользователь с номером {$phone} не найден");
            return 1;
        }

        $this->info("=== ИНФОРМАЦИЯ О ПОЛЬЗОВАТЕЛЕ ===");
        $this->info("Телефон: {$user->phone}");
        $this->info("Сумма покупок в БД: " . (float)$user->purchase_amount);
        $this->info("Баланс бонусов: " . (float)$user->bonus_amount);
        $this->line('');

        // Получаем информацию о бонусах через сервис
        $bonusInfo = $bonusService->getBonusInfo($user);
        
        $this->info("=== ИНФОРМАЦИЯ О БОНУСАХ ===");
        $this->info("Общий баланс: {$bonusInfo['bonus_amount']}");
        $this->info("Обычные бонусы: {$bonusInfo['bonus_amount_without']}");
        $this->info("Акционные бонусы: {$bonusInfo['promotional_bonus_amount']}");
        $this->info("Уровень: {$bonusInfo['level']}");
        $this->info("Кэшбэк: {$bonusInfo['cashback_percent']}%");
        $this->info("Сумма покупок из API: {$bonusInfo['total_purchase_amount']}");
        $this->line('');

        // Проверяем все записи бонусов
        $bonuses = $user->bonuses()->orderBy('created_at', 'desc')->get();
        
        $this->info("=== ПОСЛЕДНИЕ 10 ЗАПИСЕЙ БОНУСОВ ===");
        $headers = ['ID', 'Дата', 'Сумма', 'Покупка', 'Тип', 'Статус', 'ID чека', 'ID чека покупки'];
        $rows = [];
        
        foreach ($bonuses->take(10) as $bonus) {
            $rows[] = [
                $bonus->id,
                $bonus->created_at->format('d.m.Y H:i'),
                $bonus->amount,
                $bonus->purchase_amount ?? '-',
                $bonus->type,
                $bonus->status,
                $bonus->id_sell ?? '-',
                $bonus->parent_id_sell ?? '-',
            ];
        }
        
        $this->table($headers, $rows);
        
        return 0;
    }
} 