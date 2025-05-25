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
                $message = match ($this->type) {
                    NotificationType::BONUS_CREDIT => ExpoMessage::create('Начисление бонусов')
                        ->body("Вам начислено {$this->data['amount']} бонусов за покупку на сумму {$this->data['purchase_amount']}"),
                    NotificationType::BONUS_DEBIT => ExpoMessage::create('Списание бонусов')
                        ->body("С вашего счета списано {$this->data['debit_amount']} бонусов. Остаток: {$this->data['remaining_bonus']}"),
                    NotificationType::BONUS_PROMOTION => ExpoMessage::create('Промо-бонусы')
                        ->body("Вам начислено {$this->data['bonus_amount']} промо-бонусов. Действуют до " . 
                            Carbon::parse($this->data['expiry_date'])->format('d.m.Y H:i')),
                    default => ExpoMessage::create('Уведомление')
                        ->body('Новое уведомление')
                };

                return $message
                    ->data($this->data)
                    ->priority('high')
                    ->playSound();
            }
        };
    }
}