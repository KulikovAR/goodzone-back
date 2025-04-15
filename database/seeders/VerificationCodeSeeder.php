<?php

namespace Database\Seeders;

use App\Enums\EnvironmentTypeEnum;
use App\Models\VerificationCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class VerificationCodeSeeder extends Seeder
{
    public function run(): void
    {
        if (App::environment(EnvironmentTypeEnum::productEnv())) {
            return;
        }

        VerificationCode::factory()->count(10)->create();
    }
}