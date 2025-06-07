<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'city' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email,' . $this->user()->id . ',id,deleted_at,NULL'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'children' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'birthday.before_or_equal' => 'Введите корректную дату рождения',
        ];
    }
}
