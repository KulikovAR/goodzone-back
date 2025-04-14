<?php

namespace App\Docs;

class AuthController
{
    /**
     * @OA\Get(
     *     path="/login",
     *     summary="Запрос SMS-кода для аутентификации",
     *     description="Создает временный SMS-код для аутентификации по номеру телефона. В тестовой среде код возвращается в ответе, в продакшн-среде отправляется на указанный номер.",
     *     tags={"Auth"},
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         required=true,
     *         description="Номер телефона пользователя",
     *
     *         @OA\Schema(type="string", example="+79161234567")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешный запрос",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     description="SMS-код (возвращается только в тестовой среде)",
     *                     example="123456"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
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
     *     description="Проверяет SMS-код и создает токены доступа и обновления. При успешной верификации возвращаются токены и их время истечения.",
     *     tags={"Auth"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Номер телефона",
     *                 example="+79161234567"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 description="SMS-код",
     *                 example="123456"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешная авторизация",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     description="Токен доступа",
     *                     example="8c9d0f82-5e0e-4cb6-998d-3c70db6fdd4f"
     *                 ),
     *                 @OA\Property(
     *                     property="refreshToken",
     *                     type="string",
     *                     description="Токен обновления",
     *                     example="bcbdbf91-9dcd-4d9e-a7b9-52a5654f12e2"
     *                 ),
     *                 @OA\Property(
     *                     property="expired",
     *                     type="string",
     *                     format="date-time",
     *                     description="Дата истечения срока токена доступа",
     *                     example="2024-09-05T13:45:30Z"
     *                 ),
     *                 @OA\Property(
     *                     property="expiredRefresh",
     *                     type="string",
     *                     format="date-time",
     *                     description="Дата истечения срока токена обновления",
     *                     example="2024-09-08T13:45:30Z"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Ошибка верификации: неверный код",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Неверный SMS-код"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос"
     *     )
     * )
     */
    public function vefiry()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/refresh",
     *     summary="Обновление токена доступа",
     *     description="Создает новый токен доступа на основе предоставленного токена обновления.",
     *     tags={"Auth"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="refresh",
     *                 type="string",
     *                 description="Токен обновления",
     *                 example="bcbdbf91-9dcd-4d9e-a7b9-52a5654f12e2"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное обновление токена",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     description="Новый токен доступа",
     *                     example="8c9d0f82-5e0e-4cb6-998d-3c70db6fdd4f"
     *                 ),
     *                 @OA\Property(
     *                     property="expired",
     *                     type="string",
     *                     format="date-time",
     *                     description="Дата истечения нового токена доступа",
     *                     example="2024-09-05T14:00:30Z"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Ошибка обновления: токен обновления недействителен",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Недействительный токен обновления"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос"
     *     )
     * )
     */
    public function refresh()
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/logout",
     *     summary="Выход из системы",
     *     description="Прекращает сеанс пользователя, удаляя токены доступа и обновления.",
     *     tags={"Auth"},
     *     security={{"api": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешный выход"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
     *     )
     * )
     */
    public function logout()
    {
        //
    }
}
