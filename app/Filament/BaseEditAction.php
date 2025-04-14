<?php

namespace App\Filament;

use Filament\Resources\Pages\EditRecord;

class BaseEditAction extends EditRecord
{
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
