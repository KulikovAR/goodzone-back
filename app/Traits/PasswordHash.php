<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;

trait PasswordHash
{
    protected function hashMake(string $password): string
    {
        return Hash::make($password);
    }
}
