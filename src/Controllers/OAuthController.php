<?php

namespace App\Controllers;

use App\AmoClientV2;
use App\Helpers\Response;

/**
 * OAuthController
 * 
 * AmoCRM OAuth avtorizatsiya uchun
 */
class OAuthController
{
    private AmoClientV2 $client;

    public function __construct()
    {
        $this->client = AmoClientV2::getInstance();
    }

    /**
     * Authorization URL'ga redirect qilish
     * GET /oauth/authorize
     */
    public function authorize(): void
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $authUrl = $this->client->getAuthorizationUrl(['state' => $state]);
        
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * OAuth callback
     * GET /oauth/callback?code=xxx&state=xxx
     */
    public function callback(): void
    {
        // State validation
        if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
            Response::error('Invalid state parameter', [], 400);
            return;
        }

        unset($_SESSION['oauth_state']);

        // Authorization code
        if (empty($_GET['code'])) {
            Response::error('Authorization code not provided', [], 400);
            return;
        }

        $code = $_GET['code'];

        // Token olish va saqlash
        $success = $this->client->getTokenByCode($code);

        if ($success) {
            Response::success(
                ['message' => 'Authorization successful'],
                'OAuth token received and saved'
            );
        } else {
            Response::error('Failed to get OAuth token', [], 500);
        }
    }

    /**
     * Token status tekshirish
     * GET /oauth/status
     */
    public function status(): void
    {
        $hasToken = $this->client->hasToken();

        Response::success([
            'has_token' => $hasToken,
            'status' => $hasToken ? 'authorized' : 'not_authorized'
        ]);
    }
}
