<?php

namespace App\Docs;

class AuthController
{
    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Запрос SMS-кода для аутентификации",
     *     description="Создает пользователя если не существует, генерирует SMS-код и обновляет время отправки кода",
     *     tags={"Auth"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Номер телефона пользователя",
     *                 example="+79991234567"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешный запрос",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Код отправлен на телефон"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     )
     * )
     */
    public function login()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/verify",
     *     summary="Верификация SMS-кода",
     *     description="Проверяет SMS-код, устанавливает время верификации телефона при первой проверке и возвращает токен доступа",
     *     tags={"Auth"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Номер телефона",
     *                 example="+79991234567"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 description="SMS-код",
     *                 example="1234"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешная авторизация",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Вход прошел успешно"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     description="Токен доступа",
     *                     example="1|laravel_sanctum_token..."
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Неверный код",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Неверный код")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Пользователь не найден")
     *         )
     *     )
     * )
     */
    public function verify()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Выход из системы",
     *     description="Выход пользователя из системы с возможностью выхода с конкретного устройства или со всех устройств",
     *     tags={"Auth"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="device_token",
     *                 type="string",
     *                 description="Токен устройства для выхода с конкретного устройства. Если не указан, происходит выход со всех устройств",
     *                 example="ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный выход",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Успешный выход")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function logout()
    {

    }
}
