<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowUserDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:db {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Показать пользователя прямым запросом к БД';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        
        $user = DB::select('SELECT * FROM users WHERE phone = ?', [$phone]);
        
        if (empty($user)) {
            $this->error("Пользователь с номером {$phone} не найден");
            return 1;
        }

        $this->info("=== ПРЯМОЙ ЗАПРОС К БД ===");
        $this->line(json_encode($user[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return 0;
    }
}
