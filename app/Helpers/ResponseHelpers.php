<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseHelpers
{

    public static function jsonResponse($success, $message, $data, $statusCode): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        // Handle paginated data
        if ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['meta'] = [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ];
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
}
