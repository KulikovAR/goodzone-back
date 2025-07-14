<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDatabaseForTests extends Command
{
    protected $signature = 'fix:database-for-tests';
    protected $description = 'Исправляет проблемы с базой данных для тестов';

    public function handle(): int
    {
        $this->info("🔧 Исправляем проблемы с базой данных...");

        // Проверяем и добавляем недостающие колонки в users
        if (!Schema::hasColumn('users', 'role')) {
            $this->info("Добавляем колонку 'role' в таблицу users...");
            Schema::table('users', function ($table) {
                $table->string('role')->default('user');
            });
        }

        if (!Schema::hasColumn('users', 'bonus_amount')) {
            $this->info("Добавляем колонку 'bonus_amount' в таблицу users...");
            Schema::table('users', function ($table) {
                $table->integer('bonus_amount')->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'purchase_amount')) {
            $this->info("Добавляем колонку 'purchase_amount' в таблицу users...");
            Schema::table('users', function ($table) {
                $table->integer('purchase_amount')->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'birthday')) {
            $this->info("Добавляем колонку 'birthday' в таблицу users...");
            Schema::table('users', function ($table) {
                $table->timestamp('birthday')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'children')) {
            $this->info("Добавляем колонку 'children' в таблицу users...");
            Schema::table('users', function ($table) {
                $table->text('children')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'marital_status')) {
            $this->info("Добавляем колонку 'marital_status' в таблицу users...");
            Schema::table('users', function ($table) {
                $table->text('marital_status')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'profile_completed_bonus_given')) {
            $this->info("Добавляем колонку 'profile_completed_bonus_given' в таблицу users...");
            Schema::table('users', function ($table) {
                $table->boolean('profile_completed_bonus_given')->default(false);
            });
        }

        // Проверяем таблицу verification_codes
        if (!Schema::hasTable('verification_codes')) {
            $this->info("Создаем таблицу 'verification_codes'...");
            Schema::create('verification_codes', function ($table) {
                $table->id();
                $table->string('phone');
                $table->string('code');
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }

        // Проверяем таблицу bonuses
        if (!Schema::hasTable('bonuses')) {
            $this->info("Создаем таблицу 'bonuses'...");
            Schema::create('bonuses', function ($table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->decimal('purchase_amount', 10, 2)->nullable();
                $table->string('type');
                $table->string('status');
                $table->timestamp('expires_at')->nullable();
                $table->string('id_sell')->nullable();
                $table->string('parent_id_sell')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Проверяем таблицу user_device_tokens
        if (!Schema::hasTable('user_device_tokens')) {
            $this->info("Создаем таблицу 'user_device_tokens'...");
            Schema::create('user_device_tokens', function ($table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('device_token');
                $table->timestamps();
            });
        }

        $this->info("✅ База данных исправлена!");
        return 0;
    }
} 