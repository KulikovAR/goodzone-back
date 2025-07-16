<?php

namespace App\Http\Requests\Bonus;

use Illuminate\Foundation\Http\FormRequest;

class DebitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            '*.phone' => ['required', 'string', 'exists:users,phone'],
            '*.debit_amount' => ['required', 'numeric', 'min:0'],
            '*.id_sell' => ['required', 'string', 'max:255'],
            '*.parent_id_sell' => ['nullable', 'string', 'max:255'],
            '*.timestamp' => ['required', 'date']
        ];
    }
}
