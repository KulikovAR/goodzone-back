<?php

namespace App\Services;

use App\Notifications\AdminNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public static function notifyAdmin(string $message)
    {
        Notification::route('mail', [
            config('site-values.email_support.email_support') => 'Admin',
        ])->notify(new AdminNotification($message));
    }
}
