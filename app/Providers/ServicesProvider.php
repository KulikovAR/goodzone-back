<?php

namespace App\Providers;

use App\Services\BonusService;
use App\Services\ExpoNotificationService;
use Illuminate\Support\ServiceProvider;

class ServicesProvider extends ServiceProvider
{
    public function register(): void
    {
        $pushService = new ExpoNotificationService();
        $this->app->instance(ExpoNotificationService::class, $pushService);

        $bonusService = new BonusService($pushService);
        $this->app->instance(BonusService::class, $bonusService);
    }
}