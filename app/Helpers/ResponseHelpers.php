<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelpers{

    public static function jsonResponse($success,$message,$data,$statusCode): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data ?? null,
        ], $statusCode);
    }
}