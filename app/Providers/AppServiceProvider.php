<?php

namespace App\Providers;

use App\Contracts\AuthServiceContract;
use App\Enums\EnvironmentTypeEnum;
use App\Services\AuthService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (App::environment(EnvironmentTypeEnum::notProductEnv())) {
            $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
        }

        $this->app->bind(AuthServiceContract::class, AuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
