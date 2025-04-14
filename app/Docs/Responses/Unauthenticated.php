<?php

namespace App\Docs\Responses;

/**
 * @OA\Response(
 *          response="401",
 *          description="Unauthenticated",
 *
 *          @OA\MediaType(
 *              mediaType="application/json",
 *
 *              @OA\Schema(
 *
 *                  @OA\Property(property="message", type="string", example="Unauthenticated."),
 *              )
 *          )
 *      )
 */
class Unauthenticated {}
