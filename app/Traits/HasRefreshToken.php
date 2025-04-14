<?php

namespace App\Traits;

use App\Enums\TokenEnum;
use Illuminate\Support\Carbon;

trait HasRefreshToken
{
    public function createAccessToken()
    {
        return $this->createToken('access_token', [TokenEnum::ACCESS->value], Carbon::now()->addMinutes(config('auth.access_token_expires')))->plainTextToken;
    }

    public function createRefreshToken()
    {
        return $this->createToken('refresh_token', [TokenEnum::REFRESH->value], Carbon::now()->addMinutes(config('auth.refresh_token_expires')))->plainTextToken;
    }
}
