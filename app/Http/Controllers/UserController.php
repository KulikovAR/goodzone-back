<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateRequest;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function update(UpdateRequest $request): ApiJsonResponse
    {
        $user = Auth::user();
        $data = collect($request->validated())
            ->filter(fn ($value, $field) => $value !== $user->{$field})
            ->toArray();

        if (!empty($data)) {
            $user->update($data);
        }

        return new ApiJsonResponse(
            message: 'Пользователь успешно обновлен'
        );
    }
}