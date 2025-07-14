<?php

namespace App\Docs;

class OneCController
{
    /**
     * @OA\Post(
     *     path="/1c/register",
     *     summary="Регистрация пользователя из 1С",
     *     description="Создает нового пользователя с обязательными данными(полями) анкеты, поступающими из системы 1С.",
     *     tags={"1C Integration"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"phone", "name", "gender", "city", "email", "birthday"},
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Номер телефона пользователя",
     *                 example="+79991234567"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Имя пользователя",
     *                 example="Иван Петров"
     *             ),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 enum={"male", "female"},
     *                 description="Пол пользователя",
     *                 example="male"
     *             ),
     *             @OA\Property(
     *                 property="city",
     *                 type="string",
     *                 description="Город пользователя",
     *                 example="Москва"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="Email пользователя",
     *                 example="ivan@example.com"
     *             ),
     *             @OA\Property(
     *                 property="birthday",
     *                 type="string",
     *                 format="date",
     *                 description="Дата рождения пользователя",
     *                 example="1990-01-15"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешная регистрация",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Пользователь успешно зарегистрирован"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="phone", type="string", example="+79991234567"),
     *                 @OA\Property(property="bonus_awarded", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="phone",
     *                     type="array",
     *                     @OA\Items(type="string", example="Пользователь с таким номером телефона уже существует")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Доступ запрещен",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Доступ запрещен")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ошибка при регистрации пользователя")
     *         )
     *     )
     * )
     */
    public function register()
    {
        //
    }
} 