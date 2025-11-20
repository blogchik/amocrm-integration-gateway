<?php

namespace App\OAuth;

use AmoCRM\OAuth\OAuthConfigInterface;

/**
 * AmoCRM OAuth Configuration
 * 
 * OAuth sozlamalarini beradi
 */
class AmoOAuthConfig implements OAuthConfigInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $baseDomain;

    public function __construct()
    {
        // Config allaqachon index.php da yuklanadi
        $this->clientId = \App\Config::get('AMO_CLIENT_ID');
        $this->clientSecret = \App\Config::get('AMO_CLIENT_SECRET');
        $this->redirectUri = \App\Config::get('AMO_REDIRECT_URI');
        $this->baseDomain = \App\Config::get('AMO_DOMAIN');
    }

    /**
     * @return string
     */
    public function getIntegrationId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getRedirectDomain(): string
    {
        return $this->redirectUri;
    }

    /**
     * AmoCRM account domain (subdomain.amocrm.ru)
     * @return string
     */
    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }
}
