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
use App\Http\Controllers\Traits\HandlesBatchOperations;

class BonusController extends Controller
{
    use HandlesBatchOperations;

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
        $results = $this->processBatchOperations(
            $request->all(),
            function ($operation) {
                $user = User::where('phone', $operation['phone'])->firstOrFail();
                $bonus = $this->bonusService->creditBonus(
                    $user,
                    $operation['purchase_amount'],
                    $operation['id_sell']
                );
                return [
                    'calculated_bonus_amount' => (int)$bonus->amount,
                    'user_level' => $this->bonusService->getUserLevel($user)->value,
                    'cashback_percent' => $this->bonusService->getUserLevel($user)->getCashbackPercent()
                ];
            }
        );

        return new ApiJsonResponse(
            message: 'Бонусы начислены',
            data: $results
        );
    }

    public function debit(DebitRequest $request): ApiJsonResponse
    {
        $results = $this->processBatchOperations(
            $request->all(),
            function ($operation) {
                $user = User::where('phone', $operation['phone'])->firstOrFail();
                $this->bonusService->debitBonus(
                    $user,
                    $operation['debit_amount'],
                    $operation['id_sell'],
                    $operation['parent_id_sell']
                );
                return null;
            }
        );

        return new ApiJsonResponse(
            message: 'Бонусы списаны',
            data: $results
        );
    }

    public function refund(RefundRequest $request): ApiJsonResponse
    {
        $results = $this->processBatchOperations(
            $request->all(),
            function ($operation) {
                $user = User::where('phone', $operation['phone'])->firstOrFail();
                $refundResult = $this->bonusService->refundBonusByReceipt(
                    $user,
                    $operation['id_sell'],
                    $operation['parent_id_sell'],
                    $operation['refund_amount']
                );
                return [
                    'refunded_bonus_amount' => abs((int)$refundResult['refund_bonus']->amount),
                    'returned_debit_amount' => (int)$refundResult['returned_debit_amount'],
                    'refund_receipt_id' => $refundResult['refund_bonus']->id_sell,
                    'original_receipt_id' => $refundResult['refund_bonus']->parent_id_sell,
                    'refund_amount' => (int)$operation['refund_amount']
                ];
            }
        );

        return new ApiJsonResponse(
            message: 'Бонусы возвращены (возврат товара)',
            data: $results
        );
    }

    public function promotion(PromotionRequest $request): ApiJsonResponse
    {
        $results = $this->processBatchOperations(
            $request->all(),
            function ($operation) {
                $user = User::where('phone', $operation['phone'])->firstOrFail();
                $bonus = $this->bonusService->creditPromotionalBonus(
                    $user,
                    $operation['bonus_amount'],
                    Carbon::parse($operation['expiry_date'])
                );
                return null;
            }
        );

        return new ApiJsonResponse(
            message: 'Акционные бонусы начислены',
            data: $results
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
