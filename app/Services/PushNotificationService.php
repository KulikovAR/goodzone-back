<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging;

class PushNotificationService
{
    public function __construct(
        private readonly Messaging $messaging
    ) {}

    public function send(User $user, NotificationType $type, array $data = []): void
    {
        if (!$user->device_token) {
            return;
        }

        $message = CloudMessage::fromArray([
            'token' => $user->device_token,
            'notification' => [
                'title' => $this->getTitle($type),
                'body' => $this->getMessage($type, $data)
            ],
            'data' => $data
        ]);

        $this->messaging->send($message);
    }

    private function getMessage(NotificationType $type, array $data): string
    {
        return match($type) {
            NotificationType::BONUS_CREDIT => "Вам начислено {$data['amount']} бонусов за покупку на сумму {$data['purchase_amount']}",
            NotificationType::BONUS_DEBIT => "Списано {$data['debit_amount']} бонусов. Остаток: {$data['remaining_bonus']}",
            NotificationType::BONUS_PROMOTION => "Вам начислено {$data['bonus_amount']} акционных бонусов. Действует до {$data['expiry_date']}",
        };
    }

    private function getTitle(NotificationType $type): string
    {
        return match($type) {
            NotificationType::BONUS_CREDIT => 'Начисление бонусов',
            NotificationType::BONUS_DEBIT => 'Списание бонусов',
            NotificationType::BONUS_PROMOTION => 'Акционные бонусы',
        };
    }
}