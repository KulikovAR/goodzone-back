<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;

trait BearerTokenTrait
{
    protected function createBearerCookie(string $token)
    {
        return Cookie::make('bearer_token', $token, httpOnly: false);
    }

    protected function createTemporaryAuthToken(User $user, ?string $tokenName = null, ?\DateTime $expiresAt = null): string
    {
        if (empty($expiresAt)) {
            $expiresAt = Carbon::now()->addMinutes(15)->toDateTime();
        }

        return $this->createAuthToken($user, $tokenName, $expiresAt);
    }

    protected function createAuthToken(User $user, ?string $tokenName = null, ?\DateTime $expiresAt = null): string
    {
        if (empty($tokenName)) {
            $tokenName = 'spa';
        }

        return $user->createToken($tokenName, expiresAt: $expiresAt)->plainTextToken;
    }
}
