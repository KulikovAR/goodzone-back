<?php

namespace App\Filament\Resources\PushNotificationHistoryResource\Pages;

use App\Filament\Resources\PushNotificationHistoryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPushNotificationHistory extends ViewRecord
{
    protected static string $resource = PushNotificationHistoryResource::class;

    public function getTitle(): string
    {
        return 'Просмотр Push-уведомлений';
    }
}