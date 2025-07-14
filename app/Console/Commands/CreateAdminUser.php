<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin 
                            {--email=admin@goodzone.ru} 
                            {--name=Admin} 
                            {--phone=+70000000001} 
                            {--password=admin123}';
    
    protected $description = 'Create admin user for Filament panel';

    public function handle()
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $phone = $this->option('phone');
        $password = $this->option('password');

        // Проверить, существует ли уже админ с таким email
        $existingAdmin = User::where('email', $email)->first();
        
        if ($existingAdmin) {
            $this->warn('Admin user already exists:');
            $this->line("ID: {$existingAdmin->id}");
            $this->line("Email: {$existingAdmin->email}");
            $this->line("Name: {$existingAdmin->name}");
            $this->line("Role: {$existingAdmin->role->value}");
            
            if ($this->confirm('Do you want to update password?')) {
                $existingAdmin->update([
                    'password' => Hash::make($password)
                ]);
                $this->info('Password updated successfully!');
                $this->line("New password: {$password}");
            }
            
            return;
        }

        // Создать нового админа
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => UserRole::ADMIN,
            'password' => Hash::make($password),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->info('Admin user created successfully!');
        $this->line("User ID: {$admin->id}");
        $this->line("Email: {$admin->email}");
        $this->line("Name: {$admin->name}");
        $this->line("Role: {$admin->role->value}");
        $this->line("Password: {$password}");
        $this->line('');
        $this->info('Access Filament panel at: /admin');
        $this->warn('Save credentials securely!');
    }
} 