<?php

namespace App\Services;

use App\Models\User;

class PushNotificationService
{
    public function send(User $user, string $message): void
    {
        // Здесь будет реализация отправки push-уведомлений
    }
}