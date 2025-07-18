<?php

namespace App\Filament\Pages;

use App\Services\ExpoNotificationService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;

class SendPushNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Push-уведомления';
    protected static ?string $title = 'Отправка Push-уведомлений';
    protected static string $view = 'filament.pages.send-push-notification';

    public ?string $notificationTitle = '';
    public ?string $message = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('notificationTitle')
                    ->label('Заголовок уведомления')
                    ->required()
                    ->maxLength(100),

                Textarea::make('message')
                    ->label('Текст уведомления')
                    ->required()
                    ->rows(5),
            ]);
    }

    public function send(): void
    {
        if (empty($this->notificationTitle) || empty($this->message)) {
            Notification::make()
                ->title('Пожалуйста, заполните заголовок и текст уведомления.')
                ->danger()
                ->send();
            return;
        }

        app(ExpoNotificationService::class)->broadcastCustomMessage(
            $this->notificationTitle,
            $this->message
        );

        Notification::make()
            ->title('Уведомление успешно отправлено!')
            ->success()
            ->send();

        $this->notificationTitle = '';
        $this->message = '';
        $this->form->fill(); // Очистка формы
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Отправить')
                ->label('Отправить уведомление')
                ->action('send')
                ->color('primary'),
        ];
    }
}