<?php

namespace App\Services;

use App\Enums\BonusLevel;
use App\Enums\NotificationType;
use App\Http\Resources\BonusCollection;
use App\Models\Bonus;
use App\Models\User;
use Carbon\Carbon;
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
        return new BonusCollection($this->getPromotionalBonuses($user));
    }

    public function getBonusInfo(User $user): array
    {
        $totalPurchaseAmount = $user->bonuses()
            ->where('type', 'regular')
            ->sum('purchase_amount');

        $promotionalAmount = $this->getPromotionalBonuses($user)
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
        $totalBonus = $user->bonuses()
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->where('used', false)
            ->sum('amount');

        if($totalBonus < 0 ) {
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
                'type' => 'regular'
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
            if ($user->bonus_amount + $this->getPromotionalBonuses($user)
                    ->sum('amount')  < $amount) {
                throw new \Exception('Недостаточно бонусов');
            }

            Bonus::create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => 'regular'
            ]);

            $this->recalculateUserBonus($user);

            $amount = $this->debitPromotional($user, $amount);

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
                'expires_at' => $expiryDate
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
            ->orderBy('created_at', 'desc')
            ->where('service', false)
            ->get();

        return new BonusCollection($bonuses);
    }


    private function debitPromotional(User $user, int $amount)
    {
        $promotionalBonuses = $this->getPromotionalBonuses($user);
        foreach ($promotionalBonuses as $promotionalBonus) {
            $promotionalBonusAmount = (int)$promotionalBonus->amount;
            if ($amount === 0) {
                break;
            }

            $promotionalBonus->setUsed();

            if ($promotionalBonus->amount >= $amount) {
                $this->createServiceWithNewAmount($promotionalBonus, $promotionalBonusAmount - $amount);
                $promotionalBonus->setUsed();
                $amount = 0;
            } else {
                $amount -= $promotionalBonusAmount;
            }
        }

        return $amount;
    }

    private function createServiceWithNewAmount(Bonus $oldBonus, int $newAmount): void
    {
        Bonus::create([
            'user_id' => $oldBonus->user_id,
            'amount' => $newAmount,
            'type' => $oldBonus->type,
            'expires_at' => $oldBonus->expires_at,
            'service' => true
        ]);
    }

    private function getPromotionalBonuses(User $user): Collection
    {
        return $user->bonuses()
            ->where('type', 'promotional')
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->where('used', false)
            ->orderBy('expires_at')
            ->get();
    }
}
