<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Success Response
     *
     * @param string $message
     * @param array $additional
     * @param int $code
     * @return JsonResponse
     */
    protected function success_response(
        string $message,
        array|null $additional = null,
        int $code = Response::HTTP_OK,
        string $status = 'success-response'
    ): JsonResponse {
        $response = array_merge([
            'success' => true,
            'message' => $message,
            'status' => $status
        ], $additional ?? []);

        return response()->json($response, $code)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Error Response
     *
     * @param string $message
     * @param array $additional
     * @param int $code
     * @return JsonResponse
     */
    protected function error_response(
        string $message,
        ?array $additional = [],
        int $code = Response::HTTP_BAD_REQUEST,
        array $headers = [],
        string $status = 'error-response'
    ): JsonResponse {
        $response = array_merge([
            'success' => false,
            'message' => $message,
            'status' => $status
        ], $additional ?? []);

        return response()->json($response, $code, $headers);
    }

    /**
     * Validation Error Response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validation_error(
        string $message = 'The given data was invalid.',
        array|string $errors,
        array $headers = []
    ): JsonResponse {
        return $this->error_response(
            message: $message,
            additional: $errors,
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
            headers: $headers,
            status: 'error-validation-response'
        );
    }

    /**
     * Not Found Response
     *
     * @param string $message
     * @param mixed|null $errors
     * @return JsonResponse
     */
    protected function not_found_response(
        string $message = 'Resource you\'re looking for is not found.',
        ?array $errors = [],
        array $headers = []
    ): JsonResponse {
        return $this->error_response(
            message: $message,
            additional: $errors,
            code: Response::HTTP_NOT_FOUND,
            headers: $headers,
            status: 'error-not-found-response'
        );
    }
}
