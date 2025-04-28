<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\User;
use Carbon\Carbon;
use App\Enums\NotificationType;

class BonusService
{
    public function __construct(
        private readonly PushNotificationService $pushService
    ) {}

    public function creditBonus(User $user, float $amount, float $purchaseAmount): Bonus
    {
        $bonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'purchase_amount' => $purchaseAmount,
            'type' => 'regular'
        ]);

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
    }

    public function debitBonus(User $user, float $amount): void
    {
        // Fix 1: Properly group expiration conditions
        $availableBonuses = $user->bonuses()
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->sum('amount');

        if ($availableBonuses < $amount) {
            throw new \Exception('Недостаточно бонусов');
        }

        // Fix 2: Use fresh query with lock
        $bonuses = $user->bonuses()
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->orderBy('expires_at', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingDebit = $amount;
        $totalDeducted = 0;

        foreach ($bonuses as $bonus) {
            if ($remainingDebit <= 0) break;

            $deduct = min($bonus->amount, $remainingDebit);
            $bonus->amount -= $deduct;
            $totalDeducted += $deduct;
            
            if ($bonus->amount > 0) {
                $bonus->save();
            } else {
                $bonus->delete();
            }

            $remainingDebit -= $deduct;
        }

        // Fix 3: Calculate remaining from original available balance minus total deducted
        $remainingBonus = $availableBonuses - $totalDeducted;

        $this->pushService->send(
            $user,
            NotificationType::BONUS_DEBIT,
            [
                'debit_amount' => $totalDeducted,  // Use actual deducted amount
                'remaining_bonus' => number_format($remainingBonus, 2, '.', ''),
                'phone' => $user->phone
            ]
        );
    }

    public function creditPromotionalBonus(User $user, float $amount, Carbon $expiryDate): Bonus
    {
        $bonus = Bonus::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => 'promotional',
            'expires_at' => $expiryDate
        ]);

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
    }
}