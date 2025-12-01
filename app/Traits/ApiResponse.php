<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

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

    protected function limit_excedeed_response(
        Request $request,
        array $headers,
        int $max_requests,
        array $options = [],
    ): JsonResponse|RedirectResponse {
        $status = 'limit-excedeed-response';

        // Check if request is expects JSON response or not
        if ($request->isJson() || $request->wantsJson() || $request->expectsJson() || $request->ajax() || $request->isXmlHttpRequest()) {
            $this->setAdditionalHeaders($headers);

            $RETRY_AFTER = $headers['Retry-After'] ?? RateLimiter::availableIn($request);
            $ADDITIONAL_DATA = [
                'limit_details' => [
                    'max_requests_allowed' => $max_requests,
                    'time_to_wait_until_reset' => (int) $RETRY_AFTER,
                    'reset_time_until_next_request' => time() + $RETRY_AFTER,
                    'client_ip' => $request->ip()
                ]
            ];

            return $this->error_response((in_array('message', $options) ? $options['message'] : 'Too many requests, the request limit has been exceeded. Please try again later.'), (in_array('additional', $options) ? array_merge($ADDITIONAL_DATA, $options['additional']) : $ADDITIONAL_DATA), Response::HTTP_TOO_MANY_REQUESTS, $status);
        } else {
            abort(Response::HTTP_TOO_MANY_REQUESTS, (in_array('message', $options) ? $options['message'] : 'Too many requests, the request limit has been exceeded. Please try again later.'), $headers);
        }
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
        // SET additional headers value
        $this->setAdditionalHeaders($headers);

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
        // SET additional headers value
        $this->setAdditionalHeaders($headers);

        return $this->error_response(
            message: $message,
            additional: $errors,
            code: Response::HTTP_NOT_FOUND,
            status: 'error-not-found-response'
        );
    }
}
