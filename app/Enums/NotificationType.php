<?php

namespace App\Enums;

enum NotificationType: string
{
    case BONUS_CREDIT = 'bonus_credit';
    case BONUS_DEBIT = 'bonus_debit';
    case BONUS_PROMOTION = 'bonus_promotion';
    case BONUS_PROFILE_COMPLETION = 'bonus_profile_completion';
}