<?php

namespace App\Http\Requests\OneC;

use Illuminate\Foundation\Http\FormRequest;

class ClientInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phones' => 'required|array',
            'phones.*' => 'required|string|regex:/^\+7[0-9]{10}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'phones.required' => 'Номера телефонов обязательны',
            'phones.array' => 'Номера телефонов должны быть в виде массива',
            'phones.*.required' => 'Номер телефона обязателен',
            'phones.*.string' => 'Номер телефона должен быть строкой',
            'phones.*.regex' => 'Номер телефона должен быть в формате +7XXXXXXXXXX',
        ];
    }
} 