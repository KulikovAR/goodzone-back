<?php

namespace App\Http\Controllers;

use App\Enums\BonusLevel;
use App\Http\Requests\Bonus\CreditRequest;
use App\Http\Requests\Bonus\DebitRequest;
use App\Http\Requests\Bonus\PromotionRequest;
use App\Http\Responses\ApiJsonResponse;
use App\Models\User;
use App\Services\BonusService;
use Carbon\Carbon;
use Exception;
use http\Env\Request;
use Illuminate\Support\Facades\Auth;

class BonusController extends Controller
{
    public function __construct(
        private BonusService $bonusService
    )
    {
    }

    public function infoIntegration(Request $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();

        $bonusInfo = $this->bonusService->getBonusInfo($user);

        return new ApiJsonResponse(
            data: $bonusInfo
        );
    }

    public function info(): ApiJsonResponse
    {
        $user = Auth::user();

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
        } catch (Exception $exception) {
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

    public function history(): ApiJsonResponse
    {
        $user = Auth::user();

        $history = $this->bonusService->getBonusHistory($user);

        return new ApiJsonResponse(
            data: $history
        );
    }


    public function promotionalHistory(): ApiJsonResponse
    {
        $user = Auth::user();

        $history = $this->bonusService->getPromotional($user);

        return new ApiJsonResponse(
            data: $history
        );
    }

    public function levels(): ApiJsonResponse
    {
        $levels = collect(BonusLevel::cases())->map(function (BonusLevel $level) {
            return [
                'name' => $level->value,
                'cashback_percent' => $level->getCashbackPercent(),
                'min_purchase_amount' => $level->getMinPurchaseAmount(),
            ];
        });

        return new ApiJsonResponse(
            data: $levels
        );
    }
}
