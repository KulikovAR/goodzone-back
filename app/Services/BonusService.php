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
        $availableBonuses = $user->bonuses()
            ->where('expires_at', '>', now())
            ->orWhereNull('expires_at')
            ->sum('amount');

        if ($availableBonuses < $amount) {
            throw new \Exception('Недостаточно бонусов');
        }

        $bonuses = $user->bonuses()
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->orderBy('expires_at', 'asc')
            ->get();

        $remainingDebit = $amount;

        foreach ($bonuses as $bonus) {
            if ($remainingDebit <= 0) break;

            $debitFromBonus = min($bonus->amount, $remainingDebit);
            $bonus->amount -= $debitFromBonus;
            
            if ($bonus->amount > 0) {
                $bonus->save();
            } else {
                $bonus->delete();
            }

            $remainingDebit -= $debitFromBonus;
        }

        $remainingBonus = $user->bonuses()
            ->where('expires_at', '>', now())
            ->orWhereNull('expires_at')
            ->sum('amount');

        $this->pushService->send(
            $user,
            NotificationType::BONUS_DEBIT,
            [
                'debit_amount' => $amount,
                'remaining_bonus' => $remainingBonus,
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