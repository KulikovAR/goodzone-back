<?php

namespace App\Filament\Resources\PushNotificationHistoryResource\Pages;

use App\Filament\Resources\PushNotificationHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPushNotificationHistory extends EditRecord
{
    protected static string $resource = PushNotificationHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
