<?php

namespace App;

/**
 * AmoTokenStorage Class
 * 
 * AmoCRM tokenlarni faylda saqlash va o'qish
 */
class AmoTokenStorage
{
    private string $filePath;

    public function __construct()
    {
        $storagePath = Config::get('token_storage_path', './storage/tokens.json');
        
        // Handle relative paths
        if (strpos($storagePath, './') === 0) {
            $storagePath = dirname(__DIR__) . '/' . substr($storagePath, 2);
        }
        
        $this->filePath = $storagePath;
        
        // Agar fayl bo'lmasa, bo'sh struktura yaratish
        if (!file_exists($this->filePath)) {
            $this->save([
                'access_token' => '',
                'refresh_token' => '',
                'expires_at' => 0
            ]);
        }
    }

    /**
     * Tokenlarni o'qish
     * 
     * @return array|null
     */
    public function load(): ?array
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Token storage JSON decode error: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }

    /**
     * Tokenlarni saqlash
     * 
     * @param array $tokens
     * @return bool
     */
    public function save(array $tokens): bool
    {
        $json = json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Token storage JSON encode error: ' . json_last_error_msg());
            return false;
        }

        $result = file_put_contents($this->filePath, $json);
        
        if ($result === false) {
            error_log('Failed to write tokens to file: ' . $this->filePath);
            return false;
        }

        return true;
    }

    /**
     * Tokenning muddati tugaganmi?
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        $tokens = $this->load();
        
        if (!$tokens || empty($tokens['expires_at'])) {
            return true;
        }

        // 60 soniya oldin yangilaymiz (buffer)
        return time() >= ($tokens['expires_at'] - 60);
    }

    /**
     * Access token olish
     * 
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        $tokens = $this->load();
        return $tokens['access_token'] ?? null;
    }

    /**
     * Refresh token olish
     * 
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        $tokens = $this->load();
        return $tokens['refresh_token'] ?? null;
    }
}
