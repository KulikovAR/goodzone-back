<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Enums\UserRole;

class Test1cRegistration extends Command
{
    protected $signature = 'test:1c-registration';
    protected $description = 'Тестирует новый эндпоинт регистрации пользователей из 1С';

    public function handle()
    {
        // Создаем 1С пользователя для тестирования
        $oneCUser = User::where('role', UserRole::ONE_C)->first();
        if (!$oneCUser) {
            $this->error('1С пользователь не найден. Запустите: php artisan user:create-1c');
            return self::FAILURE;
        }

        $token = $oneCUser->createToken('test-token')->plainTextToken;
        $this->info("Используем токен 1С пользователя: {$oneCUser->name}");

        // Данные для тестирования
        $testData = [
            'phone' => '+7' . rand(9000000000, 9999999999),
            'name' => 'Тестовый Пользователь',
            'gender' => 'male',
            'city' => 'Москва',
            'email' => 'test-' . time() . '@example.com',
            'birthday' => '1990-01-15',
        ];

        $this->info("Отправляем запрос с данными:");
        $this->table(
            ['Поле', 'Значение'],
            collect($testData)->map(fn($value, $key) => [$key, $value])->values()->toArray()
        );

        // Отправляем запрос
        try {
            // Используем nginx контейнер вместо localhost
            $baseUrl = 'http://goodzone-nginx';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$baseUrl}/api/1c/register", $testData);

            $this->info("Код ответа: {$response->status()}");
            $this->info("Тело ответа:");
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['user_id'])) {
                    $user = User::find($data['data']['user_id']);
                    if ($user) {
                        $this->info("\nПользователь успешно создан:");
                        $this->info("ID: {$user->id}");
                        $this->info("Телефон: {$user->phone}");
                        $this->info("Имя: {$user->name}");
                        $this->info("Email: {$user->email}");
                        $this->info("Профиль заполнен: " . ($user->isProfileCompleted() ? 'Да' : 'Нет (нужны children/marital_status)'));
                        $this->info("Бонус за профиль: " . ($user->profile_completed_bonus_given ? 'Начислен' : 'НЕ начислен (профиль неполный)'));
                        
                        // Проверяем бонусы (их НЕ должно быть)
                        $bonuses = $user->bonuses()->where('type', 'regular')->get();
                        $this->info("Бонусов в базе: " . $bonuses->count() . " (ожидается 0)");
                    }
                }
                return self::SUCCESS;
            } else {
                $this->error("Запрос не удался!");
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("Ошибка при отправке запроса: " . $e->getMessage());
            return self::FAILURE;
        }
    }
} 