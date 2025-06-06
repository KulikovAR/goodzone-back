<?php

namespace App\Services;

use App\Enums\BonusLevel;
use App\Enums\NotificationType;
use App\Http\Resources\BonusCollection;
use App\Models\Bonus;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BonusService
{
    public function __construct(
        private readonly ExpoNotificationService $pushService
    )
    {
    }

    public function getPromotional(User $user): BonusCollection
    {
        return new BonusCollection($this->getHistoryPromotionalBonuses($user));
    }

    public function getBonusInfo(User $user): array
    {
        $totalPurchaseAmount = $user->bonuses()
            ->where('type', 'regular')
            ->sum('purchase_amount');

        $promotionalAmount = $this->getActivePromotionalBonuses($user)
            ->sum('amount');

        $currentLevel = BonusLevel::BRONZE;
        foreach (BonusLevel::cases() as $level) {
            if ($totalPurchaseAmount >= $level->getMinPurchaseAmount()) {
                $currentLevel = $level;
            }
        }

        return [
            'bonus_amount' => (int)$user->bonus_amount,
            'bonus_amount_without' => (int)$user->bonus_amount - (int)$promotionalAmount,
            'promotional_bonus_amount' => (int)$promotionalAmount,
            'level' => $currentLevel->value,
            'cashback_percent' => $currentLevel->getCashbackPercent(),
            'total_purchase_amount' => (int)$totalPurchaseAmount,
            'next_level' => $currentLevel->getNextLevel()?->value,
            'next_level_min_amount' => $currentLevel->getNextLevelMinAmount(),
            'progress_to_next_level' => $currentLevel->getProgressToNextLevel($totalPurchaseAmount),
        ];
    }

    public function recalculateUserBonus(User $user): void
    {
        $regularBonus = $user->bonuses()
            ->where('type', 'regular')
            ->whereIn('status', ['show-and-calc', 'calc-not-show'])
            ->sum('amount');

        $promotionalBonus = $this->getActivePromotionalBonuses($user)
            ->sum('amount');

        $totalBonus = $regularBonus + $promotionalBonus;

        if ($totalBonus < 0) {
            $totalBonus = 0;
        }

        $user->update(['bonus_amount' => (int)$totalBonus]);
    }

    public function creditBonus(User $user, float $amount, float $purchaseAmount): Bonus
    {
        return DB::transaction(function () use ($user, $amount, $purchaseAmount) {
            $bonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'purchase_amount' => $purchaseAmount,
                'type' => 'regular',
                'status' => 'show-and-calc'
            ]);

            $this->recalculateUserBonus($user);

            $user->addPurchaseAmount($purchaseAmount);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_CREDIT,
                [
                    'amount' => (int)$amount,
                    'purchase_amount' => (int)$purchaseAmount,
                    'phone' => $user->phone
                ]
            );

            return $bonus;
        });
    }

    public function debitBonus(User $user, float $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            $availableBonus = $user->bonus_amount;

            if ($availableBonus < $amount) {
                throw new Exception('Недостаточно бонусов');
            }

            $promotionalBonuses = $this->getActivePromotionalBonuses($user);

            $promotionalAmount = $promotionalBonuses->sum('amount');

            if ($promotionalBonuses->count() > 0) {
                $this->debitPromotionalBonuses($user, $amount);
            }

            $remainingAmount = $amount - $promotionalAmount;

            if ($remainingAmount > 0) {
                $this->debitRegularBonuses($user, $remainingAmount);
            }

            Bonus::create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => 'regular',
                'status' => 'show-not-calc'
            ]);


            $this->recalculateUserBonus($user);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_DEBIT,
                [
                    'debit_amount' => (int)$amount,
                    'remaining_bonus' => (int)$user->bonus_amount,
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
                'expires_at' => $expiryDate,
                'status' => 'show-and-calc'
            ]);

            $this->recalculateUserBonus($user);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_PROMOTION,
                [
                    'bonus_amount' => (int)$amount,
                    'expiry_date' => $expiryDate->format('d.m.Y H:i'),
                    'phone' => $user->phone
                ]
            );

            return $bonus;
        });
    }

    public function getBonusHistory(User $user): BonusCollection
    {
        $bonuses = $user->bonuses()
            ->whereIn('status', ['show-and-calc', 'show-not-calc'])
            ->orderBy('created_at', 'desc')
            ->get();

        return new BonusCollection($bonuses);
    }

    private function debitPromotionalBonuses(User $user, float $remainingAmount): void
    {
        $promotionalBonuses = $this->getActivePromotionalBonuses($user);

        foreach ($promotionalBonuses as $bonus) {
            if ($remainingAmount <= 0) {
                break;
            }

            $debitAmount = min($remainingAmount, $bonus->amount);

            if ($debitAmount < $bonus->amount) {
                Bonus::create([
                    'user_id' => $user->id,
                    'amount' => $bonus->amount - $debitAmount,
                    'type' => 'promotional',
                    'expires_at' => $bonus->expires_at,
                    'status' => 'calc-not-show'
                ]);
            }

            $bonus->update(['status' => 'show-not-calc']);
            $remainingAmount -= $debitAmount;
        }
    }

    private function debitRegularBonuses(User $user, float $amount): void
    {
        Bonus::create([
            'user_id' => $user->id,
            'amount' => -$amount,
            'type' => 'regular',
            'status' => 'calc-not-show'
        ]);
    }

    private function getActivePromotionalBonuses(User $user): Collection
    {
        return $user->bonuses()
            ->where('type', 'promotional')
            ->whereIn('status', ['show-and-calc', 'calc-not-show'])
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->orderBy('expires_at')
            ->get();
    }

    private function getHistoryPromotionalBonuses(User $user): Collection
    {
        return $user->bonuses()
            ->where('type', 'promotional')
            ->whereIn('status', ['show-and-calc', 'show-not-calc'])
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->orderBy('expires_at')
            ->get();
    }
}
