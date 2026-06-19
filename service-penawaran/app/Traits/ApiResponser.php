<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    protected function successResponse($data, $message = 'Data retrieved successfully', $code = 200, $meta = []): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => array_merge(['service_name' => 'Penawaran-Service', 'api_version' => 'v1'], $meta),
        ], $code);
    }
    protected function errorResponse($message, $code, $errors = null): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
}