<?php

declare(strict_types=1);

namespace App\Contracts;

interface EventServiceContract
{
    public function handler(array $request): void;

    public function proccess(): void;

    public function distribute(): void;
}
