<?php

namespace App\Services;

use App\DataTransferObjects\RegisterUserDto;
use App\Jobs\OneCRequest;
use App\Models\User;

class OneCService
{
    public function sendRegister(User $user): void
    {
        $dto = RegisterUserDto::fromUser($user);
        
        OneCRequest::dispatch(
            config('one-c.routes.register'),
            $dto->toArray(),
            'POST'
        );
    }

    public function sendUser(User $user): void
    {
        $endpoint = str_replace(
            '{phone}',
            $user->phone,
            config('one-c.routes.user')
        );
        
        OneCRequest::dispatch($endpoint, [], 'GET');
    }

    public function updateUser(User $user): void
    {
        $endpoint = str_replace(
            '{phone}',
            $user->phone,
            config('one-c.routes.user')
        );

        $data = [
            'name' => $user->name,
            'gender' => $user->gender,
            'city' => $user->city,
            'email' => $user->email,
            'birthday' => $user->birthday?->format('Y-m-d'),
            'children' => $user->children,
            'marital_status' => $user->marital_status,
        ];

        OneCRequest::dispatch($endpoint, $data, 'PUT');
    }
}