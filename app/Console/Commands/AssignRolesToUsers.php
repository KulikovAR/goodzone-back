<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignRolesToUsers extends Command
{
    protected $signature = 'users:assign-roles';
    protected $description = 'Массово назначить роли пользователям на основе поля role в таблице users';

    public function handle()
    {
        $roles = ['user', 'admin'];
        $total = 0;
        foreach ($roles as $role) {
            $users = User::where('role', $role)->get();
            foreach ($users as $user) {
                $user->assignRole($role);
                $this->line("Пользователю #{$user->id} ({$user->email}) назначена роль {$role}");
                $total++;
            }
        }
        $this->info("Готово! Назначено ролей: {$total}");
    }
} 