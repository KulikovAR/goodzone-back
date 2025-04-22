<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bonus\CreditRequest;
use App\Http\Requests\Bonus\DebitRequest;
use App\Http\Requests\Bonus\PromotionRequest;
use App\Http\Responses\ApiJsonResponse;
use App\Models\User;
use App\Services\BonusService;
use App\Services\PushNotificationService;
use Carbon\Carbon;

class BonusController extends Controller
{
    public function __construct(
        private BonusService $bonusService,
        private PushNotificationService $pushService
    ) {}

    public function credit(CreditRequest $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();
        
        $bonus = $this->bonusService->creditBonus(
            $user,
            $request->bonus_amount,
            $request->purchase_amount
        );

        $this->pushService->send(
            $user,
            "Вам начислено {$request->bonus_amount} бонусов за покупку"
        );

        return new ApiJsonResponse(
            message: 'Бонусы начислены'
        );
    }

    public function debit(DebitRequest $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();
        
        $this->bonusService->debitBonus(
            $user,
            $request->debit_amount
        );

        $this->pushService->send(
            $user,
            "Списано {$request->debit_amount} бонусов. Остаток: {$request->remaining_bonus}"
        );

        return new ApiJsonResponse(
            message: 'Бонусы списаны'
        );
    }

    public function promotion(PromotionRequest $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();
        
        $bonus = $this->bonusService->creditPromotionalBonus(
            $user,
            $request->bonus_amount,
            Carbon::parse($request->expiry_date)
        );

        $this->pushService->send(
            $user,
            "Вам начислено {$request->bonus_amount} акционных бонусов"
        );

        return new ApiJsonResponse(
            message: 'Акционные бонусы начислены'
        );
    }
}