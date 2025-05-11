<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\User;
use Carbon\Carbon;
use App\Enums\NotificationType;
use App\Enums\BonusLevel;
use Illuminate\Support\Facades\DB;

class BonusService
{
    public function __construct(
        private readonly PushNotificationService $pushService
    ) {}

    public function getBonusInfo(User $user): array
    {
        $totalPurchaseAmount = $user->bonuses()
            ->where('type', 'regular')
            ->sum('purchase_amount');

        $currentLevel = BonusLevel::BRONZE;
        foreach (BonusLevel::cases() as $level) {
            if ($totalPurchaseAmount >= $level->getMinPurchaseAmount()) {
                $currentLevel = $level;
            }
        }

        return [
            'bonus_amount' => $user->bonus_amount,
            'level' => $currentLevel->value,
            'cashback_percent' => $currentLevel->getCashbackPercent(),
            'total_purchase_amount' => $totalPurchaseAmount,
            'next_level' => $currentLevel->getNextLevel()?->value,
            'next_level_min_amount' => $currentLevel->getNextLevelMinAmount(),
            'progress_to_next_level' => $currentLevel->getProgressToNextLevel($totalPurchaseAmount),
        ];
    }

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

            $user->addPurchaseAmount($purchaseAmount);

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

    public function getBonusHistory(User $user): array
    {
        $bonuses = $user->bonuses()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Bonus $bonus) {
                return [
                    'id' => $bonus->id,
                    'amount' => $bonus->amount,
                    'type' => $bonus->type,
                    'purchase_amount' => $bonus->purchase_amount,
                    'expires_at' => $bonus->expires_at?->format('Y-m-d\TH:i:s'),
                    'created_at' => $bonus->created_at->format('Y-m-d\TH:i:s'),
                ];
            });

        return [
            'history' => $bonuses,
            'total_count' => $bonuses->count(),
        ];
    }
}
