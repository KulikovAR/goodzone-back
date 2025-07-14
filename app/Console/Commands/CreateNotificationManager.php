<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateNotificationManager extends Command
{
    protected $signature = 'user:create-notification-manager 
                            {--email=notifications@goodzone.ru} 
                            {--name=Notification Manager} 
                            {--phone=+70000000002} 
                            {--password=notif123}';
    
    protected $description = 'Create notification manager user for Filament panel';

    public function handle()
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $phone = $this->option('phone');
        $password = $this->option('password');

        // Проверить, существует ли уже менеджер уведомлений
        $existingManager = User::where('email', $email)->first();
        
        if ($existingManager) {
            $this->warn('Notification manager already exists:');
            $this->line("ID: {$existingManager->id}");
            $this->line("Email: {$existingManager->email}");
            $this->line("Name: {$existingManager->name}");
            
            if ($this->confirm('Do you want to update password?')) {
                $existingManager->update([
                    'password' => Hash::make($password)
                ]);
                $this->info('Password updated successfully!');
                $this->line("New password: {$password}");
            }
            
            return;
        }

        // Создать менеджера уведомлений (используем роль user, но потом можно создать отдельную роль)
        $manager = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => UserRole::USER, // Можно создать отдельную роль 'notification_manager'
            'password' => Hash::make($password),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->info('Notification manager created successfully!');
        $this->line("User ID: {$manager->id}");
        $this->line("Email: {$manager->email}");
        $this->line("Name: {$manager->name}");
        $this->line("Password: {$password}");
        $this->line('');
        $this->info('Access Filament panel at: /admin');
        $this->warn('Note: You may need to configure specific permissions for this user');
    }
} 