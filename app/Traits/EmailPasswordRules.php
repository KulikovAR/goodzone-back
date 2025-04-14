<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Validation\Rule;

trait EmailPasswordRules
{
    protected function passwordCreationRules(): array
    {
        return $this->passwordResetRules() + ['confirmed'];
    }

    protected function passwordResetRules(): array
    {
        return ['required', 'string', 'min:8'];
    }

    protected function emailCreationRules(): array
    {
        return ['required', 'string', 'email', 'max:100', Rule::unique(User::class)];
    }

    protected function emailInputRules(): array
    {
        return ['required', 'string', 'email', 'max:100'];
    }
}
