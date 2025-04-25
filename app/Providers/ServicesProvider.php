<?php

namespace App\Providers;

use App\Services\BonusService;
use App\Services\PushNotificationService;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Messaging;

class ServicesProvider extends ServiceProvider
{
    public function register(): void
    {
        $pushService = new PushNotificationService(
            $this->app->make(Messaging::class)
        );
        $this->app->instance(PushNotificationService::class, $pushService);

        $bonusService = new BonusService($pushService);
        $this->app->instance(BonusService::class, $bonusService);
    }
}