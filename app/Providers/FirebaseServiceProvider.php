<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Messaging::class, function ($app) {
            return (new Factory)
                ->withServiceAccount(storage_path('app/firebase-credentials.json'))
                ->createMessaging();
        });
    }
}