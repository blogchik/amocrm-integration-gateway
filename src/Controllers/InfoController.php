<?php

namespace App\Controllers;

use App\AmoClient;
use App\Helpers\Response;

/**
 * InfoController
 * 
 * AmoCRM ma'lumotlarini olish uchun endpoint
 */
class InfoController
{
    private AmoClient $client;

    public function __construct()
    {
        $this->client = new AmoClient();
    }

    /**
     * Barcha pipeline'larni olish
     * GET /api/v1/info/pipelines
     */
    public function getPipelines(): void
    {
        $result = $this->client->get('/api/v4/leads/pipelines');

        if (!$result['success']) {
            error_log('Failed to get pipelines: ' . json_encode($result));
            Response::error(
                'Failed to get pipelines from AmoCRM',
                [
                    'amocrm_error' => $result['error'] ?? 'Unknown error',
                    'http_code' => $result['http_code'] ?? null,
                ],
                500
            );
            return;
        }

        Response::success($result['data'], 'Pipelines retrieved successfully');
    }

    /**
     * Custom fields (lead properties) olish
     * GET /api/v1/info/lead-fields
     */
    public function getLeadFields(): void
    {
        $result = $this->client->get('/api/v4/leads/custom_fields');

        if (!$result['success']) {
            error_log('Failed to get lead fields: ' . json_encode($result));
            Response::error(
                'Failed to get lead fields from AmoCRM',
                [
                    'amocrm_error' => $result['error'] ?? 'Unknown error',
                    'http_code' => $result['http_code'] ?? null,
                ],
                500
            );
            return;
        }

        Response::success($result['data'], 'Lead fields retrieved successfully');
    }

    /**
     * Contact custom fields olish
     * GET /api/v1/info/contact-fields
     */
    public function getContactFields(): void
    {
        $result = $this->client->get('/api/v4/contacts/custom_fields');

        if (!$result['success']) {
            error_log('Failed to get contact fields: ' . json_encode($result));
            Response::error(
                'Failed to get contact fields from AmoCRM',
                [
                    'amocrm_error' => $result['error'] ?? 'Unknown error',
                    'http_code' => $result['http_code'] ?? null,
                ],
                500
            );
            return;
        }

        Response::success($result['data'], 'Contact fields retrieved successfully');
    }

    /**
     * Account ma'lumotlari olish
     * GET /api/v1/info/account
     */
    public function getAccount(): void
    {
        $result = $this->client->get('/api/v4/account');

        if (!$result['success']) {
            error_log('Failed to get account info: ' . json_encode($result));
            Response::error(
                'Failed to get account info from AmoCRM',
                [
                    'amocrm_error' => $result['error'] ?? 'Unknown error',
                    'http_code' => $result['http_code'] ?? null,
                ],
                500
            );
            return;
        }

        Response::success($result['data'], 'Account info retrieved successfully');
    }

    /**
     * Bitta pipeline'ning statuslarini olish
     * GET /api/v1/info/pipelines/{id}
     */
    public function getPipelineById(int $pipelineId): void
    {
        $result = $this->client->get("/api/v4/leads/pipelines/{$pipelineId}");

        if (!$result['success']) {
            error_log("Failed to get pipeline {$pipelineId}: " . json_encode($result));
            Response::error(
                'Failed to get pipeline from AmoCRM',
                [
                    'amocrm_error' => $result['error'] ?? 'Unknown error',
                    'http_code' => $result['http_code'] ?? null,
                ],
                500
            );
            return;
        }

        Response::success($result['data'], 'Pipeline retrieved successfully');
    }
}
