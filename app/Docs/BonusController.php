<?php

namespace App\Docs;

class BonusController
{
    /**
     * @OA\Get(
     *     path="/bonus/info",
     *     summary="Получение информации о бонусах",
     *     description="Возвращает текущий баланс бонусов, уровень пользователя и информацию о прогрессе к следующему уровню",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Успешное получение информации",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="bonus_amount", type="number", example=100),
     *                 @OA\Property(property="level", type="string", example="bronze", enum={"bronze", "silver", "gold"}),
     *                 @OA\Property(property="cashback_percent", type="number", example=5),
     *                 @OA\Property(property="total_purchase_amount", type="number", example=5000),
     *                 @OA\Property(property="next_level", type="string", example="silver", nullable=true),
     *                 @OA\Property(property="next_level_min_amount", type="number", example=10000, nullable=true),
     *                 @OA\Property(property="progress_to_next_level", type="number", example=50)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function info()
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/bonus/info-integration",
     *     summary="Получение информации о бонусах",
     *     description="Возвращает текущий баланс бонусов, уровень пользователя и информацию о прогрессе к следующему уровню",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\Parameter(
     *      name="id",
     *      description="phone",
     *      example="+7111111111",
     *      required=true,
     *      in="query",
     *      @OA\Schema(
     *          type="string"
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешное получение информации",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="bonus_amount", type="number", example=100),
     *                 @OA\Property(property="level", type="string", example="bronze", enum={"bronze", "silver", "gold"}),
     *                 @OA\Property(property="cashback_percent", type="number", example=5),
     *                 @OA\Property(property="total_purchase_amount", type="number", example=5000),
     *                 @OA\Property(property="next_level", type="string", example="silver", nullable=true),
     *                 @OA\Property(property="next_level_min_amount", type="number", example=10000, nullable=true),
     *                 @OA\Property(property="progress_to_next_level", type="number", example=50)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function infoIntegration()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/bonus/credit",
     *     summary="Начисление бонусов",
     *     description="Начисляет бонусы пользователю за покупку",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"phone", "bonus_amount", "purchase_amount"},
     *             @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
     *             @OA\Property(property="bonus_amount", type="number", example=100, description="Сумма начисляемых бонусов"),
     *             @OA\Property(property="purchase_amount", type="number", example=1000, description="Сумма покупки")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное начисление",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Бонусы начислены")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User]")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="phone",
     *                     type="array",
     *                     @OA\Items(type="string", example="The phone field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function credit()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/bonus/debit",
     *     summary="Списание бонусов",
     *     description="Списывает бонусы у пользователя",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"phone", "debit_amount"},
     *             @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
     *             @OA\Property(property="debit_amount", type="number", example=50, description="Сумма списываемых бонусов")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное списание",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Бонусы списаны")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Недостаточно бонусов",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Недостаточно бонусов")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User]")
     *         )
     *     )
     * )
     */
    public function debit()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/bonus/promotion",
     *     summary="Начисление акционных бонусов",
     *     description="Начисляет акционные бонусы пользователю с датой истечения",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"phone", "bonus_amount", "expiry_date"},
     *             @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
     *             @OA\Property(property="bonus_amount", type="number", example=200, description="Сумма акционных бонусов"),
     *             @OA\Property(property="expiry_date", type="string", format="date", example="2024-12-31", description="Дата истечения бонусов")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное начисление",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Акционные бонусы начислены")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User]")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="expiry_date",
     *                     type="array",
     *                     @OA\Items(type="string", example="The expiry date must be a date after now.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function promotion()
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/bonus/history",
     *     summary="Получение истории бонусов",
     *     description="Возвращает историю начисления и списания бонусов пользователя",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Успешное получение истории",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="history",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="amount", type="number", example=100),
     *                         @OA\Property(property="type", type="string", example="regular", enum={"regular", "promotional"}),
     *                         @OA\Property(property="purchase_amount", type="number", example=1000, nullable=true),
     *                         @OA\Property(property="expires_at", type="string", format="date-time", example="2024-12-31T23:59:59", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00")
     *                     )
     *                 ),
     *                 @OA\Property(property="total_count", type="integer", example=10)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function history()
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/bonus-level",
     *     summary="Получение информации о бонусных уровнях",
     *     description="Возвращает информацию о всех доступных бонусных уровнях и их условиях",
     *     tags={"Bonus"},
     *     security={{ "sanctum": {} }},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное получение информации",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="bronze", enum={"bronze", "silver", "gold"}),
     *                     @OA\Property(property="cashback_percent", type="integer", example=5),
     *                     @OA\Property(property="min_purchase_amount", type="integer", example=0)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function levels()
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/bonus/promotional-history",
     *     summary="Получение истории бонусов",
     *     description="Возвращает историю начисления и списания бонусов пользователя",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Успешное получение истории",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="history",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="amount", type="number", example=100),
     *                         @OA\Property(property="type", type="string", example="regular", enum={"regular", "promotional"}),
     *                         @OA\Property(property="purchase_amount", type="number", example=1000, nullable=true),
     *                         @OA\Property(property="expires_at", type="string", format="date-time", example="2024-12-31T23:59:59", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00")
     *                     )
     *                 ),
     *                 @OA\Property(property="total_count", type="integer", example=10)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function promotionalHistory()
    {

    }
}
