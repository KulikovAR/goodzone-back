<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;
use App\Models\User;

class RegisterUserDto extends Data
{
    public function __construct(
        public string $phone,
        public bool $already_in_app = false
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            phone: $user->phone,
            already_in_app: $user->come_from_app
        );
    }
}