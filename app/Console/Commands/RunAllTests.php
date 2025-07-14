<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunAllTests extends Command
{
    protected $signature = 'test:run-all';
    protected $description = 'Запускает все тесты с предварительными исправлениями';

    public function handle(): int
    {
        $this->info("🧪 ЗАПУСК ВСЕХ ТЕСТОВ");
        $this->line('');
        
        // Шаг 1: Исправляем базу данных
        $this->info("🔧 Шаг 1: Исправляем базу данных...");
        Artisan::call('fix:database-for-tests');
        $this->info("✅ База данных исправлена");
        $this->line('');
        
        // Шаг 2: Запускаем миграции
        $this->info("📦 Шаг 2: Запускаем миграции...");
        Artisan::call('migrate');
        $this->info("✅ Миграции выполнены");
        $this->line('');
        
        // Шаг 3: Запускаем тесты
        $this->info("🧪 Шаг 3: Запускаем тесты...");
        $result = Artisan::call('test');
        
        if ($result === 0) {
            $this->info("✅ Все тесты прошли успешно!");
        } else {
            $this->error("❌ Некоторые тесты не прошли");
        }
        
        return $result;
    }
} 