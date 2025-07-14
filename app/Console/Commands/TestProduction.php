<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TestProduction extends Command
{
    protected $signature = 'test:production {--filter=} {--v}';
    protected $description = 'Запуск тестов для продакшена';

    public function handle()
    {
        $this->info('🧪 Запуск тестов для продакшена...');
        
        // Создаем необходимые таблицы для тестов
        $this->createTestTables();
        
        $filter = $this->option('filter');
        $verbose = $this->option('v');
        
        $command = 'php artisan test tests/Production/';
        
        if ($filter) {
            $command .= ' --filter=' . $filter;
        }
        
        if ($verbose) {
            $command .= ' --verbose';
        }
        
        $this->info('Выполняем команду: ' . $command);
        
        $result = shell_exec($command . ' 2>&1');
        
        $this->info($result);
        
        $this->info('✅ Тесты для продакшена завершены');
    }
    
    private function createTestTables(): void
    {
        $this->info('📋 Создание необходимых таблиц для тестов...');
        
        // Создаем таблицу bonuses если её нет
        if (!\Schema::hasTable('bonuses')) {
            \Schema::create('bonuses', function ($table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->string('type')->default('regular');
                $table->string('receipt_id')->nullable();
                $table->string('id_sell')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
            $this->info('✅ Таблица bonuses создана');
        }
        
        // Создаем таблицу user_device_tokens если её нет
        if (!\Schema::hasTable('user_device_tokens')) {
            \Schema::create('user_device_tokens', function ($table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('device_token');
                $table->timestamps();
            });
            $this->info('✅ Таблица user_device_tokens создана');
        }
        
        // Создаем таблицу verification_codes если её нет
        if (!\Schema::hasTable('verification_codes')) {
            \Schema::create('verification_codes', function ($table) {
                $table->id();
                $table->string('phone');
                $table->string('code');
                $table->timestamp('expires_at');
                $table->timestamps();
            });
            $this->info('✅ Таблица verification_codes создана');
        }
        
        $this->info('✅ Все необходимые таблицы созданы');
    }
} 