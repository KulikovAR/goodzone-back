<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\CheckRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\VerificationService;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private VerificationService $verificationService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $code = $this->verificationService->generateCode($request->phone);
        
        // Here would be SMS service integration
        
        return response()->json([
            'status' => 'success',
            'message' => 'Код отправлен на телефон'
        ]);
    }

    public function check(CheckRequest $request): JsonResponse
    {
        $isValid = $this->verificationService->verifyCode(
            $request->phone,
            $request->code
        );

        if (!$isValid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Неверный код'
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Вход прошел успешно'
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        // Here would be 1C integration
        
        return response()->json([
            'status' => 'success',
            'message' => 'Регистрация успешна'
        ]);
    }
}
