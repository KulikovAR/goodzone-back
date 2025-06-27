<?php

namespace App\Enums;

enum BonusLevel: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';

    public function getCashbackPercent(): int
    {
        return match($this) {
            self::BRONZE => 5,
            self::SILVER => 10,
            self::GOLD => 30,
        };
    }

    public function getMinPurchaseAmount(): int
    {
        return match($this) {
            self::BRONZE => 0,
            self::SILVER => 10000,
            self::GOLD => 30000,
        };
    }

    public function getNextLevel(): ?self
    {
        return match($this) {
            self::BRONZE => self::SILVER,
            self::SILVER => self::GOLD,
            self::GOLD => null,
        };
    }

    public function getNextLevelMinAmount(): ?int
    {
        return $this->getNextLevel()?->getMinPurchaseAmount();
    }

    public function getProgressToNextLevel(int $currentAmount): float
    {
        $nextLevel = $this->getNextLevel();
        if (!$nextLevel) {
            return 100;
        }

        $currentLevelMin = $this->getMinPurchaseAmount();
        $nextLevelMin = $nextLevel->getMinPurchaseAmount();
        $range = $nextLevelMin - $currentLevelMin;

        if ($range <= 0) {
            return 100;
        }

        $progress = ($currentAmount - $currentLevelMin) / $range * 100;

        return min(max($progress, 0), 100);
    }
}
