<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UuidRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['sometimes', 'uuid'],
        ];
    }
}
