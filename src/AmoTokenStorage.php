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
     * Tokenlarni o'qish (file lock bilan)
     * 
     * @return array|null
     */
    public function load(): ?array
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $fp = fopen($this->filePath, 'r');
        if ($fp === false) {
            error_log('Failed to open token file for reading: ' . $this->filePath);
            return null;
        }

        // Shared lock (multiple readers allowed)
        if (flock($fp, LOCK_SH)) {
            $content = '';
            while (!feof($fp)) {
                $content .= fread($fp, 8192);
            }
            flock($fp, LOCK_UN);
            fclose($fp);

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Token storage JSON decode error: ' . json_last_error_msg());
                return null;
            }

            return $data;
        } else {
            error_log('Failed to acquire lock on token file');
            fclose($fp);
            return null;
        }
    }

    /**
     * Tokenlarni saqlash (exclusive file lock bilan)
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

        // Directory mavjudligini tekshirish
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Exclusive lock bilan yozish
        $fp = fopen($this->filePath, 'c');
        if ($fp === false) {
            error_log('Failed to open token file for writing: ' . $this->filePath);
            return false;
        }

        if (flock($fp, LOCK_EX)) {
            // Truncate file and write
            ftruncate($fp, 0);
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            
            // Set proper permissions
            chmod($this->filePath, 0600);
            return true;
        } else {
            error_log('Failed to acquire exclusive lock on token file');
            fclose($fp);
            return false;
        }
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
        
        if (!$tokens || empty($tokens['access_token'])) {
            return null;
        }
        
        // Token formatini tekshirish (JWT bo'lishi kerak)
        $token = $tokens['access_token'];
        if (substr_count($token, '.') !== 2) {
            error_log('Access token format is invalid (not a JWT)');
            return null;
        }
        
        return $token;
    }

    /**
     * Refresh token olish
     * 
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        $tokens = $this->load();
        
        if (!$tokens || empty($tokens['refresh_token'])) {
            return null;
        }
        
        return $tokens['refresh_token'];
    }
    
    /**
     * Token ma'lumotlarini validatsiya qilish
     * 
     * @return array Validation holati
     */
    public function validateTokens(): array
    {
        $tokens = $this->load();
        
        if (!$tokens) {
            return ['valid' => false, 'error' => 'Tokens file not found or empty'];
        }
        
        if (empty($tokens['access_token'])) {
            return ['valid' => false, 'error' => 'Access token is empty'];
        }
        
        if (empty($tokens['refresh_token'])) {
            return ['valid' => false, 'error' => 'Refresh token is empty'];
        }
        
        if (empty($tokens['expires_at']) || !is_numeric($tokens['expires_at'])) {
            return ['valid' => false, 'error' => 'Invalid expires_at value'];
        }
        
        // Access token JWT formatini tekshirish
        if (substr_count($tokens['access_token'], '.') !== 2) {
            return ['valid' => false, 'error' => 'Access token is not a valid JWT'];
        }
        
        // Muddati tugagan bo'lsa ham, refresh token mavjud ekan deb belgilaymiz
        $expired = $this->isExpired();
        
        return [
            'valid' => true,
            'expired' => $expired,
            'expires_at' => $tokens['expires_at'],
            'expires_in' => $tokens['expires_at'] - time(),
        ];
    }
}
