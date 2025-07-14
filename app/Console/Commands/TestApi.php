<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BonusService;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BonusController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:api {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестировать API эндпоинты для получения баланса';

    /**
     * Execute the console command.
     */
    public function handle(BonusService $bonusService)
    {
        $phone = $this->argument('phone');
        
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            $this->error("Пользователь с номером {$phone} не найден");
            return 1;
        }

        $this->info("=== ТЕСТ API ЭНДПОИНТОВ ===");
        $this->info("Пользователь: {$user->phone}");
        $this->info("Баланс в БД: " . (float)$user->bonus_amount);
        $this->line('');

        // Эмулируем авторизацию
        Auth::setUser($user);

        // Тестируем getBonusInfo
        $this->info("=== BONUS INFO API ===");
        $bonusInfo = $bonusService->getBonusInfo($user);
        $this->line(json_encode($bonusInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line('');

        // Тестируем уровни бонусов
        $this->info("=== BONUS LEVELS API ===");
        $userLevel = $bonusService->getUserLevel($user);
        $this->info("Текущий уровень: {$userLevel->value}");
        $this->info("Процент кэшбэка: {$userLevel->getCashbackPercent()}%");
        $this->info("Минимальная сумма для уровня: {$userLevel->getMinPurchaseAmount()}₽");
        
        if ($userLevel->getNextLevel()) {
            $this->info("Следующий уровень: {$userLevel->getNextLevel()->value}");
            $this->info("Сумма для следующего уровня: {$userLevel->getNextLevelMinAmount()}₽");
            $this->info("Прогресс к следующему уровню: {$userLevel->getProgressToNextLevel($user->purchase_amount)}%");
        } else {
            $this->info("Максимальный уровень достигнут");
        }
        $this->line('');

        // Тестируем User API (как возвращается в UserController::show)
        $this->info("=== USER API ===");
        $userArray = $user->toArray();
        $this->line(json_encode($userArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return 0;
    }
}
