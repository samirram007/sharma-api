<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiErrorResponse
{
    /**
     * Build a standardized error response.
     *
     * @param string $message   Human-readable error message
     * @param int $code         HTTP status code
     * @param array|null $errors Optional array of detailed field errors
     * @param string|null $errorCode Optional internal error code for frontend
     * @return JsonResponse
     */
    public static function respond(
        string $message,
        int $code = 500,
        ?array $errors = null,
        ?string $errorCode = null,
        ?array $debug = null
    ): JsonResponse {
        // Ensure the status code is a valid HTTP status code.
        // If it's 0 or outside the valid range, default to 500.
        if ($code < 100 || $code >= 600) {
            $code = 500;
        }

        $response = [
            'success' => false,
            'message' => $message,
            'code'    => $code,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        if ($errorCode) {
            $response['errorCode'] = $errorCode;
        }

        if ($debug && config('app.debug')) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $code);
    }
}
