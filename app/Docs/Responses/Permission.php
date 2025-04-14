<?php

namespace App\Docs\Responses;

/**
 * @OA\Response(
 *          response="403",
 *          description="Don't have permissions",
 *
 *          @OA\MediaType(
 *              mediaType="application/json",
 *
 *              @OA\Schema(
 *
 *                  @OA\Property(property="message", type="string", example="You don't have permissions to process this request"),
 *             ),
 *         )
 *     ),
 */
class Permission {}
