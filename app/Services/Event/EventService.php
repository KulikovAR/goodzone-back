<?php

namespace App\Services;

use App\Contracts\EventServiceContract;

class EventService implements EventServiceContract
{
    public function handler(array $request): void
    {
        $this->proccess();

    }

    public function proccess(): void {}

    public function distribute(): void {}
}
