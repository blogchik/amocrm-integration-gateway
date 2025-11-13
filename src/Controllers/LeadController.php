<?php

namespace App\Controllers;

use App\AmoClient;
use App\Helpers\Response;

/**
 * LeadController
 * 
 * Lead bilan bog'liq endpointlarni boshqaradi
 */
class LeadController
{
    private AmoClient $client;

    public function __construct()
    {
        $this->client = new AmoClient();
    }

    /**
     * Unsorted lead yaratish
     * POST /api/v1/leads/unsorted
     * 
     * Gateway formatidan AmoCRM unsorted formatiga convert qiladi
     */
    public function createUnsorted(): void
    {
        // Request body o'qish
        $rawBody = file_get_contents('php://input');
        $requestData = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON', ['json_error' => json_last_error_msg()]);
            return;
        }

        // Validatsiya
        $validationErrors = $this->validateUnsortedRequest($requestData);
        if (!empty($validationErrors)) {
            Response::validationError($validationErrors);
            return;
        }

        // AmoCRM unsorted formatini yaratish
        $amoPayload = $this->buildAmoUnsortedPayload($requestData);

        // AmoCRM'ga yuborish
        $result = $this->client->post('/api/v4/leads/unsorted/forms', $amoPayload);

        if (!$result['success']) {
            error_log('Failed to create unsorted lead: ' . json_encode($result));
            Response::error(
                'Failed to create lead in AmoCRM',
                [
                    'amocrm_error' => $result['error'] ?? 'Unknown error',
                    'http_code' => $result['http_code'] ?? null,
                ],
                500
            );
            return;
        }

        // Success response
        Response::success(
            $result['data'],
            'Unsorted lead created successfully'
        );
    }

    /**
     * Request validatsiya
     * 
     * @param array|null $data
     * @return array Xatolar ro'yxati
     */
    private function validateUnsortedRequest(?array $data): array
    {
        $errors = [];

        if ($data === null) {
            $errors[] = 'Request body is required';
            return $errors;
        }

        // Source validatsiya
        if (empty($data['source'])) {
            $errors['source'] = 'Source is required';
        }

        // Form name validatsiya
        if (empty($data['form_name'])) {
            $errors['form_name'] = 'Form name is required';
        }

        // Lead validatsiya
        if (empty($data['lead']) || !is_array($data['lead'])) {
            $errors['lead'] = 'Lead object is required';
        } else {
            if (empty($data['lead']['name'])) {
                $errors['lead.name'] = 'Lead name is required';
            }
        }

        // Contact validatsiya (kamida bitta field kerak)
        if (empty($data['contact']) || !is_array($data['contact'])) {
            $errors['contact'] = 'Contact object is required';
        } else {
            $hasContactField = !empty($data['contact']['name']) 
                || !empty($data['contact']['phone']) 
                || !empty($data['contact']['email']);
            
            if (!$hasContactField) {
                $errors['contact'] = 'At least one contact field (name, phone, email) is required';
            }
        }

        return $errors;
    }

    /**
     * Gateway formatidan AmoCRM formatiga convert qilish
     * 
     * @param array $data
     * @return array
     */
    private function buildAmoUnsortedPayload(array $data): array
    {
        // Base payload structure
        $payload = [
            'source_name' => $data['source'] ?? 'gateway',
            'source_uid' => uniqid('gw_', true),
            'created_at' => time(),
            '_embedded' => [
                'leads' => [],
                'contacts' => [],
            ],
        ];

        // Pipeline ID (ixtiyoriy, lekin unsorted uchun kerak emas odatda)
        if (!empty($data['pipeline_id'])) {
            $payload['pipeline_id'] = (int)$data['pipeline_id'];
        }

        // Lead qo'shish
        $lead = [
            'name' => $data['lead']['name'],
        ];

        if (!empty($data['lead']['price'])) {
            $lead['price'] = (int)$data['lead']['price'];
        }

        // Custom fields - universal (har qanday field qo'shish mumkin)
        if (!empty($data['lead']['custom_fields']) && is_array($data['lead']['custom_fields'])) {
            $lead['custom_fields_values'] = [];
            
            foreach ($data['lead']['custom_fields'] as $fieldData) {
                if (empty($fieldData['field_id'])) {
                    continue; // field_id yo'q bo'lsa o'tkazib yuboramiz
                }
                
                $customField = [
                    'field_id' => (int)$fieldData['field_id']
                ];
                
                // Agar enum_id'lar berilgan bo'lsa (multiselect, select)
                if (!empty($fieldData['enum_ids']) && is_array($fieldData['enum_ids'])) {
                    $customField['values'] = array_map(function($enumId) {
                        return ['enum_id' => (int)$enumId];
                    }, $fieldData['enum_ids']);
                }
                // Agar oddiy value berilgan bo'lsa (text, textarea, numeric)
                elseif (isset($fieldData['value'])) {
                    $customField['values'] = [
                        ['value' => $fieldData['value']]
                    ];
                }
                // Agar values array berilgan bo'lsa (multitext masalan)
                elseif (!empty($fieldData['values']) && is_array($fieldData['values'])) {
                    $customField['values'] = $fieldData['values'];
                }
                
                $lead['custom_fields_values'][] = $customField;
            }
        }

        // Tags qo'shish (n ta tag)
        if (!empty($data['lead']['tags']) && is_array($data['lead']['tags'])) {
            $lead['_embedded']['tags'] = [];
            
            foreach ($data['lead']['tags'] as $tag) {
                if (is_string($tag)) {
                    // Agar tag nomi berilgan bo'lsa
                    $lead['_embedded']['tags'][] = ['name' => $tag];
                } elseif (is_array($tag) && !empty($tag['name'])) {
                    // Agar tag object sifatida berilgan bo'lsa
                    $lead['_embedded']['tags'][] = ['name' => $tag['name']];
                } elseif (is_array($tag) && !empty($tag['id'])) {
                    // Agar tag ID berilgan bo'lsa
                    $lead['_embedded']['tags'][] = ['id' => (int)$tag['id']];
                }
            }
        }

        // UTM va comment'ni lead note sifatida qo'shamiz
        $noteText = '';
        
        if (!empty($data['utm']) && is_array($data['utm'])) {
            $utmParts = [];
            foreach ($data['utm'] as $key => $value) {
                $utmParts[] = "$key: $value";
            }
            $noteText .= "UTM:\n" . implode("\n", $utmParts) . "\n\n";
        }

        if (!empty($data['comment'])) {
            $noteText .= "Izoh: " . $data['comment'];
        }

        if (!empty($noteText)) {
            $lead['_embedded'] = [
                'notes' => [
                    [
                        'note_type' => 'common',
                        'params' => [
                            'text' => trim($noteText)
                        ]
                    ]
                ]
            ];
        }

        $payload['_embedded']['leads'][] = $lead;

        // Contact qo'shish
        $contact = [];

        if (!empty($data['contact']['name'])) {
            $contact['name'] = $data['contact']['name'];
        }

        // Contact custom fields
        $contactFields = [];

        if (!empty($data['contact']['phone'])) {
            $contactFields[] = [
                'field_code' => 'PHONE',
                'values' => [
                    [
                        'value' => $data['contact']['phone'],
                        'enum_code' => 'WORK',
                    ],
                ],
            ];
        }

        if (!empty($data['contact']['email'])) {
            $contactFields[] = [
                'field_code' => 'EMAIL',
                'values' => [
                    [
                        'value' => $data['contact']['email'],
                        'enum_code' => 'WORK',
                    ],
                ],
            ];
        }

        if (!empty($contactFields)) {
            $contact['custom_fields_values'] = $contactFields;
        }

        $payload['_embedded']['contacts'][] = $contact;

        // Metadata (faqat AmoCRM ruxsat bergan fieldlar)
        $formName = $data['form_name'] ?? 'Gateway Form';
        $metadata = [
            'form_id' => md5($formName),
            'form_name' => $formName,
            'form_page' => $data['form_page'] ?? 'https://gateway.example.com',
            'form_sent_at' => time(),
            'referer' => $data['referer'] ?? 'https://gateway.example.com',
        ];

        // IP address (ixtiyoriy)
        if (!empty($data['ip'])) {
            $metadata['ip'] = $data['ip'];
        }

        $payload['metadata'] = $metadata;

        return [$payload]; // AmoCRM array kutadi
    }
}
