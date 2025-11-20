<?php

namespace App\Controllers;

use App\AmoClientV2;
use App\Helpers\Response;
use AmoCRM\Helpers\EntityTypesInterface;

/**
 * InfoControllerV2
 * 
 * AmoCRM ma'lumotlarini olish (yangi kutubxona bilan)
 */
class InfoControllerV2
{
    private AmoClientV2 $client;

    public function __construct()
    {
        $this->client = AmoClientV2::getInstance();
    }

    /**
     * Barcha pipeline'larni olish
     * GET /api/v1/info/pipelines
     */
    public function getPipelines(): void
    {
        try {
            $pipelines = $this->client->pipelines()->get();
            
            $result = [];
            foreach ($pipelines as $pipeline) {
                $result[] = $pipeline->toArray();
            }

            Response::success(
                ['_embedded' => ['pipelines' => $result]],
                'Pipelines retrieved successfully'
            );
        } catch (\AmoCRM\Exceptions\AmoCRMApiException $e) {
            error_log('AmoCRM API Error: ' . $e->getMessage());
            Response::error(
                'Failed to get pipelines from AmoCRM',
                [
                    'error' => $e->getTitle(),
                    'description' => $e->getDescription(),
                ],
                $e->getCode() ?: 500
            );
        } catch (\Throwable $e) {
            error_log('Unexpected error: ' . $e->getMessage());
            Response::error('Internal server error', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bitta pipeline olish
     * GET /api/v1/info/pipelines/{id}
     */
    public function getPipelineById(int $pipelineId): void
    {
        try {
            $pipeline = $this->client->pipelines()->getOne($pipelineId);
            
            Response::success($pipeline->toArray(), 'Pipeline retrieved successfully');
        } catch (\AmoCRM\Exceptions\AmoCRMApiException $e) {
            error_log('AmoCRM API Error: ' . $e->getMessage());
            Response::error(
                'Failed to get pipeline from AmoCRM',
                [
                    'error' => $e->getTitle(),
                    'description' => $e->getDescription(),
                ],
                $e->getCode() ?: 500
            );
        } catch (\Throwable $e) {
            error_log('Unexpected error: ' . $e->getMessage());
            Response::error('Internal server error', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Lead custom fields olish
     * GET /api/v1/info/lead-fields
     */
    public function getLeadFields(): void
    {
        try {
            $customFields = $this->client->customFields()->get(EntityTypesInterface::LEADS);
            
            $result = [];
            foreach ($customFields as $field) {
                $result[] = $field->toArray();
            }

            Response::success(
                ['_embedded' => ['custom_fields' => $result]],
                'Lead fields retrieved successfully'
            );
        } catch (\AmoCRM\Exceptions\AmoCRMApiException $e) {
            error_log('AmoCRM API Error: ' . $e->getMessage());
            Response::error(
                'Failed to get lead fields from AmoCRM',
                [
                    'error' => $e->getTitle(),
                    'description' => $e->getDescription(),
                ],
                $e->getCode() ?: 500
            );
        } catch (\Throwable $e) {
            error_log('Unexpected error: ' . $e->getMessage());
            Response::error('Internal server error', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Contact custom fields olish
     * GET /api/v1/info/contact-fields
     */
    public function getContactFields(): void
    {
        try {
            $customFields = $this->client->customFields()->get(EntityTypesInterface::CONTACTS);
            
            $result = [];
            foreach ($customFields as $field) {
                $result[] = $field->toArray();
            }

            Response::success(
                ['_embedded' => ['custom_fields' => $result]],
                'Contact fields retrieved successfully'
            );
        } catch (\AmoCRM\Exceptions\AmoCRMApiException $e) {
            error_log('AmoCRM API Error: ' . $e->getMessage());
            Response::error(
                'Failed to get contact fields from AmoCRM',
                [
                    'error' => $e->getTitle(),
                    'description' => $e->getDescription(),
                ],
                $e->getCode() ?: 500
            );
        } catch (\Throwable $e) {
            error_log('Unexpected error: ' . $e->getMessage());
            Response::error('Internal server error', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Account ma'lumotlari olish
     * GET /api/v1/info/account
     */
    public function getAccount(): void
    {
        try {
            $account = $this->client->account()->getCurrent();
            
            Response::success($account->toArray(), 'Account info retrieved successfully');
        } catch (\AmoCRM\Exceptions\AmoCRMApiException $e) {
            error_log('AmoCRM API Error: ' . $e->getMessage());
            Response::error(
                'Failed to get account info from AmoCRM',
                [
                    'error' => $e->getTitle(),
                    'description' => $e->getDescription(),
                ],
                $e->getCode() ?: 500
            );
        } catch (\Throwable $e) {
            error_log('Unexpected error: ' . $e->getMessage());
            Response::error('Internal server error', ['message' => $e->getMessage()], 500);
        }
    }
}
