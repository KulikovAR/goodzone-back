<?php

namespace App\Docs;

class BonusController
{
    /**
     * @OA\Post(
     *     path="/bonus/credit",
     *     summary="Начисление бонусов",
     *     description="Начисляет бонусы пользователю за покупку",
     *     tags={"Bonus"},
     *     security={{ "sanctum": {} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="phone", type="string", example="+79991234567"),
     *             @OA\Property(property="bonus_amount", type="number", example=100),
     *             @OA\Property(property="purchase_amount", type="number", example=1000)
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
     *         response=404,
     *         description="Пользователь не найден"
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
     *     security={{ "sanctum": {} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="phone", type="string", example="+79991234567"),
     *             @OA\Property(property="debit_amount", type="number", example=50)
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
     *         response=404,
     *         description="Пользователь не найден"
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
     *     security={{ "sanctum": {} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="phone", type="string", example="+79991234567"),
     *             @OA\Property(property="bonus_amount", type="number", example=200),
     *             @OA\Property(property="expiry_date", type="string", format="date", example="2024-12-31")
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
     *         response=404,
     *         description="Пользователь не найден"
     *     )
     * )
     */
    public function promotion()
    {
        //
    }
}