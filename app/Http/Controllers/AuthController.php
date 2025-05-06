<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyRequest;
use App\Http\Responses\ApiJsonResponse;
use App\Services\VerificationService;
use App\Models\User;
use App\Services\SmsService;
use App\Services\OneCService;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private SmsService $smsService;

    public function __construct(
        private VerificationService $verificationService,
        SmsService $smsService,
        private OneCService $oneCService
    ) {
        $this->smsService = $smsService;
    }

    public function login(LoginRequest $request): ApiJsonResponse
    {
        $user = User::firstOrCreate(
            ['phone' => $request->phone]
        );

        $code = $this->verificationService->generateCode($request->phone);
        
        $user->code_send_at = now();
        $user->save();
        
        // Here would be SMS service integration
        try {
            // Add timeout for SMS service
            $sessionId = $this->smsService->getSessionId();
            if ($sessionId) {
                $message = "Ваш код верификации: $code";
                $this->smsService->sendSms($sessionId, $request->phone, $message);
            }
        } catch (\Exception $e) {
            // Log the error but don't stop the process
            Log::error('SMS service error: ' . $e->getMessage());
        }

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

        $isFirstVerification = !$user->phone_verified_at;

        if ($request->device_token) {
            $user->device_token = $request->device_token;
        }

        if ($isFirstVerification) {
            $user->phone_verified_at = now();
            
            $platform = $request->header('platform');
            if (in_array($platform, ['android', 'ios'])) {
                $user->come_from_app = true;
            }
        }
        
        $user->save();

        if ($isFirstVerification) {
            $this->oneCService->sendRegister($user);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return new ApiJsonResponse(
            message: 'Вход прошел успешно',
            data: ['token' => $token]
        );
    }
}
