<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Create1cUser extends Command
{
    protected $signature = 'user:create-1c {--email=1c@goodzone.ru} {--name=1C Integration} {--phone=+70000000000}';
    protected $description = 'Create 1C user with access token';

    public function handle()
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $phone = $this->option('phone');

        // Проверить, существует ли уже пользователь 1С
        $existingUser = User::where('role', UserRole::ONE_C)->first();
        
        if ($existingUser) {
            $this->warn('1C user already exists:');
            $this->line("ID: {$existingUser->id}");
            $this->line("Email: {$existingUser->email}");
            $this->line("Name: {$existingUser->name}");
            
            if ($this->confirm('Do you want to create a new token for existing user?')) {
                $token = $existingUser->createToken('1c-integration-' . Str::random(8));
                $this->info('1C User Token:');
                $this->line("Bearer " . $token->plainTextToken);
            }
            
            return;
        }

        // Создать нового пользователя 1С
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => UserRole::ONE_C,
            'password' => bcrypt(Str::random(16)),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Создать токен
        $token = $user->createToken('1c-integration');

        $this->info('1C user created successfully!');
        $this->line("User ID: {$user->id}");
        $this->line("Email: {$user->email}");
        $this->line("Role: {$user->role->value}");
        $this->line('');
        $this->info('1C User Token:');
        $this->line("Bearer " . $token->plainTextToken);
        $this->line('');
        $this->warn('Save this token securely! It won\'t be shown again.');
    }
}
