<?php

namespace App\Controllers;

use App\AmoTokenStorage;
use App\Config;
use App\Helpers\Response;

/**
 * DiagnosticsController
 * 
 * Token holatini tekshirish va diagnostika
 */
class DiagnosticsController
{
    /**
     * Token holatini tekshirish
     * GET /api/v1/diagnostics/token-status
     */
    public function getTokenStatus(): void
    {
        $storage = new AmoTokenStorage();
        
        $validation = $storage->validateTokens();
        
        $statusInfo = [
            'token_validation' => $validation,
            'storage_path' => Config::get('token_storage_path'),
            'current_time' => time(),
            'current_datetime' => date('Y-m-d H:i:s'),
        ];
        
        if ($validation['valid']) {
            $statusInfo['access_token_preview'] = substr($storage->getAccessToken(), 0, 50) . '...';
            $statusInfo['refresh_token_length'] = strlen($storage->getRefreshToken());
            
            if (isset($validation['expires_at'])) {
                $statusInfo['expires_datetime'] = date('Y-m-d H:i:s', $validation['expires_at']);
            }
        }
        
        Response::success($statusInfo, 'Token diagnostics');
    }
    
    /**
     * AmoCRM konfiguratsiyasini tekshirish (xavfsiz)
     * GET /api/v1/diagnostics/config
     */
    public function getConfig(): void
    {
        $config = [
            'domain' => Config::get('amocrm.domain'),
            'client_id' => substr(Config::get('amocrm.client_id'), 0, 10) . '...',
            'redirect_uri' => Config::get('amocrm.redirect_uri'),
            'token_storage_path' => Config::get('token_storage_path'),
        ];
        
        Response::success($config, 'Configuration (sanitized)');
    }
}
