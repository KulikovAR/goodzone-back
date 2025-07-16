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
     * @OA\Post(
     *     path="/bonus/credit",
     *     summary="Начисление бонусов",
     *     description="Начисляет бонусы пользователю за покупку (поддерживает пакетную обработку)",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 required={"phone", "purchase_amount", "id_sell", "timestamp"},
     *                 @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
     *                 @OA\Property(property="purchase_amount", type="number", example=1000, description="Сумма покупки"),
     *                 @OA\Property(property="id_sell", type="string", example="RECEIPT_123456", description="ID чека продажи от 1С"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-01T12:00:00Z", description="Временная метка операции")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное начисление (все операции)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Бонусы начислены"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="RECEIPT_123456"),
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="calculated_bonus_amount", type="integer", example=100),
     *                         @OA\Property(property="user_level", type="string", example="bronze"),
     *                         @OA\Property(property="cashback_percent", type="number", example=5)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=206,
     *         description="Частичное начисление (не все операции успешны)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Часть бонусов начислена"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     oneOf={
     *                         @OA\Schema(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", example="RECEIPT_123456"),
     *                             @OA\Property(property="success", type="boolean", example=true),
     *                             @OA\Property(
     *                                 property="data",
     *                                 type="object",
     *                                 @OA\Property(property="calculated_bonus_amount", type="integer", example=100),
     *                                 @OA\Property(property="user_level", type="string", example="bronze"),
     *                                 @OA\Property(property="cashback_percent", type="number", example=5)
     *                             )
     *                         ),
     *                         @OA\Schema(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", example="RECEIPT_789012"),
     *                             @OA\Property(property="success", type="boolean", example=false),
     *                             @OA\Property(property="error", type="string", example="Ошибка при обработке")
     *                         )
     *                     }
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
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 required={"phone", "debit_amount", "id_sell", "timestamp"},
     *                 @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
     *                 @OA\Property(property="debit_amount", type="number", example=50, description="Сумма списываемых бонусов"),
     *                 @OA\Property(property="id_sell", type="string", example="DEBIT_123456", description="ID чека списания от 1С"),
     *                 @OA\Property(property="parent_id_sell", type="string", example="RECEIPT_123456", description="ID родительского чека (опционально)"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-01T12:00:00Z", description="Временная метка операции")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешное списание",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Бонусы списаны"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="DEBIT_123456"),
     *                     @OA\Property(property="success", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=206,
     *         description="Частичное списание (не все операции успешны)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Часть бонусов списана"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     oneOf={
     *                         @OA\Schema(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", example="DEBIT_123456"),
     *                             @OA\Property(property="success", type="boolean", example=true)
     *                         ),
     *                         @OA\Schema(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", example="DEBIT_789012"),
     *                             @OA\Property(property="success", type="boolean", example=false),
     *                             @OA\Property(property="error", type="string", example="Недостаточно бонусов")
     *                         )
     *                     }
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Недостаточно бонусов",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Не удалось списать бонусы")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
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
    *     path="/bonus/refund",
    *     summary="Возврат бонусов (отмена покупки) пачкой",
    *     description="Отменяет начисление бонусов за конкретные покупки и возвращает пропорциональную часть списанных бонусов. 
    *                  Поддерживается пакетная обработка нескольких операций в одном запросе. 
    *                  Для каждой операции: 1) Уменьшается сумма покупок пользователя на сумму возврата, 
    *                  2) Пересчитывается уровень пользователя, 
    *                  3) Отменяется пропорциональная часть начисленных бонусов по текущему уровню, 
    *                  4) Возвращается пропорциональная часть списанных бонусов по данному чеку (если таковые были).",
    *     tags={"Bonus"},
    *     security={{"api": {}}},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 type="object",
    *                 required={"phone", "id_sell", "parent_id_sell", "refund_amount", "timestamp"},
    *                 @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
    *                 @OA\Property(property="id_sell", type="string", example="REFUND_789012", description="ID чека возврата от 1С"),
    *                 @OA\Property(property="parent_id_sell", type="string", example="RECEIPT_123456", description="ID чека продажи, по которому делается возврат"),
    *                 @OA\Property(property="refund_amount", type="number", example=500, description="Сумма возврата товара"),
    *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-07-16T12:34:56Z", description="Временная метка операции возврата")
    *             )
    *         )
    *     ),
    * 
    *     @OA\Response(
    *         response=200,
    *         description="Успешный возврат (все операции успешны)",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="ok", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Бонусы возвращены (возврат товара)"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     type="object",
    *                     @OA\Property(property="id", type="string", example="REFUND_789012", description="ID чека возврата"),
    *                     @OA\Property(property="success", type="boolean", example=true),
    *                     @OA\Property(property="data", type="object",
    *                         @OA\Property(property="refunded_bonus_amount", type="integer", example=25, description="Сумма отмененных начисленных бонусов"),
    *                         @OA\Property(property="returned_debit_amount", type="integer", example=25, description="Сумма возвращенных списанных бонусов"),
    *                         @OA\Property(property="refund_receipt_id", type="string", example="REFUND_789012", description="ID чека возврата"),
    *                         @OA\Property(property="original_receipt_id", type="string", example="RECEIPT_123456", description="ID исходного чека продажи"),
    *                         @OA\Property(property="refund_amount", type="integer", example=500, description="Сумма возврата товара")
    *                     )
    *                 )
    *             )
    *         )
    *     ),
    * 
    *     @OA\Response(
    *         response=206,
    *         description="Частичный успех возврата (часть операций выполнена успешно, часть с ошибками)",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="ok", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Часть бонусов возвращена"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     oneOf={
    *                         @OA\Schema(
    *                             type="object",
    *                             required={"id", "success", "data"},
    *                             @OA\Property(property="id", type="string", example="REFUND_789012"),
    *                             @OA\Property(property="success", type="boolean", example=true),
    *                             @OA\Property(property="data", type="object",
    *                                 @OA\Property(property="refunded_bonus_amount", type="integer", example=25, description="Сумма отмененных начисленных бонусов"),
    *                                 @OA\Property(property="returned_debit_amount", type="integer", example=25, description="Сумма возвращенных списанных бонусов"),
    *                                 @OA\Property(property="refund_receipt_id", type="string", example="REFUND_789012"),
    *                                 @OA\Property(property="original_receipt_id", type="string", example="RECEIPT_123456"),
    *                                 @OA\Property(property="refund_amount", type="integer", example=500)
    *                             )
    *                         ),
    *                         @OA\Schema(
    *                             type="object",
    *                             required={"id", "success", "error"},
    *                             @OA\Property(property="id", type="string", example="REFUND_789013"),
    *                             @OA\Property(property="success", type="boolean", example=false),
    *                             @OA\Property(property="error", type="string", example="Исходный чек продажи с ID RECEIPT_123457 не найден для данного пользователя")
    *                         )
    *                     }
    *                 ),
    *                 example={
    *                     {
    *                         "id": "REFUND_789012",
    *                         "success": true,
    *                         "data": {
    *                             "refunded_bonus_amount": 25,
    *                             "returned_debit_amount": 25,
    *                             "refund_receipt_id": "REFUND_789012",
    *                             "original_receipt_id": "RECEIPT_123456",
    *                             "refund_amount": 500
    *                         }
    *                     },
    *                     {
    *                         "id": "REFUND_789013",
    *                         "success": false,
    *                         "error": "Исходный чек продажи с ID RECEIPT_123457 не найден для данного пользователя"
    *                     }
    *                 }
    *             )
    *         )
    *     ),
    * 
    *     @OA\Response(
    *         response=400,
    *         description="Все операции возврата не выполнены",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="ok", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Не удалось вернуть бонусы"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     type="object",
    *                     @OA\Property(property="id", type="string", example="REFUND_789012"),
    *                     @OA\Property(property="success", type="boolean", example=false),
    *                     @OA\Property(property="error", type="string", example="Исходный чек продажи с ID RECEIPT_123457 не найден для данного пользователя")
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
    public function refund()
    {
        // only docs
    }

    /**
     * @OA\Post(
     *     path="/bonus/promotion",
     *     summary="Начисление акционных бонусов",
     *     description="Начисляет указанную сумму акционных бонусов пользователю с определённым сроком действия.",
     *     tags={"Bonus"},
     *     security={{"api": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 required={"phone", "bonus_amount", "expiry_date", "timestamp"},
     *                 @OA\Property(property="phone", type="string", example="+79991234567", description="Номер телефона пользователя"),
     *                 @OA\Property(property="bonus_amount", type="number", example=100, description="Сумма акционных бонусов для начисления"),
     *                 @OA\Property(property="expiry_date", type="string", format="date-time", example="2025-12-31T23:59:59Z", description="Дата истечения срока действия бонусов"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-07-16T12:34:56Z", description="Время операции")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Все акционные бонусы успешно начислены",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Акционные бонусы начислены"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"id", "success", "data"},
     *                     @OA\Property(property="id", type="string", example="PROMO_123456", description="ID операции (id_sell или сгенерированный)"),
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=206,
     *         description="Часть акционных бонусов начислена",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Часть акционных бонусов начислена"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     oneOf={
     *                         @OA\Schema(
     *                             type="object",
     *                             required={"id", "success", "data"},
     *                             @OA\Property(property="id", type="string", example="PROMO_123456"),
     *                             @OA\Property(property="success", type="boolean", example=true),
     *                         ),
     *                         @OA\Schema(
     *                             type="object",
     *                             required={"id", "success", "error"},
     *                             @OA\Property(property="id", type="string", example="PROMO_654321"),
     *                             @OA\Property(property="success", type="boolean", example=false),
     *                             @OA\Property(property="error", type="string", example="Неверный формат даты истечения срока действия")
     *                         )
     *                     }
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка при начислении акционных бонусов",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Не удалось начислить акционные бонусы")
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
        // only docs
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
