<?php

namespace App;

/**
 * AmoAuth Class
 * 
 * AmoCRM OAuth2 avtorizatsiyasi
 */
class AmoAuth
{
    private AmoTokenStorage $storage;
    private string $domain;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->storage = new AmoTokenStorage();
        $this->domain = Config::get('amocrm.domain');
        $this->clientId = Config::get('amocrm.client_id');
        $this->clientSecret = Config::get('amocrm.client_secret');
        $this->redirectUri = Config::get('amocrm.redirect_uri');
    }

    /**
     * Authorization code orqali token olish
     * 
     * @param string $code
     * @return bool
     */
    public function getTokenByCode(string $code): bool
    {
        $url = "https://{$this->domain}/oauth2/access_token";
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ];

        $response = $this->makeRequest($url, $data);

        if (!$response || !isset($response['access_token'])) {
            error_log('Failed to get token by code: ' . json_encode($response));
            return false;
        }

        return $this->saveTokens($response);
    }

    /**
     * Refresh token orqali yangi token olish
     * 
     * @return bool
     */
    public function refreshToken(): bool
    {
        $refreshToken = $this->storage->getRefreshToken();

        if (empty($refreshToken)) {
            error_log('Refresh token is empty, cannot refresh');
            return false;
        }

        $url = "https://{$this->domain}/oauth2/access_token";
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'redirect_uri' => $this->redirectUri,
        ];

        $response = $this->makeRequest($url, $data);

        if (!$response || !isset($response['access_token'])) {
            error_log('Failed to refresh token: ' . json_encode($response));
            return false;
        }

        return $this->saveTokens($response);
    }

    /**
     * Tokenlarni saqlash
     * 
     * @param array $response
     * @return bool
     */
    private function saveTokens(array $response): bool
    {
        $tokens = [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'expires_at' => time() + $response['expires_in'],
        ];

        return $this->storage->save($tokens);
    }

    /**
     * CURL so'rov yuborish
     * 
     * @param string $url
     * @param array $data
     * @return array|null
     */
    private function makeRequest(string $url, array $data): ?array
    {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('CURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("HTTP error $httpCode: $response");
            return null;
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            return null;
        }

        return $result;
    }
}
