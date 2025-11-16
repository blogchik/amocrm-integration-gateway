<?php

namespace App;

/**
 * AmoClient Class
 * 
 * AmoCRM API bilan ishlash uchun CURL wrapper
 * Avtomatik token refresh qiladi
 */
class AmoClient
{
    private AmoTokenStorage $storage;
    private AmoAuth $auth;
    private string $domain;
    private string $lockFile;

    public function __construct()
    {
        $this->storage = new AmoTokenStorage();
        $this->auth = new AmoAuth();
        $this->domain = Config::get('amocrm.domain');
        
        // Token refresh uchun lock file
        $storagePath = Config::get('token_storage_path', './storage/tokens.json');
        if (strpos($storagePath, './') === 0) {
            $storagePath = dirname(__DIR__) . '/' . substr($storagePath, 2);
        }
        $this->lockFile = dirname($storagePath) . '/refresh.lock';
    }

    /**
     * POST so'rov yuborish
     * 
     * @param string $endpoint API endpoint (masalan: /api/v4/leads)
     * @param array $data
     * @return array
     */
    public function post(string $endpoint, array $data): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * GET so'rov yuborish
     * 
     * @param string $endpoint
     * @return array
     */
    public function get(string $endpoint): array
    {
        return $this->request('GET', $endpoint);
    }

    /**
     * Asosiy so'rov funksiyasi
     * 
     * @param string $method
     * @param string $endpoint
     * @param array|null $data
     * @param bool $isRetry
     * @return array
     */
    private function request(string $method, string $endpoint, ?array $data = null, bool $isRetry = false): array
    {
        // Token muddati tugagan bo'lsa, yangilaymiz (lock bilan)
        if ($this->storage->isExpired() && !$isRetry) {
            $refreshed = $this->refreshTokenWithLock();
            if (!$refreshed) {
                return [
                    'success' => false,
                    'error' => 'Failed to refresh token',
                ];
            }
        }

        $accessToken = $this->storage->getAccessToken();
        
        if (empty($accessToken)) {
            return [
                'success' => false,
                'error' => 'Access token is empty',
            ];
        }

        $url = "https://{$this->domain}{$endpoint}";
        
        $ch = curl_init($url);
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        if ($method === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            if ($data !== null) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            error_log("CURL error: $error");
            curl_close($ch);
            return [
                'success' => false,
                'error' => $error,
            ];
        }

        curl_close($ch);

        // Agar 401 bo'lsa va retry bo'lmagan bo'lsa, token refresh qilib qayta urinish
        if ($httpCode === 401 && !$isRetry) {
            $refreshed = $this->refreshTokenWithLock();
            if ($refreshed) {
                return $this->request($method, $endpoint, $data, true);
            }
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'http_code' => $httpCode,
            ];
        }

        // AmoCRM error handling
        if ($httpCode >= 400) {
            error_log("AmoCRM API error (HTTP $httpCode): " . json_encode($result));
            return [
                'success' => false,
                'error' => $result['title'] ?? 'Unknown error',
                'http_code' => $httpCode,
                'details' => $result,
            ];
        }

        return [
            'success' => true,
            'data' => $result,
            'http_code' => $httpCode,
        ];
    }

    /**
     * Token refresh qilish (lock mexanizmi bilan)
     * Race condition oldini oladi
     * 
     * @return bool
     */
    private function refreshTokenWithLock(): bool
    {
        // Lock file yaratish
        $lockDir = dirname($this->lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }

        $fp = fopen($this->lockFile, 'c');
        if ($fp === false) {
            error_log('Failed to create refresh lock file');
            return $this->auth->refreshToken();
        }

        // Exclusive lock olish (10 soniya timeout)
        $locked = false;
        $startTime = time();
        while (!$locked && (time() - $startTime) < 10) {
            $locked = flock($fp, LOCK_EX | LOCK_NB);
            if (!$locked) {
                usleep(100000); // 100ms kutish
            }
        }

        if (!$locked) {
            error_log('Failed to acquire refresh lock within timeout');
            fclose($fp);
            return false;
        }

        try {
            // Lock ichida qayta tekshirish (boshqa process yangilagan bo'lishi mumkin)
            if (!$this->storage->isExpired()) {
                // Token allaqachon yangilangan
                flock($fp, LOCK_UN);
                fclose($fp);
                return true;
            }

            // Token refresh
            $result = $this->auth->refreshToken();
            
            flock($fp, LOCK_UN);
            fclose($fp);
            
            return $result;
        } catch (\Throwable $e) {
            error_log('Exception during token refresh: ' . $e->getMessage());
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }
    }
}
