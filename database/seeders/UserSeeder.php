<?php

namespace Database\Seeders;

use App\Enums\EnvironmentTypeEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    const ADMIN_PASSWORD = 'admin@admin';

    const ADMIN_EMAIL = 'admin@admin';

    const USER_PASSWORD = 'test@test.ru';

    const USER_EMAIL = 'test@test.ru';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (App::environment(EnvironmentTypeEnum::productEnv())) {
            return;
        }

        $userAdmin = User::factory()->create(
            [
                'password' => Hash::make(self::ADMIN_PASSWORD),
                'email' => self::ADMIN_EMAIL,
            ]
        );
        $userAdmin->assignRole(Role::ROLE_ADMIN);

        $user = User::factory()->create(
            [
                'password' => Hash::make(self::USER_PASSWORD),
                'email' => self::USER_EMAIL,
            ],
        );
        $user->assignRole(Role::ROLE_USER);
    }
}
