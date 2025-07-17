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
        return new BonusCollection($this->getActivePromotionalBonuses($user));
    }

    public function getBonusInfo(User $user): array
    {
        // Используем чистую сумму покупок из поля пользователя
        $totalPurchaseAmount = $user->purchase_amount ?? 0;

        $promotionalAmount = $this->getActivePromotionalBonuses($user)
            ->sum('amount');

        $currentLevel = $this->getUserLevel($user);

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

    /**
     * Определяет уровень пользователя на основе общей суммы покупок
     */
    public function getUserLevel(User $user): BonusLevel
    {
        // Используем чистую сумму покупок из поля пользователя (уже учитывает возвраты)
        $totalPurchaseAmount = $user->purchase_amount ?? 0;

        $currentLevel = BonusLevel::BRONZE;
        foreach (BonusLevel::cases() as $level) {
            if ($totalPurchaseAmount >= $level->getMinPurchaseAmount()) {
                $currentLevel = $level;
            }
        }

        return $currentLevel;
    }

    /**
     * Рассчитывает количество бонусов для начисления на основе суммы покупки и уровня пользователя
     */
    public function calculateBonusAmount(User $user, float $purchaseAmount): float
    {
        $userLevel = $this->getUserLevel($user);
        $cashbackPercent = $userLevel->getCashbackPercent();
        
        return round($purchaseAmount * $cashbackPercent / 100, 2);
    }

    public function recalculateUserBonus(User $user): void
    {
        $regularBonus = $user->bonuses()
            ->whereIn('type', ['regular', 'refund'])  // включаем возвраты
            ->whereIn('status', ['show-and-calc', 'calc-not-show'])
            ->sum('amount');

        $promotionalBonus = $this->getActivePromotionalBonuses($user)
            ->sum('amount');

        $totalBonus = $regularBonus + $promotionalBonus;

        // Сохраняем реальный баланс, включая отрицательные значения
        $user->update(['bonus_amount' => (int)$totalBonus]);
    }

    public function creditBonus(User $user, float $purchaseAmount, string $idSell): Bonus
    {
        return DB::transaction(function () use ($user, $purchaseAmount, $idSell) {
            // Проверяем, не существует ли уже чек с таким id_sell для этого пользователя
            $existingBonus = Bonus::where('user_id', $user->id)
                ->where('id_sell', $idSell)
                ->first();

            if ($existingBonus) {
                // Возвращаем существующую запись без создания дубликата
                return $existingBonus;
            }

            // Рассчитываем количество бонусов на основе уровня пользователя
            $calculatedAmount = $this->calculateBonusAmount($user, $purchaseAmount);
            
            $bonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => $calculatedAmount,
                'purchase_amount' => $purchaseAmount,
                'type' => 'regular',
                'status' => 'show-and-calc',
                'id_sell' => $idSell
            ]);

            $this->recalculateUserBonus($user);
            $user->addPurchaseAmount($purchaseAmount);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_CREDIT,
                [
                    'amount' => (int)$calculatedAmount,
                    'purchase_amount' => (int)$purchaseAmount,
                    'phone' => $user->phone
                ]
            );

            return $bonus;
        });
    }

    public function debitBonus(User $user, float $amount, string $idSell = null, string $parentIdSell = null): void
    {
        DB::transaction(function () use ($user, $amount, $idSell, $parentIdSell) {
            $availableBonus = $user->bonus_amount;

            // Проверяем что баланс положительный и достаточный для списания
            if ($availableBonus <= 0 || $availableBonus < $amount) {
                throw new Exception('Недостаточно бонусов');
            }

            // Проверяем, что указан parent_id_sell (обязательно для списания)
            if (!$parentIdSell) {
                throw new Exception('Необходимо указать parent_id_sell для списания бонусов');
            }

            // Проверяем лимит списания по чеку (максимум 30% от стоимости чека)
            $originalBonus = Bonus::where('user_id', $user->id)
                ->where('id_sell', $parentIdSell)
                ->where('type', 'regular')
                ->where('amount', '>', 0)
                ->first();

            if (!$originalBonus) {
                throw new Exception("Исходный чек покупки с ID {$parentIdSell} не найден");
            }

            $maxDebitAmount = $originalBonus->purchase_amount * 0.3; // 30% от стоимости чека
            
            if ($amount > $maxDebitAmount) {
                throw new Exception("Сумма списания превышает максимально допустимую (30% от стоимости чека). Максимум: " . (int)$maxDebitAmount . " бонусов");
            }

            $promotionalBonuses = $this->getActivePromotionalBonuses($user);
            $promotionalAmount = $promotionalBonuses->sum('amount');

            // Рассчитываем сколько списать с промо и сколько с обычных
            $promotionalToDebit = min($amount, $promotionalAmount);
            $regularToDebit = $amount - $promotionalToDebit;

            // Списываем промо-бонусы (если есть что списывать)
            if ($promotionalToDebit > 0) {
                $this->debitPromotionalBonuses($user, $promotionalToDebit);
            }

            // Создаем ОДНУ запись списания с правильной привязкой к чеку
            $bonusData = [
                'user_id' => $user->id,
                'amount' => -$amount, // полная сумма списания
                'type' => 'regular',
                'status' => 'show-not-calc',
                'parent_id_sell' => $parentIdSell // всегда указываем parent_id_sell
            ];
            
            // Добавляем id_sell если передан
            if ($idSell) {
                $bonusData['id_sell'] = $idSell;
            }

            Bonus::create($bonusData);

            // Если нужно списать обычные бонусы, создаем техническую запись для баланса
            if ($regularToDebit > 0) {
                Bonus::create([
                    'user_id' => $user->id,
                    'amount' => -$regularToDebit,
                    'type' => 'regular',
                    'status' => 'calc-not-show', // техническая запись, не показывается в истории
                    'parent_id_sell' => $parentIdSell // <--- добавили связь с исходным чеком
                ]);
            }

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
            ->where(function ($query) {
                $query->where('status', 'show-and-calc')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('status', 'show-not-calc')
                            ->where('type', '!=', 'promotional'); // Исключаем списанные акционные бонусы
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return new BonusCollection($bonuses);
    }

    /**
     * Начисляет бонус за заполнение анкеты
     */
    public function creditProfileCompletionBonus(User $user): Bonus
    {
        return DB::transaction(function () use ($user) {
            $amount = 500;
            
            $bonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'regular',
                'status' => 'show-and-calc'
            ]);

            $this->recalculateUserBonus($user);

            $this->pushService->send(
                $user,
                NotificationType::BONUS_PROFILE_COMPLETION,
                [
                    'amount' => (int)$amount,
                    'phone' => $user->phone
                ]
            );

            return $bonus;
        });
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


            if($bonus->status === 'calc-not-show') {
                $bonus->delete();
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

    /**
     * Рассчитывает уровень пользователя на конкретную дату
     */
    public function getUserLevelAtDate(User $user, Carbon $date): BonusLevel
    {
        $totalPurchaseAmount = $user->bonuses()
            ->where('type', 'regular')
            ->where('created_at', '<=', $date)
            ->sum('purchase_amount');

        $currentLevel = BonusLevel::BRONZE;
        foreach (BonusLevel::cases() as $level) {
            if ($totalPurchaseAmount >= $level->getMinPurchaseAmount()) {
                $currentLevel = $level;
            }
        }

        return $currentLevel;
    }

    /**
     * Возврат бонусов по чеку
     * Возвращает массив с информацией о возврате: [refund_bonus, returned_debit_amount]
     */
    public function refundBonusByReceipt(User $user, string $refundReceiptId, string $parentReceiptId, float $refundAmount): array
    {
        return DB::transaction(function () use ($user, $refundReceiptId, $parentReceiptId, $refundAmount) {
            // Проверяем, не был ли уже обработан этот чек возврата
            $existingRefund = Bonus::where('user_id', $user->id)
                ->where('id_sell', $refundReceiptId)
                ->first();

            if ($existingRefund) {
                // Если возврат уже был, проверяем корректность parent_id_sell
                if (!$existingRefund->parent_id_sell || $existingRefund->parent_id_sell !== $parentReceiptId) {
                    throw new \Exception("Возврат уже был, но parent_id_sell не совпадает или отсутствует. Проверьте корректность возврата.");
                }
                $existingDebitRefund = Bonus::where('user_id', $user->id)
                    ->where('id_sell', $refundReceiptId . '_DEBIT_REFUND')
                    ->first();
                return [
                    'refund_bonus' => $existingRefund,
                    'returned_debit_amount' => $existingDebitRefund ? $existingDebitRefund->amount : 0
                ];
            }

            // Ищем исходный чек продажи
            $originalBonus = Bonus::where('user_id', $user->id)
                ->where('id_sell', $parentReceiptId)
                ->where('type', 'regular')
                ->where('amount', '>', 0)
                ->first();

            if (!$originalBonus) {
                throw new \Exception("Исходный чек продажи с ID {$parentReceiptId} не найден для данного пользователя");
            }

            // Рассчитываем уже возвращенную сумму по данному чеку
            $alreadyRefundedAmount = Bonus::where('user_id', $user->id)
                ->where('parent_id_sell', $parentReceiptId)
                ->where('type', 'refund')
                ->sum('purchase_amount'); // сумма возвратов

            $totalRefundAmount = $alreadyRefundedAmount + $refundAmount;
            
            if ($totalRefundAmount > $originalBonus->purchase_amount) {
                throw new Exception("Сумма возврата превышает сумму исходной покупки. Уже возвращено: " . (string)$alreadyRefundedAmount . ", попытка возврата: " . (string)$refundAmount . ", исходная сумма: " . (string)$originalBonus->purchase_amount);
            }

            // ВАЖНО: Уменьшаем общую сумму покупок пользователя
            $user->subtractPurchaseAmount($refundAmount);
            
            // Рассчитываем возврат бонусов по ТЕКУЩЕМУ уровню пользователя (после уменьшения покупок)
            $currentUserLevel = $this->getUserLevel($user);
            $currentCashbackPercent = $currentUserLevel->getCashbackPercent();
            
            // Рассчитываем сумму бонусов к возврату по текущему уровню
            $refundBonusAmount = round($refundAmount * $currentCashbackPercent / 100, 2);

            // *** НОВАЯ ЛОГИКА: Возврат пропорциональной части списанных бонусов ***
            
            // Ищем все записи списания БЕЗ учёта акционных бонусов (только технические записи regular)
            $debitTransactions = Bonus::where('user_id', $user->id)
                ->where('parent_id_sell', $parentReceiptId)
                ->where('amount', '<', 0) // отрицательные суммы = списания
                ->where('status', 'calc-not-show') // берём только технические записи обычных бонусов
                ->get();

            $totalDebitedAmount = 0;
            $refundedDebitAmount = 0;

            if ($debitTransactions->isNotEmpty()) {
                // Считаем общую сумму списанных бонусов по этому чеку
                $totalDebitedAmount = abs($debitTransactions->sum('amount'));
                
                // Рассчитываем пропорцию возврата
                $refundProportion = $refundAmount / $originalBonus->purchase_amount;
                
                // Рассчитываем сумму списанных бонусов к возврату
                $refundedDebitAmount = round($totalDebitedAmount * $refundProportion, 2);
                
                if ($refundedDebitAmount > 0) {
                    // Создаём отдельную запись для возврата списанных бонусов
                    Bonus::create([
                        'user_id' => $user->id,
                        'amount' => $refundedDebitAmount, // положительная сумма - возврат списанных бонусов
                        'type' => 'regular',
                        'status' => 'show-and-calc',
                        'id_sell' => $refundReceiptId . '_DEBIT_REFUND',
                        'parent_id_sell' => $parentReceiptId
                    ]);
                }
            }

            // Создаём запись возврата начисленных бонусов (как и раньше)
            $refundBonus = Bonus::create([
                'user_id' => $user->id,
                'amount' => -$refundBonusAmount,
                'purchase_amount' => $refundAmount, // сумма возврата товара
                'type' => 'refund',
                'status' => 'show-and-calc', // должно влиять на баланс!
                'id_sell' => $refundReceiptId,
                'parent_id_sell' => $parentReceiptId
            ]);

            // Пересчитываем итоговый баланс
            $this->recalculateUserBonus($user);
            
            // Обновляем объект пользователя для получения актуального баланса
            $user->refresh();

            // Уведомление (обновляем с учетом возвращенных списанных бонусов)
            $this->pushService->send(
                $user,
                NotificationType::BONUS_DEBIT,
                [
                    'debit_amount' => (int)$refundBonusAmount,
                    'returned_debit_amount' => (int)$refundedDebitAmount,
                    'remaining_bonus' => (int)$user->bonus_amount,
                    'reason' => 'refund',
                    'receipt_id' => $parentReceiptId,
                    'phone' => $user->phone
                ]
            );

            return [
                'refund_bonus' => $refundBonus,
                'returned_debit_amount' => $refundedDebitAmount
            ];
        });
    }
}
