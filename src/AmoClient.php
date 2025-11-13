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

    public function __construct()
    {
        $this->storage = new AmoTokenStorage();
        $this->auth = new AmoAuth();
        $this->domain = Config::get('amocrm.domain');
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
        // Token muddati tugagan bo'lsa, yangilaymiz
        if ($this->storage->isExpired()) {
            $refreshed = $this->auth->refreshToken();
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
            $refreshed = $this->auth->refreshToken();
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
}
