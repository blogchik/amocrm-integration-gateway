<?php

namespace App\Middleware;

use App\Config;
use App\Helpers\Response;

/**
 * ApiKeyMiddleware
 * 
 * Barcha requestlarni API Key orqali autentifikatsiya qiladi
 */
class ApiKeyMiddleware
{
    /**
     * API Key tekshirish
     * 
     * @return bool
     */
    public static function check(): bool
    {
        $expectedKey = Config::get('API_KEY');
        $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if ($providedKey !== $expectedKey) {
            Response::unauthorized([
                'error' => 'Invalid or missing API key',
                'message' => 'Please provide valid X-API-KEY header'
            ]);
            return false;
        }

        return true;
    }
}
