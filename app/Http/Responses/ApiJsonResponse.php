<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use stdClass;

class ApiJsonResponse implements Responsable
{
    public function __construct(
        public readonly int $httpCode = 200,
        public readonly bool $ok = true,
        public readonly string $message = '',
        public readonly object|array $data = new stdClass,
    ) {}

    public function toResponse($request): JsonResponse
    {
        return response()->json(
            [
                'ok' => $this->ok,
                'data' => $this->data,
                'message' => $this->message,
            ],
            $this->httpCode
        );
    }
}
