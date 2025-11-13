<?php

namespace App;

/**
 * Config Class
 * 
 * .env fayldan konfiguratsiyani yuklaydi
 */
class Config
{
    private static bool $loaded = false;

    /**
     * .env faylni yuklash
     */
    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envPath = dirname(__DIR__) . '/.env';
        
        if (!file_exists($envPath)) {
            throw new \Exception('.env file not found');
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Set environment variable
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Environment variable olish
     * 
     * @param string $key Variable nomi
     * @param mixed $default Default qiymat
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::load();

        // Support dot notation (e.g., 'amocrm.domain')
        if (strpos($key, '.') !== false) {
            return self::getDotNotation($key, $default);
        }

        $value = $_ENV[$key] ?? getenv($key);
        
        return $value !== false ? $value : $default;
    }

    /**
     * Dot notation bilan olish (backward compatibility)
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private static function getDotNotation(string $key, $default = null)
    {
        // Map old config keys to new env keys
        $mapping = [
            'api_key' => 'API_KEY',
            'amocrm.domain' => 'AMO_DOMAIN',
            'amocrm.client_id' => 'AMO_CLIENT_ID',
            'amocrm.client_secret' => 'AMO_CLIENT_SECRET',
            'amocrm.redirect_uri' => 'AMO_REDIRECT_URI',
            'token_storage_path' => 'TOKEN_STORAGE_PATH',
            'log_errors' => 'LOG_ERRORS',
        ];

        if (isset($mapping[$key])) {
            return self::get($mapping[$key], $default);
        }

        return $default;
    }

    /**
     * Environment variable mavjudligini tekshirish
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        self::load();
        return isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Barcha environment variablelarni olish
     * 
     * @return array
     */
    public static function all(): array
    {
        self::load();
        return $_ENV;
    }
}
