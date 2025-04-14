<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->resetCachedRolesAndPermissions();

        $admin = Role::create(['name' => Role::ROLE_ADMIN, 'guard_name' => 'web']);
        $user = Role::create(['name' => Role::ROLE_USER, 'guard_name' => 'web']);

        Permission::create(['name' => Permission::PERMISSION]);

        $admin->givePermissionTo([Permission::PERMISSION]);
        $user->givePermissionTo([Permission::PERMISSION]);
    }

    public function resetCachedRolesAndPermissions(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
