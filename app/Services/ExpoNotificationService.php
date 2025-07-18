<?php

namespace App\Services;

use App\Models\User;
use App\Models\PushNotificationHistory;
use App\Enums\NotificationType;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class ExpoNotificationService
{
    public function send(User $user, NotificationType|Notification $notification, ?array $data = null): void
    {
        if ($user->deviceTokens->isEmpty()) {
            return;
        }

        $notificationObject = $notification instanceof Notification
            ? $notification
            : $this->createNotification($notification, $data);

        $user->notify($notificationObject);
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
                    NotificationType::BONUS_PROFILE_COMPLETION => 'Бонус за анкету',
                };
            }

            private function getMessage(): string
            {
                return match($this->type) {
                    NotificationType::BONUS_CREDIT => "Вам начислено {$this->data['amount']} бонусов за покупку на сумму {$this->data['purchase_amount']}",
                    NotificationType::BONUS_DEBIT => "Списано {$this->data['debit_amount']} бонусов. Остаток: {$this->data['remaining_bonus']}",
                    NotificationType::BONUS_PROMOTION => "Вам начислено {$this->data['bonus_amount']} акционных бонусов. Действует до {$this->data['expiry_date']}",
                    NotificationType::BONUS_PROFILE_COMPLETION => "Поздравляем! Вам начислено {$this->data['amount']} бонусов за заполнение анкеты. Спасибо за предоставленную информацию!",
                };
            }
        };
    }

    public function broadcastToAllUsers(NotificationType|Notification $notification, ?array $data = null): void
    {
        $users = User::whereHas('deviceTokens')->get();

        foreach ($users as $user) {
            $this->send($user, $notification, $data);
        }
    }

    public function broadcastCustomMessage(string $title, string $message): void
    {
        $customNotification = new class($title, $message) extends Notification {
            public function __construct(
                private readonly string $title,
                private readonly string $message
            )
            {
            }

            public function via($notifiable): array
            {
                return [ExpoChannel::class];
            }

            public function toExpo($notifiable): ExpoMessage
            {
                return ExpoMessage::create()
                    ->title($this->title)
                    ->body($this->message)
                    ->data(['type' => 'custom']);
            }
        };

        PushNotificationHistory::create([
            'title' => $title,
            'message' => $message,
            'type' => 'custom',
        ]);

        $this->broadcastToAllUsers($customNotification);
    }
}