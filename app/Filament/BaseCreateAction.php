<?php

namespace App\Filament;

use Filament\Resources\Pages\CreateRecord;

class BaseCreateAction extends CreateRecord
{
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
