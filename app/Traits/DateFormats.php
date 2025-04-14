<?php

namespace App\Traits;

use App\Enums\PassengerTypeEnum;
use Carbon\Carbon;

trait DateFormats
{
    public function passengerType(string $birthday, ?string $arrivalDate = null): ?string
    {
        $dateEnd = $arrivalDate
            ? Carbon::parse($arrivalDate)
            : Carbon::tomorrow();

        $yearsOld = Carbon::parse($birthday)->diffInYears($dateEnd);

        return match (true) {
            $yearsOld >= 12 => PassengerTypeEnum::ADULT->value,
            ($yearsOld >= 3 && $yearsOld < 12) => PassengerTypeEnum::CHILD->value,
            ($yearsOld < 3) => PassengerTypeEnum::INFANT->value,
            default => null,
        };
    }

    private function formatDateForInput(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        return date('Y-m-d', strtotime($date));
    }

    private function formatDateForOutput(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        return date('d.m.Y', strtotime($date));
    }

    private function formatWithTimezone(?string $date): string
    {
        return Carbon::parse($date)->toISOString();
    }
}
