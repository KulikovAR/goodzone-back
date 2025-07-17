<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterFromOneCRequest;
use App\Http\Requests\OneC\ClientInfoRequest;
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

    /**
     * Получить информацию о клиентах по номерам телефонов
     */
    public function getClientInfo(ClientInfoRequest $request): ApiJsonResponse
    {
        try {
            $phones = $request->phones;
            $clients = [];

            foreach ($phones as $phone) {
                $user = User::where('phone', $phone)->first();

                if ($user) {
                    // Пользователь найден - получаем информацию о бонусах
                    $bonusInfo = $this->bonusService->getBonusInfo($user);
                    
                    $clients[] = [
                        'phone' => $phone,
                        'is_registered' => true,
                        'cashback_percent' => $bonusInfo['cashback_percent'],
                        'bonus_amount' => $bonusInfo['bonus_amount'],
                        'level' => $bonusInfo['level'],
                        'total_purchase_amount' => $bonusInfo['total_purchase_amount'],
                    ];
                } else {
                    // Пользователь не найден
                    $clients[] = [
                        'phone' => $phone,
                        'is_registered' => false,
                        'cashback_percent' => null,
                        'bonus_amount' => null,
                        'level' => null,
                        'total_purchase_amount' => null,
                    ];
                }
            }

            return new ApiJsonResponse(
                message: 'Информация о клиентах получена',
                data: [
                    'clients' => $clients,
                    'total_count' => count($phones),
                    'registered_count' => collect($clients)->where('is_registered', true)->count(),
                    'unregistered_count' => collect($clients)->where('is_registered', false)->count(),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Failed to get client info from 1C', [
                'phones' => $request->phones,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new ApiJsonResponse(
                httpCode: 500,
                ok: false,
                message: 'Ошибка при получении информации о клиентах: ' . $e->getMessage()
            );
        }
    }
} 