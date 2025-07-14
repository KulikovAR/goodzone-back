<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateRequest;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\OneCService;
use App\Services\BonusService;

class UserController extends Controller
{
    public function __construct(
        private OneCService $oneCService,
        private BonusService $bonusService
    ) {}

    public function show(): ApiJsonResponse
    {
        $user = Auth::user();

        return new ApiJsonResponse(
            message: 'Данные пользователя получены',
            data: $user
        );
    }

    public function update(UpdateRequest $request): ApiJsonResponse
    {
        $user = Auth::user();
        $wasProfileCompleted = $user->isProfileCompleted();
        
        $data = collect($request->validated())
            ->filter(fn ($value, $field) => $value !== $user->{$field})
            ->toArray();

        if (!empty($data)) {
            $user->update($data);
            $user->refresh(); // Обновляем объект после изменений
            
            $this->oneCService->updateUser($user);
            
            // Проверяем, заполнен ли теперь профиль и не начислялись ли бонусы ранее
            if (!$wasProfileCompleted && 
                $user->isProfileCompleted() && 
                !$user->profile_completed_bonus_given) {
                
                // Начисляем бонусы за заполнение анкеты
                $this->bonusService->creditProfileCompletionBonus($user);
                
                // Отмечаем, что бонус выдан
                $user->update(['profile_completed_bonus_given' => true]);
            }
        }

        return new ApiJsonResponse(
            message: 'Пользователь успешно обновлен'
        );
    }
}