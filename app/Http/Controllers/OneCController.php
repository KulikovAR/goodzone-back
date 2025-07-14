<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterFromOneCRequest;
use App\Http\Responses\ApiJsonResponse;
use App\Models\User;
use App\Services\BonusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OneCController extends Controller
{
    public function __construct(
        private BonusService $bonusService
    ) {}

    public function register(RegisterFromOneCRequest $request): ApiJsonResponse
    {
        try {
            DB::beginTransaction();
            
            // Создаем пользователя с данными из 1С
            $user = User::create([
                'phone' => $request->phone,
                'name' => $request->name,
                'gender' => $request->gender,
                'city' => $request->city,
                'email' => $request->email,
                'birthday' => $request->birthday,
                'phone_verified_at' => now(), // Сразу считаем телефон подтвержденным
                'come_from_app' => false, // Регистрация не из мобильного приложения
                'profile_completed_bonus_given' => false, // Бонус за заполнение профиля НЕ начислен
                'role' => \App\Enums\UserRole::USER,
            ]);

            // НЕ начисляем бонус автоматически при регистрации из 1С
            // Пользователь получит бонус только когда дозаполнит children и marital_status в приложении

            DB::commit();

            Log::info('User registered from 1C', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'profile_completed_bonus' => false, // всегда false при регистрации из 1С
                'profile_completed' => $user->isProfileCompleted()
            ]);

            return new ApiJsonResponse(
                message: 'Пользователь успешно зарегистрирован',
                data: [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'bonus_awarded' => false // всегда false при регистрации из 1С
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to register user from 1C', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new ApiJsonResponse(
                httpCode: 500,
                ok: false,
                message: 'Ошибка при регистрации пользователя: ' . $e->getMessage()
            );
        }
    }
} 