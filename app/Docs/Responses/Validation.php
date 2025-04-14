<?php

namespace App\Docs\Responses;

/**
 * @OA\Response(
 *          response="422",
 *          description="Error validation",
 *
 *          @OA\MediaType(
 *              mediaType="application/json",
 *
 *              @OA\Schema(
 *
 *                  @OA\Property(property="message", type="string", example="The given data was invalid."),
 *                  @OA\Property(property="errors", type="object",
 *                      @OA\Property(property="email", type="array",
 *
 *                          @OA\Items(example="Must be a valid email.")
 *                      ),
 *
 *                      @OA\Property(property="password", type="array",
 *
 *                          @OA\Items(example="More than 8 chars."),
 *                      )
 *                  ),
 *             ),
 *         )
 *     ),
 */
class Validation {}
