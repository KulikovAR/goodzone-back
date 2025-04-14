<?php

namespace App\Http\Controllers;

use App\Contracts\AuthServiceContract;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\VerifyRequest;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthServiceContract $service) {}

    public function login(LoginRequest $request): ApiJsonResponse
    {
        return $this->service->login($request);
    }

    public function verify(VerifyRequest $request): ApiJsonResponse
    {
        return $this->service->verify($request);
    }

    public function refresh(RefreshRequest $request): ApiJsonResponse
    {
        return $this->service->refresh($request);
    }

    public function logout(Request $request): ApiJsonResponse
    {
        $this->service->logout($request->user());

        return new ApiJsonResponse;
    }
}
