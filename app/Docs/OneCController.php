<?php

namespace App\Docs;

class OneCController
{
    /**
     * @OA\Post(
     *     path="/1c/register",
     *     summary="Регистрация пользователя из 1С",
     *     description="Создает нового пользователя с полными данными анкеты, поступающими из системы 1С. Пользователь создается с подтвержденным телефоном, но бонус за заполнение профиля НЕ начисляется автоматически. Бонус будет начислен только когда пользователь дозаполнит недостающие поля (children, marital_status) в мобильном приложении.",
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
     *                 description="Номер телефона пользователя (формат: +79991234567)",
     *                 example="+79991234567",
     *                 pattern="^([0-9\s\-\+\(\)]*)$"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Имя пользователя",
     *                 example="Иван Петров",
     *                 maxLength=255
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
     *                 example="Москва",
     *                 maxLength=255
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="Email пользователя (должен быть уникальным)",
     *                 example="ivan@example.com"
     *             ),
     *             @OA\Property(
     *                 property="birthday",
     *                 type="string",
     *                 format="date",
     *                 description="Дата рождения пользователя (не может быть в будущем)",
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
     *                 @OA\Property(property="user_id", type="integer", example=123, description="ID созданного пользователя"),
     *                 @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
     *                 @OA\Property(property="bonus_awarded", type="boolean", example=false, description="Бонус за заполнение профиля НЕ начислен (будет начислен при дозаполнении в приложении)")
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
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="Пользователь с таким email уже существует")
     *                 ),
     *                 @OA\Property(
     *                     property="birthday",
     *                     type="array",
     *                     @OA\Items(type="string", example="Дата рождения не может быть в будущем")
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
     *             @OA\Property(property="message", type="string", example="Ошибка при регистрации пользователя: [детали ошибки]")
     *         )
     *     )
     * )
     */
    public function register()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/1c/client-info",
     *     summary="Получить информацию о клиентах по номерам телефонов",
     *     description="Возвращает информацию о клиентах: процент кэшбека, количество бонусов, уровень и статус регистрации. Принимает массив номеров телефонов в JSON формате. Для незарегистрированных пользователей возвращает null значения.",
     *     tags={"1C Integration"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"phones"},
     *             @OA\Property(
     *                 property="phones",
     *                 type="array",
     *                 description="Массив номеров телефонов для проверки",
     *                 @OA\Items(
     *                     type="string",
     *                     pattern="^([0-9\s\-\+\(\)]*)$",
     *                     example="+79991234567"
     *                 ),
     *                 example={"+79991234567", "+79991234568"}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Информация о клиентах получена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Информация о клиентах получена"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="clients",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона"),
     *                         @OA\Property(property="is_registered", type="boolean", example=true, description="Зарегистрирован ли пользователь"),
     *                         @OA\Property(property="cashback_percent", type="number", format="float", example=10, nullable=true, description="Процент кешбэка (null для незарегистрированных)"),
     *                         @OA\Property(property="bonus_amount", type="integer", example=150, nullable=true, description="Количество бонусов (null для незарегистрированных)"),
     *                         @OA\Property(property="level", type="string", example="silver", nullable=true, description="Уровень пользователя (null для незарегистрированных)"),
     *                         @OA\Property(property="total_purchase_amount", type="integer", example=10000, nullable=true, description="Общая сумма покупок (null для незарегистрированных)")
     *                     )
     *                 ),
     *                 @OA\Property(property="total_count", type="integer", example=1),
     *                 @OA\Property(property="registered_count", type="integer", example=1),
     *                 @OA\Property(property="unregistered_count", type="integer", example=0)
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
     *                     property="phones",
     *                     type="array",
     *                     @OA\Items(type="string", example="Номера телефонов обязательны")
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
     *             @OA\Property(property="message", type="string", example="Ошибка при получении информации о клиентах")
     *         )
     *     )
     * )
     */
    public function getClientInfo()
    {
        //
    }
} 