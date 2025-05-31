<?php

namespace App\Docs;

class UserController
{
    /**
     * @OA\Get(
     *     path="/user",
     *     summary="Получение данных пользователя",
     *     description="Возвращает данные авторизованного пользователя",
     *     tags={"User"},
     *     security={{"api": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Успешный запрос",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Данные пользователя получены"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone", type="string", example="+79991234567"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="city", type="string", example="Moscow"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1990-01-01", nullable=true),
     *                 @OA\Property(property="children", type="string", example="2", nullable=true),
     *                 @OA\Property(property="marital_status", type="string", example="married", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован"
     *     )
     * )
     */
    public function show()
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/user/update",
     *     summary="Обновление данных пользователя",
     *     description="Обновляет данные авторизованного пользователя",
     *     tags={"User"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="city", type="string", example="Moscow"),
     *             @OA\Property(property="birthday", type="string", format="date", example="1990-01-01", nullable=true),
     *             @OA\Property(property="children", type="string", example="2", nullable=true),
     *             @OA\Property(property="marital_status", type="string", example="married", nullable=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное обновление",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Пользователь успешно обновлен")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     )
     * )
     */
    public function update()
    {
        //
    }
}
