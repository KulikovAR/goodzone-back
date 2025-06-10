<?php

namespace App\Services;

use App\Models\User;
use App\Enums\NotificationType;
use NotificationChannels\Expo\ExpoMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class ExpoNotificationService
{
    public function send(User $user, NotificationType $type, array $data): void
    {
        if ($user->deviceTokens->isEmpty()) {
            return;
        }

        $notification = $this->createNotification($type, $data);
        $user->notify($notification);
    }

    private function createNotification(NotificationType $type, array $data): Notification
    {
        return new class($type, $data) extends Notification
        {
            public function __construct(
                private readonly NotificationType $type,
                private readonly array $data
            ) {}

            public function via($notifiable): array
            {
                return ['expo'];
            }

            public function toExpo($notifiable): ExpoMessage
            {
                return ExpoMessage::create()
                    ->title($this->getTitle())
                    ->body($this->getMessage())
                    ->data($this->data);
            }

            private function getTitle(): string
            {
                return match($this->type) {
                    NotificationType::BONUS_CREDIT => 'Начисление бонусов',
                    NotificationType::BONUS_DEBIT => 'Списание бонусов',
                    NotificationType::BONUS_PROMOTION => 'Акционные бонусы',
                };
            }

            private function getMessage(): string
            {
                return match($this->type) {
                    NotificationType::BONUS_CREDIT => "Вам начислено {$this->data['amount']} бонусов за покупку на сумму {$this->data['purchase_amount']}",
                    NotificationType::BONUS_DEBIT => "Списано {$this->data['debit_amount']} бонусов. Остаток: {$this->data['remaining_bonus']}",
                    NotificationType::BONUS_PROMOTION => "Вам начислено {$this->data['bonus_amount']} акционных бонусов. Действует до {$this->data['expiry_date']}",
                };
            }
        };
    }
}