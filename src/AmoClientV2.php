<?php

namespace App;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Client\AmoCRMApiClientFactory;
use App\OAuth\AmoOAuthConfig;
use App\OAuth\AmoOAuthService;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * AmoClientV2 Class
 * 
 * AmoCRM rasmiy kutubxonasi bilan ishlash uchun wrapper
 * Avtomatik token refresh, xatolar bilan ishlash
 */
class AmoClientV2
{
    private AmoCRMApiClient $apiClient;
    private AmoOAuthService $oauthService;
    private static ?self $instance = null;

    private function __construct()
    {
        $this->oauthService = new AmoOAuthService();
        
        // Factory orqali API client yaratish
        $oauthConfig = new AmoOAuthConfig();
        $factory = new AmoCRMApiClientFactory($oauthConfig, $this->oauthService);
        $this->apiClient = $factory->make();

        // Token'ni yuklash va o'rnatish
        $this->initializeToken();
    }

    /**
     * Singleton pattern
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Token'ni boshlang'ich o'rnatish
     */
    private function initializeToken(): void
    {
        $accessToken = $this->oauthService->getOAuthToken();
        
        if ($accessToken === null) {
            error_log('No OAuth token found. Please authorize first.');
            return;
        }

        // Base domain .env dan olish
        $baseDomain = Config::get('AMO_DOMAIN');
        if (empty($baseDomain)) {
            error_log('AMO_DOMAIN not found in .env file');
            return;
        }

        try {
            $this->apiClient->setAccessToken($accessToken)
                ->setAccountBaseDomain($baseDomain);
            
            // Agar storage'da base_domain noto'g'ri bo'lsa, yangilash
            $storedBaseDomain = $this->oauthService->getBaseDomain();
            if ($storedBaseDomain !== $baseDomain) {
                error_log("Updating base_domain from '$storedBaseDomain' to '$baseDomain'");
                $this->oauthService->saveOAuthToken($accessToken, $baseDomain);
            }
            
            error_log('AmoCRM API Client initialized successfully with domain: ' . $baseDomain);
        } catch (\Throwable $e) {
            error_log('Failed to initialize API client: ' . $e->getMessage());
        }
    }

    /**
     * AmoCRM API client'ni olish
     * 
     * @return AmoCRMApiClient
     */
    public function getClient(): AmoCRMApiClient
    {
        return $this->apiClient;
    }

    /**
     * Authorization code orqali token olish
     * 
     * @param string $code
     * @return bool
     */
    public function getTokenByCode(string $code): bool
    {
        try {
            $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode($code);
            
            if (!$accessToken) {
                error_log('Failed to get access token by code');
                return false;
            }

            // Base domain .env dan olish
            $baseDomain = Config::get('AMO_DOMAIN');

            // Token'ni saqlash
            $this->oauthService->saveOAuthToken($accessToken, $baseDomain);
            
            // API client'ga o'rnatish
            $this->apiClient->setAccessToken($accessToken)
                ->setAccountBaseDomain($baseDomain);

            error_log('Token obtained and saved successfully');
            return true;
        } catch (\Throwable $e) {
            error_log('Error getting token by code: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Leads service'ni olish
     * 
     * @return \AmoCRM\EntitiesServices\Leads
     */
    public function leads()
    {
        return $this->apiClient->leads();
    }

    /**
     * Contacts service'ni olish
     * 
     * @return \AmoCRM\EntitiesServices\Contacts
     */
    public function contacts()
    {
        return $this->apiClient->contacts();
    }

    /**
     * Companies service'ni olish
     * 
     * @return \AmoCRM\EntitiesServices\Companies
     */
    public function companies()
    {
        return $this->apiClient->companies();
    }

    /**
     * Pipelines service'ni olish
     * 
     * @return \AmoCRM\EntitiesServices\Pipelines
     */
    public function pipelines()
    {
        return $this->apiClient->pipelines();
    }

    /**
     * Custom fields service'ni olish
     * 
     * @param string $entityType Entity type (EntityTypesInterface::LEADS, CONTACTS, etc.)
     * @return \AmoCRM\EntitiesServices\CustomFields
     */
    public function customFields(string $entityType)
    {
        return $this->apiClient->customFields($entityType);
    }

    /**
     * Account service'ni olish
     * 
     * @return \AmoCRM\EntitiesServices\Account
     */
    public function account()
    {
        return $this->apiClient->account();
    }

    /**
     * Unsorted service'ni olish
     * 
     * @return \AmoCRM\EntitiesServices\Unsorted
     */
    public function unsorted()
    {
        return $this->apiClient->unsorted();
    }

    /**
     * Authorization URL olish
     * 
     * @param array $options
     * @return string
     */
    public function getAuthorizationUrl(array $options = []): string
    {
        return $this->apiClient->getOAuthClient()->getAuthorizeUrl($options);
    }

    /**
     * Token mavjudligini tekshirish
     * 
     * @return bool
     */
    public function hasToken(): bool
    {
        return $this->oauthService->getOAuthToken() !== null;
    }
}
