<?php

namespace App\Adapters\Inbound\Http\Responses;

use Illuminate\Http\JsonResponse;

class StandardApiResponse
{
    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        if ($data === null) {
            return response()->json(null, $status);
        }
        return response()->json($data, $status);
    }

    public static function error(string $code, string $message, array $details = [], int $status = 400): JsonResponse
    {
        $payload = [
            'code' => $code,
            'message' => $message,
        ];
        if (!empty($details)) {
            $payload['details'] = $details;
        }
        return response()->json($payload, $status);
    }
}
