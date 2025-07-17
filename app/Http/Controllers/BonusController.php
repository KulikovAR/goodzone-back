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
        $user = User::find(Auth::id());

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
            // Проверяем, является ли ошибка связанной с лимитом списания
            if (str_contains($exception->getMessage(), 'Сумма списания превышает максимально допустимую')) {
                return new ApiJsonResponse(
                    400,
                    false,
                    $exception->getMessage()
                );
            }
            
            // Проверяем, является ли ошибка связанной с отсутствием parent_id_sell
            if (str_contains($exception->getMessage(), 'Необходимо указать parent_id_sell')) {
                return new ApiJsonResponse(
                    400,
                    false,
                    $exception->getMessage()
                );
            }
            
            // Проверяем, является ли ошибка связанной с отсутствием исходного чека
            if (str_contains($exception->getMessage(), 'не найден')) {
                return new ApiJsonResponse(
                    400,
                    false,
                    $exception->getMessage()
                );
            }
            
            return new ApiJsonResponse(
                400,
                false,
                'Недостаточно бонусов'
            );
        }

        // Получаем обновленную информацию о бонусах пользователя
        $bonusInfo = $this->bonusService->getBonusInfo($user);
        
        return new ApiJsonResponse(
            message: 'Бонусы списаны',
            data: [
                'debit_amount' => (int)$request->debit_amount,
                'remaining_balance' => (int)$bonusInfo['bonus_amount'],
                'debit_receipt_id' => $request->id_sell,
                'parent_receipt_id' => $request->parent_id_sell
            ]
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

            // Если parent_id_sell отсутствует, это ошибка!
            if (!$refundBonus->parent_id_sell) {
                return new ApiJsonResponse(
                    400,
                    false,
                    'Ошибка возврата: исходный чек не найден или возврат некорректен.'
                );
            }

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
        } catch (\Exception $exception) {
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

        // Получаем обновленную информацию о бонусах пользователя
        $bonusInfo = $this->bonusService->getBonusInfo($user);
        
        return new ApiJsonResponse(
            message: 'Акционные бонусы начислены',
            data: [
                'bonus_amount' => (int)$bonus->amount,
                'expires_at' => $bonus->expires_at?->toISOString(),
                'total_balance' => (int)$bonusInfo['bonus_amount']
            ]
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
