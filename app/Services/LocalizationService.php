<?php

namespace App\Services;

class LocalizationService
{
    public static function supportedLanguages(): array
    {
        return array_keys(config('localization.languages'));
    }
}
