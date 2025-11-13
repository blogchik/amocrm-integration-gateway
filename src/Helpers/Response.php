<?php

namespace App\Helpers;

/**
 * Response Helper
 * 
 * JSON response yuborish uchun helper class
 */
class Response
{
    /**
     * JSON response yuborish
     * 
     * @param mixed $data
     * @param int $statusCode
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Success response
     * 
     * @param mixed $data
     * @param string|null $message
     */
    public static function success($data = [], ?string $message = null): void
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if (!empty($data)) {
            $response['data'] = $data;
        }

        self::json($response, 200);
    }

    /**
     * Error response
     * 
     * @param string $message
     * @param array $details
     * @param int $statusCode
     */
    public static function error(string $message, array $details = [], int $statusCode = 400): void
    {
        $response = [
            'success' => false,
            'error' => $message,
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        self::json($response, $statusCode);
    }

    /**
     * 404 Not Found
     * 
     * @param string|null $message
     */
    public static function notFound(?string $message = null): void
    {
        self::error($message ?? 'Endpoint not found', [], 404);
    }

    /**
     * 401 Unauthorized
     * 
     * @param array $data
     */
    public static function unauthorized(array $data = []): void
    {
        $response = array_merge([
            'success' => false,
            'error' => 'Unauthorized',
        ], $data);

        self::json($response, 401);
    }

    /**
     * 500 Internal Server Error
     * 
     * @param string|null $message
     */
    public static function serverError(?string $message = null): void
    {
        self::error($message ?? 'Internal server error', [], 500);
    }

    /**
     * Validation error response
     * 
     * @param array $errors
     */
    public static function validationError(array $errors): void
    {
        self::error('Validation failed', $errors, 422);
    }
}
