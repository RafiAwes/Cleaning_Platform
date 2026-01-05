<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Success Response
     *
     * * @param mixed $data
     */
    public function successResponse($data, string $message = 'success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function errorResponse(string $message = 'error', int $statusCode = 400, $error = null): JsonResponse
    {
        $response = [
            'success' => false,
            'status' => 'error',
            'message' => $message,
        ];
        if (! empty($error)) {
            $response['data'] = $error;
        }

        return response()->json($response, $statusCode);
    }
}
