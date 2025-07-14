<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFromOneCRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10', 'unique:users,phone'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:male,female'],
            'city' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'birthday' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Номер телефона обязателен для заполнения',
            'phone.unique' => 'Пользователь с таким номером телефона уже существует',
            'phone.regex' => 'Неверный формат номера телефона',
            'name.required' => 'Имя обязательно для заполнения',
            'gender.required' => 'Пол обязателен для заполнения',
            'gender.in' => 'Пол должен быть male или female',
            'city.required' => 'Город обязателен для заполнения',
            'email.required' => 'Email обязателен для заполнения',
            'email.email' => 'Неверный формат email',
            'email.unique' => 'Пользователь с таким email уже существует',
            'birthday.required' => 'Дата рождения обязательна для заполнения',
            'birthday.date' => 'Неверный формат даты рождения',
            'birthday.before_or_equal' => 'Дата рождения не может быть в будущем',
        ];
    }
} 