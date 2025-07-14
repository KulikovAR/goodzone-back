<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ShowUserObject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:show {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Показать объект пользователя как он возвращается в API';

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

        $this->info("=== ОБЪЕКТ ПОЛЬЗОВАТЕЛЯ ===");
        $this->line("Прямое обращение к bonus_amount: " . $user->bonus_amount);
        $this->line("Все атрибуты:");
        $this->line(json_encode($user->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->line("Все атрибуты включая скрытые:");
        $this->line(json_encode($user->attributesToArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return 0;
    }
}
