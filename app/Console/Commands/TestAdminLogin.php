<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Filament\Panel;

class TestAdminLogin extends Command
{
    protected $signature = 'admin:test-login {email} {password}';
    protected $description = 'Test admin login credentials';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $this->info("Testing login for: {$email}");

        // Найти пользователя
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found with email: {$email}");
            return 1;
        }

        $this->info("User found:");
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Role: {$user->role->value}");

        // Проверить пароль
        if (Hash::check($password, $user->password)) {
            $this->info("✓ Password is correct!");
        } else {
            $this->error("✗ Password is incorrect!");
            return 1;
        }

        // Проверить доступ к панели (создаем мок панели)
        $panel = new Panel('admin');
        if ($user->canAccessPanel($panel)) {
            $this->info("✓ User can access Filament panel!");
        } else {
            $this->error("✗ User cannot access Filament panel!");
            return 1;
        }

        // Проверить, что пользователь не удален
        if ($user->deleted_at) {
            $this->error("✗ User is soft deleted!");
            return 1;
        }

        $this->info("✓ All checks passed! User should be able to login.");
        $this->line("");
        $this->line("Try logging in at: http://localhost:8000/admin/login");
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");

        return 0;
    }
} 