<?php

namespace App\OAuth;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use AmoCRM\OAuth\OAuthServiceInterface;

/**
 * AmoCRM OAuth Service Implementation
 * 
 * Token'larni saqlash va boshqarish uchun
 */
class AmoOAuthService implements OAuthServiceInterface
{
    private string $filePath;

    public function __construct()
    {
        $storagePath = \App\Config::get('token_storage_path', './storage/tokens.json');
        
        // Handle relative paths
        if (strpos($storagePath, './') === 0) {
            $storagePath = dirname(__DIR__, 2) . '/' . substr($storagePath, 2);
        }
        
        $this->filePath = $storagePath;
        
        // Ensure directory exists
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * OAuth token'ni saqlash
     * 
     * @param AccessTokenInterface $accessToken
     * @param string $baseDomain
     * @return void
     */
    public function saveOAuthToken(AccessTokenInterface $accessToken, string $baseDomain): void
    {
        $data = [
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $accessToken->getRefreshToken(),
            'expires_at' => $accessToken->getExpires(),
            'base_domain' => $baseDomain,
            'updated_at' => time(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('OAuth token JSON encode error: ' . json_last_error_msg());
            return;
        }

        // Exclusive lock bilan yozish
        $fp = fopen($this->filePath, 'c');
        if ($fp === false) {
            error_log('Failed to open token file for writing: ' . $this->filePath);
            return;
        }

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            chmod($this->filePath, 0600);
            
            error_log('OAuth token saved successfully. Expires at: ' . date('Y-m-d H:i:s', $accessToken->getExpires()));
        } else {
            error_log('Failed to acquire lock for saving OAuth token');
            fclose($fp);
        }
    }

    /**
     * Token'ni yuklash
     * 
     * @return AccessTokenInterface|null
     */
    public function getOAuthToken(): ?AccessTokenInterface
    {
        if (!file_exists($this->filePath)) {
            error_log('Token file does not exist: ' . $this->filePath);
            return null;
        }

        $fp = fopen($this->filePath, 'r');
        if ($fp === false) {
            error_log('Failed to open token file for reading: ' . $this->filePath);
            return null;
        }

        if (flock($fp, LOCK_SH)) {
            $content = '';
            while (!feof($fp)) {
                $content .= fread($fp, 8192);
            }
            flock($fp, LOCK_UN);
            fclose($fp);

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Token JSON decode error: ' . json_last_error_msg());
                return null;
            }

            if (empty($data['access_token']) || empty($data['refresh_token'])) {
                error_log('Token data incomplete');
                return null;
            }

            // AccessToken obyektini yaratish
            $tokenData = [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires' => $data['expires_at'] ?? 0,
                'baseDomain' => $data['base_domain'] ?? '',
            ];

            return new AccessToken($tokenData);
        }

        error_log('Failed to acquire lock for reading OAuth token');
        fclose($fp);
        return null;
    }

    /**
     * Base domain olish
     * 
     * @return string|null
     */
    public function getBaseDomain(): ?string
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data['base_domain'] ?? null;
    }
}
