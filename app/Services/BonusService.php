<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\User;
use Carbon\Carbon;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\DB;

class BonusService
{
    public function __construct(
        private readonly PushNotificationService $pushService
    ) {}

    public function recalculateUserBonus(User $user): void
    {
        $totalBonus = $user->bonuses()
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->sum('amount');

        $user->update(['bonus_amount' => $totalBonus]);
    }

    public function creditBonus(User $user, float $amount, float $purchaseAmount): Bonus
    {
        return DB::transaction(function () use ($user, $amount, $purchaseAmount) {
            $bonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'purchase_amount' => $purchaseAmount,
                'type' => 'regular'
            ]);

            $this->recalculateUserBonus($user);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_CREDIT,
                [
                    'amount' => $amount,
                    'purchase_amount' => $purchaseAmount,
                    'phone' => $user->phone
                ]
            );

            return $bonus;
        });
    }

    public function debitBonus(User $user, float $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            if ($user->bonus_amount < $amount) {
                throw new \Exception('Недостаточно бонусов');
            }

            Bonus::create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => 'regular'
            ]);

            $this->recalculateUserBonus($user);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_DEBIT,
                [
                    'debit_amount' => $amount,
                    'remaining_bonus' => number_format($user->bonus_amount, 2, '.', ''),
                    'phone' => $user->phone
                ]
            );
        });
    }

    public function creditPromotionalBonus(User $user, float $amount, Carbon $expiryDate): Bonus
    {
        return DB::transaction(function () use ($user, $amount, $expiryDate) {
            $bonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'promotional',
                'expires_at' => $expiryDate
            ]);

            $this->recalculateUserBonus($user);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_PROMOTION,
                [
                    'bonus_amount' => $amount,
                    'expiry_date' => $expiryDate->format('Y-m-d\TH:i:s'),
                    'phone' => $user->phone
                ]
            );

            return $bonus;
        });
    }
}
