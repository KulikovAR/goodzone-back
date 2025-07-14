<?php

namespace App\Http\Controllers;

use App\Enums\BonusLevel;
use App\Http\Requests\Bonus\CreditRequest;
use App\Http\Requests\Bonus\DebitRequest;
use App\Http\Requests\Bonus\PromotionRequest;
use App\Http\Requests\Bonus\RefundRequest;
use App\Http\Responses\ApiJsonResponse;
use App\Models\User;
use App\Services\BonusService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

class BonusController extends Controller
{
    public function __construct(
        private BonusService $bonusService
    )
    {
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

        try {
        $bonus = $this->bonusService->creditBonus(
            $user,
                $request->purchase_amount,
                $request->id_sell
        );

        return new ApiJsonResponse(
            message: 'Бонусы начислены',
            data: [
                'calculated_bonus_amount' => (int)$bonus->amount,
                'user_level' => $this->bonusService->getUserLevel($user)->value,
                    'cashback_percent' => $this->bonusService->getUserLevel($user)->getCashbackPercent(),
                    'receipt_id' => $bonus->id_sell
            ]
        );
        } catch (Exception $exception) {
            return new ApiJsonResponse(
                400,
                false,
                $exception->getMessage()
            );
        }
    }

    public function debit(DebitRequest $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();

        try {
            $this->bonusService->debitBonus(
                $user,
                $request->debit_amount,
                $request->id_sell,
                $request->parent_id_sell
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

    public function refund(RefundRequest $request): ApiJsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();

        try {
            $refundResult = $this->bonusService->refundBonusByReceipt(
                $user,
                $request->id_sell,
                $request->parent_id_sell,
                $request->refund_amount
            );

            $refundBonus = $refundResult['refund_bonus'];
            $returnedDebitAmount = $refundResult['returned_debit_amount'];

            return new ApiJsonResponse(
                message: 'Бонусы возвращены (возврат товара)',
                data: [
                    'refunded_bonus_amount' => abs((int)$refundBonus->amount),
                    'returned_debit_amount' => (int)$returnedDebitAmount,
                    'refund_receipt_id' => $refundBonus->id_sell,
                    'original_receipt_id' => $refundBonus->parent_id_sell,
                    'refund_amount' => (int)$request->refund_amount
                ]
            );
        } catch (Exception $exception) {
            return new ApiJsonResponse(
                400,
                false,
                $exception->getMessage()
            );
        }
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
