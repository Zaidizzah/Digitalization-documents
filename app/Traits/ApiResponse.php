<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use PhpParser\Node\Expr\Cast\Array_;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    private array $ADDITIONAL_HEADERS = [];
    private static array $MAIN_HEADERS = [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ];

    /**
     * Set additional headers for the response.
     *
     * @param array $headers    
     * @return void
     */
    protected function setAdditionalHeaders(array $headers)
    {
        $this->ADDITIONAL_HEADERS = array_merge(self::$MAIN_HEADERS, $headers);
    }

    protected function getAdditionalHeaders(): array
    {
        return $this->ADDITIONAL_HEADERS ?: self::$MAIN_HEADERS;
    }

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
        string $status = 'success-response',
    ): JsonResponse {
        $response = array_merge([
            'success' => true,
            'message' => $message,
            'status' => $status
        ], $additional ?? []);

        return response()->json($response, $code, $this->getAdditionalHeaders());
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
        string $status = 'error-response'
    ): JsonResponse {
        $response = array_merge([
            'success' => false,
            'message' => $message,
            'status' => $status
        ], $additional ?? []);

        return response()->json($response, $code, $this->getAdditionalHeaders());
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
            status: 'error-not-found-response'
        );
    }
}
