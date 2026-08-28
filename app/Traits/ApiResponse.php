<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Return a successful JSON response.
     */
    protected function successResponse(mixed $data = null, string $message = null, int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => true,
        ];

        // Merge data directly to maintain backward compatibility
        if ($data !== null) {
            $response = array_merge($response, $data);
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message, mixed $errors = null, int $statusCode = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Return a not found JSON response.
     */
    protected function notFoundResponse(string $message = 'Data tidak ditemukan.'): \Illuminate\Http\JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Return a validation error JSON response.
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validasi gagal.'): \Illuminate\Http\JsonResponse
    {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Return a server error JSON response.
     */
    protected function serverErrorResponse(string $message = 'Terjadi kesalahan pada server.'): \Illuminate\Http\JsonResponse
    {
        return $this->errorResponse($message, null, 500);
    }
}
