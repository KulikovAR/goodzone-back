<?php

namespace App\Enums;

enum EventExternalCallbackEnum: string
{
    case NEW = 'new';

    public static function getUrls()
    {
        return [
            self::NEW->value => config('kontur_event.external_callbacks.new'),
        ];
    }
}
