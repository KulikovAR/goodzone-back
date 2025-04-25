<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyRequest;
use App\Http\Responses\ApiJsonResponse;
use App\Services\VerificationService;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct(
        private VerificationService $verificationService
    ) {}

    public function login(LoginRequest $request): ApiJsonResponse
    {
        $user = User::firstOrCreate(
            ['phone' => $request->phone]
        );

        $code = $this->verificationService->generateCode($request->phone);
        
        $user->code_send_at = now();
        $user->save();
        
        // Here would be SMS service integration
        
        return new ApiJsonResponse(
            message: 'Код отправлен на телефон'
        );
    }

    public function verify(VerifyRequest $request): ApiJsonResponse
    {
        $isValid = $this->verificationService->verifyCode(
            $request->phone,
            $request->code
        );

        if (!$isValid) {
            return new ApiJsonResponse(
                httpCode: 422,
                ok: false,
                message: 'Неверный код'
            );
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return new ApiJsonResponse(
                httpCode: 404,
                ok: false,
                message: 'Пользователь не найден'
            );
        }

        if ($request->device_token) {
            $user->device_token = $request->device_token;
        }

        if (!$user->phone_verified_at) {
            $user->phone_verified_at = now();
        }
        
        $user->save();

        $token = $user->createToken('auth-token')->plainTextToken;

        return new ApiJsonResponse(
            message: 'Вход прошел успешно',
            data: ['token' => $token]
        );
    }
}
