<?php

namespace App\Docs;

class SearchCityController
{
    /**
     * @OA\Get(
     *     path="/v1/search-cities",
     *     summary="Поиск городов",
     *     description="Поиск городов по введенной строке через сервис Dadata",
     *     tags={"City"},
     *     security={{"api": {}}},
     *     @OA\Parameter(
     *         name="s",
     *         in="query",
     *         required=true,
     *         description="Строка для поиска города",
     *         @OA\Schema(type="string", example="м")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный поиск",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(
     *                     property="city", 
     *                     type="string", 
     *                     example="Москва"
     *                 )
     *             ),
     *             example={
     *                 {"city": "Москва"},
     *                 {"city": "Магадан"},
     *                 {"city": "Махачкала"},
     *                 {"city": "Минск"},
     *                 {"city": "Майкоп"},
     *                 {"city": "Мурманск"}
     *             }
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
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Missing parameter: s")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка Dadata API",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Something went wrong on Dadata side.")
     *         )
     *     )
     * )
     */
    public function __invoke()
    {
        //
    }
}