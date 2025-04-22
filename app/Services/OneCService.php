<?php

namespace App\Services;

use App\Jobs\OneCRequest;
use App\Models\User;

class OneCService
{
    public function __construct(
        private OneCClient $client
    ) {}

    public function sendRegister(User $user, bool $alreadyInApp = false): void
    {
        $data = [
            'phone' => $user->phone,
            'already_in_app' => $alreadyInApp
        ];

        OneCRequest::dispatch('api/register', $data, 'POST');
    }

    public function sendUser(User $user): void
    {
        OneCRequest::dispatch('api/user/' . $user->phone, [], 'GET');
    }

    public function updateUser(User $user): void
    {
        $data = [
            'name' => $user->name,
            'gender' => $user->gender,
            'city' => $user->city,
            'email' => $user->email,
        ];

        OneCRequest::dispatch('api/user/' . $user->phone, $data, 'PUT');
    }
}