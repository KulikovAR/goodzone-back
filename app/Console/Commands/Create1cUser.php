<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Create1cUser extends Command
{
    protected $signature = 'user:create-1c';
    protected $description = 'Create or update 1C user and generate new token';

    public function handle()
    {
        $user = User::where('role', UserRole::ONE_C)->first();

        if (!$user) {
            $user = User::create([
                'phone' => '1c-' . Str::random(10),
                'role' => UserRole::ONE_C,
                'name' => '1C Integration'
            ]);
        }

        $token = $user->createToken('1c-token')->plainTextToken;

        $this->info('1C User Token: ' . $token);

        return Command::SUCCESS;
    }
}
