<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\VerifyRequest;
use App\Http\Responses\ApiJsonResponse;
use App\Models\User;

interface AuthServiceContract
{
    public function login(LoginRequest $request): ApiJsonResponse;

    public function verify(VerifyRequest $request): ApiJsonResponse;

    public function refresh(RefreshRequest $request): ApiJsonResponse;

    public function logout(User $user): void;

    public function clearVerificationCodes(): void;
}
