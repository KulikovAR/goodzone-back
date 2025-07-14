<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TestBonusApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:bonus-api {--user=} {--create-test-user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестировать все API эндпоинты бонусной системы';

    /**
     * Execute the console command.
     */
    public function handle(BonusService $bonusService)
    {
        $this->info("=== ТЕСТ ВСЕХ API БОНУСНОЙ СИСТЕМЫ ===");
        $this->line('');

        $user = $this->getTestUser();
        if (!$user) {
            return 1;
        }

        $token = $user->createToken('test-token')->plainTextToken;

        $this->info("Пользователь: {$user->phone}");
        $this->info("Токен: " . substr($token, 0, 20) . "...");
        $this->line('');

        // Тест 1: Получение информации о бонусах
        $this->testBonusInfo($token);

        // Тест 2: Получение уровней бонусов
        $this->testBonusLevels($token);

        // Тест 3: Получение истории бонусов
        $this->testBonusHistory($token);

        // Тест 4: Начисление бонусов (через 1С)
        $this->testBonusCredit($token, $user);

        // Тест 5: Списание бонусов
        $this->testBonusDebit($token, $user);

        // Тест 6: Возврат бонусов
        $this->testBonusRefund($token, $user);

        $this->info("✅ Все API тесты завершены!");
        return 0;
    }

    private function getTestUser(): ?User
    {
        $userPhone = $this->option('user');
        
        if ($userPhone) {
            $user = User::where('phone', $userPhone)->first();
            if (!$user) {
                $this->error("Пользователь с номером {$userPhone} не найден");
                return null;
            }
            return $user;
        }

        if ($this->option('create-test-user')) {
            return $this->createTestUser();
        }

        $this->error("Укажите --user=PHONE или --create-test-user");
        return null;
    }

    private function createTestUser(): User
    {
        $this->info("Создаем тестового пользователя...");
        
        return User::create([
            'phone' => '+7999' . time(),
            'name' => 'Test API User',
            'email' => 'testapi' . time() . '@example.com',
            'gender' => 'male',
            'city' => 'Moscow',
            'birthday' => '1990-01-01',
            'children' => 'none',
            'marital_status' => 'single',
            'purchase_amount' => 5000,
            'bonus_amount' => 250,
            'role' => 'user',
            'profile_completed_bonus' => true
        ]);
    }

    private function testBonusInfo(string $token): void
    {
        $this->info("📊 ТЕСТ 1: GET /api/bonus/info");
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('http://goodzone-nginx/api/bonus/info');

        if ($response->successful()) {
            $this->info("✅ Статус: {$response->status()}");
            $data = $response->json();
            $this->info("   Баланс: " . ($data['data']['bonus_amount'] ?? 'N/A'));
            $this->info("   Уровень: " . ($data['data']['level'] ?? 'N/A'));
            $this->info("   Кэшбэк: " . ($data['data']['cashback_percent'] ?? 'N/A') . "%");
        } else {
            $this->error("❌ Статус: {$response->status()}");
            $this->error("   Ответ: " . $response->body());
        }
        $this->line('');
    }

    private function testBonusLevels(string $token): void
    {
        $this->info("🥉 ТЕСТ 2: GET /api/bonus/levels");
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('http://goodzone-nginx/api/bonus/levels');

        if ($response->successful()) {
            $this->info("✅ Статус: {$response->status()}");
            $data = $response->json();
            $levels = $data['data'] ?? [];
            $this->info("   Найдено уровней: " . count($levels));
            
            foreach ($levels as $level) {
                $this->info("   - {$level['name']}: {$level['cashback_percent']}% (от {$level['min_purchase_amount']}₽)");
            }
        } else {
            $this->error("❌ Статус: {$response->status()}");
            $this->error("   Ответ: " . $response->body());
        }
        $this->line('');
    }

    private function testBonusHistory(string $token): void
    {
        $this->info("📜 ТЕСТ 3: GET /api/bonus/history");
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('http://goodzone-nginx/api/bonus/history');

        if ($response->successful()) {
            $this->info("✅ Статус: {$response->status()}");
            $data = $response->json();
            $history = $data['data']['history'] ?? [];
            $totalCount = $data['data']['total_count'] ?? 0;
            $this->info("   Записей в истории: {$totalCount}");
            $this->info("   Показано записей: " . count($history));
        } else {
            $this->error("❌ Статус: {$response->status()}");
            $this->error("   Ответ: " . $response->body());
        }
        $this->line('');
    }

    private function testBonusCredit(string $token, User $user): void
    {
        $this->info("💰 ТЕСТ 4: POST /api/bonus/credit (через 1С)");
        
        // Получаем 1С пользователя
        $oneCUser = User::where('role', \App\Enums\UserRole::ONE_C)->first();
        if (!$oneCUser) {
            $this->error("❌ 1С пользователь не найден");
            $this->line('');
            return;
        }

        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('http://goodzone-nginx/api/bonus/credit', [
            'phone' => $user->phone,
            'purchase_amount' => 2000,
            'id_sell' => 'API_TEST_' . time(),
        ]);

        if ($response->successful()) {
            $this->info("✅ Статус: {$response->status()}");
            $data = $response->json();
            $this->info("   Начислено бонусов: " . ($data['data']['calculated_bonus_amount'] ?? 'N/A'));
            $this->info("   Уровень пользователя: " . ($data['data']['user_level'] ?? 'N/A'));
        } else {
            $this->error("❌ Статус: {$response->status()}");
            $this->error("   Ответ: " . $response->body());
        }
        $this->line('');
    }

    private function testBonusDebit(string $token, User $user): void
    {
        $this->info("💳 ТЕСТ 5: POST /api/bonus/debit");
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('http://goodzone-nginx/api/bonus/debit', [
            'amount' => 50,
            'id_sell' => 'DEBIT_API_TEST_' . time(),
            'parent_id_sell' => 'PARENT_API_TEST_' . time(),
        ]);

        if ($response->successful()) {
            $this->info("✅ Статус: {$response->status()}");
            $data = $response->json();
            $this->info("   Списано бонусов: " . ($data['data']['debited_amount'] ?? 'N/A'));
            $this->info("   Остаток баланса: " . ($data['data']['remaining_balance'] ?? 'N/A'));
        } else {
            $this->error("❌ Статус: {$response->status()}");
            $this->error("   Ответ: " . $response->body());
        }
        $this->line('');
    }

    private function testBonusRefund(string $token, User $user): void
    {
        $this->info("🔄 ТЕСТ 6: POST /api/bonus/refund (через 1С)");
        
        // Получаем 1С пользователя
        $oneCUser = User::where('role', \App\Enums\UserRole::ONE_C)->first();
        if (!$oneCUser) {
            $this->error("❌ 1С пользователь не найден");
            $this->line('');
            return;
        }

        $oneCToken = $oneCUser->createToken('test-token')->plainTextToken;
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $oneCToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('http://goodzone-nginx/api/bonus/refund', [
            'phone' => $user->phone,
            'refund_amount' => 1000,
            'id_sell' => 'REFUND_API_TEST_' . time(),
            'parent_id_sell' => 'API_TEST_' . time(),
        ]);

        if ($response->successful()) {
            $this->info("✅ Статус: {$response->status()}");
            $data = $response->json();
            $this->info("   Возвращено бонусов: " . ($data['data']['refunded_bonus_amount'] ?? 'N/A'));
            $this->info("   Возвращено списанных: " . ($data['data']['returned_debit_amount'] ?? 'N/A'));
        } else {
            $this->error("❌ Статус: {$response->status()}");
            $this->error("   Ответ: " . $response->body());
        }
        $this->line('');
    }
} 