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
use Exception;

class BonusController extends Controller
{
    public function __construct(
        private BonusService $bonusService
    )
    {
    }

    public function info(): ApiJsonResponse
    {
        $user = auth()->user();
        
        $bonusInfo = $this->bonusService->getBonusInfo($user);

        return new ApiJsonResponse(
            data: $bonusInfo
        );
    }

    public function credit(CreditRequest $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();

        $this->bonusService->creditBonus(
            $user,
            $request->bonus_amount,
            $request->purchase_amount
        );

        return new ApiJsonResponse(
            message: 'Бонусы начислены'
        );
    }

    public function debit(DebitRequest $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();

        try {
            $this->bonusService->debitBonus(
                $user,
                $request->debit_amount
            );
        }
        catch (Exception $exception) {
            return new ApiJsonResponse(
                400,
                false,
                'Недостаточно бонусов'
            );
        }


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

        return new ApiJsonResponse(
            message: 'Акционные бонусы начислены'
        );
    }
}
