<?php

namespace App\Http\Requests\Bonus;

use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'exists:users,phone'],
            'bonus_amount' => ['required', 'numeric', 'min:0'],
            'expiry_date' => ['required', 'date', 'after:now'],
        ];
    }
}