<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ShowUserBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:show {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Показать все записи бонусов пользователя';

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

        $bonuses = $user->bonuses()->orderBy('created_at', 'desc')->get();
        
        $this->info("Пользователь: {$user->phone}");
        $this->info("Текущий баланс: " . (float)$user->bonus_amount);
        $this->info("Всего записей: {$bonuses->count()}");
        $this->line('');
        
        if ($bonuses->isEmpty()) {
            $this->warn('Нет записей бонусов');
            return 0;
        }

        $headers = ['ID', 'Дата', 'Сумма', 'Покупка', 'Тип', 'Статус', 'ID чека', 'Родит. чек'];
        $rows = [];
        
        foreach ($bonuses as $bonus) {
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
