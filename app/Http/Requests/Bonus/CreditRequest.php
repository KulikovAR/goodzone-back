<?php

namespace App\Http\Requests\Bonus;

use Illuminate\Foundation\Http\FormRequest;

class CreditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            '*.phone' => ['required', 'string', 'exists:users,phone'],
            '*.purchase_amount' => ['required', 'numeric', 'min:0'],
            '*.id_sell' => ['required', 'string', 'max:255'],
            '*.timestamp' => ['required', 'date']
        ];
    }
}
