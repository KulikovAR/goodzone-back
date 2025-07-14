<?php

namespace App\Http\Requests\Bonus;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'exists:users,phone'],
            'id_sell' => ['required', 'string', 'max:255'],
            'parent_id_sell' => ['required', 'string', 'max:255'],
            'refund_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
} 