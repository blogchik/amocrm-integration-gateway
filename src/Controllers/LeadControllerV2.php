<?php

namespace App\Controllers;

use App\AmoClientV2;
use App\Helpers\Response;
use AmoCRM\Models\Unsorted\FormsMetadata;
use AmoCRM\Models\Unsorted\FormUnsortedModel;
use AmoCRM\Collections\Leads\Unsorted\FormsUnsortedCollection;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\MultiselectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultiselectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultiselectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Models\TagModel;
use AmoCRM\Models\NoteType\CommonNote;

/**
 * LeadControllerV2
 * 
 * AmoCRM rasmiy kutubxonasi bilan lead yaratish
 */
class LeadControllerV2
{
    private AmoClientV2 $client;

    public function __construct()
    {
        $this->client = AmoClientV2::getInstance();
    }

    /**
     * Unsorted lead yaratish
     * POST /api/v1/leads/unsorted
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

        try {
            // Unsorted modelini yaratish
            $unsortedForm = $this->buildUnsortedForm($requestData);
            
            error_log('About to send to AmoCRM: ' . json_encode([
                'source_name' => $unsortedForm->getSourceName(),
                'lead_name' => $unsortedForm->getLead()?->getName(),
                'contacts_count' => $unsortedForm->getContacts()?->count(),
                'category' => $unsortedForm->getCategory(),
            ]));
            
            // AmoCRM'ga yuborish (addOne ishlatamiz)
            $addedForm = $this->client->unsorted()->addOne($unsortedForm);
            
            error_log('Response received from AmoCRM.');
            
            if ($addedForm) {
                Response::success(
                    [
                        'uid' => $addedForm->getUid(),
                        'source_uid' => $addedForm->getSourceUid(),
                    ],
                    'Unsorted lead created successfully'
                );
            } else {
                Response::error('Failed to create unsorted lead', [], 500);
            }
        } catch (\AmoCRM\Exceptions\AmoCRMApiException $e) {
            $exceptionClass = get_class($e);
            error_log('AmoCRM API Exception class: ' . $exceptionClass);
            error_log('AmoCRM API Error getMessage: "' . $e->getMessage() . '"');
            error_log('Error title: "' . ($e->getTitle() ?? 'null') . '"');
            error_log('Error description: "' . ($e->getDescription() ?? 'null') . '"');
            error_log('Error code: ' . $e->getErrorCode());
            error_log('HTTP Code: ' . $e->getCode());
            
            Response::error(
                'Failed to create lead in AmoCRM',
                [
                    'exception_class' => $exceptionClass,
                    'message' => $e->getMessage(),
                    'error' => $e->getTitle() ?: $e->getMessage(),
                    'description' => $e->getDescription(),
                    'code' => $e->getErrorCode(),
                    'http_code' => $e->getCode(),
                ],
                $e->getCode() ?: 500
            );
        } catch (\Throwable $e) {
            error_log('Unexpected error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            Response::error(
                'Internal server error',
                ['message' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Request validatsiya
     */
    private function validateUnsortedRequest(?array $data): array
    {
        $errors = [];

        if ($data === null) {
            $errors[] = 'Request body is required';
            return $errors;
        }

        // Lead validatsiya
        if (empty($data['lead']) || !is_array($data['lead'])) {
            $errors['lead'] = 'Lead object is required';
        } else {
            if (empty($data['lead']['name'])) {
                $errors['lead.name'] = 'Lead name is required';
            }
        }

        // Contact validatsiya
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
     * Unsorted form modelini yaratish
     */
    private function buildUnsortedForm(array $data): FormUnsortedModel
    {
        // Metadata yaratish
        $metadata = new FormsMetadata();
        $formName = $data['form_name'] ?? 'Gateway Form';
        
        $metadata->setFormId(md5($formName))
            ->setFormName($formName)
            ->setFormPage($data['form_page'] ?? 'https://gateway.example.com')
            ->setFormSentAt(time())
            ->setReferer($data['referer'] ?? 'https://gateway.example.com');

        if (!empty($data['ip'])) {
            $metadata->setIp($data['ip']);
        }

        // Lead yaratish
        $lead = new LeadModel();
        $lead->setName($data['lead']['name']);

        // Lead price
        if (!empty($data['lead']['price'])) {
            $lead->setPrice((int)$data['lead']['price']);
        }

        // Lead custom fields
        if (!empty($data['lead']['custom_fields']) && is_array($data['lead']['custom_fields'])) {
            $customFields = $this->buildCustomFields($data['lead']['custom_fields']);
            if ($customFields->count() > 0) {
                $lead->setCustomFieldsValues($customFields);
            }
        }

        // Lead tags
        if (!empty($data['lead']['tags']) && is_array($data['lead']['tags'])) {
            $tags = $this->buildTags($data['lead']['tags']);
            if ($tags->count() > 0) {
                $lead->setTags($tags);
            }
        }

        // Contact yaratish
        $contact = new ContactModel();
        
        if (!empty($data['contact']['name'])) {
            $contact->setName($data['contact']['name']);
        }

        // Contact fields (phone, email) - sodda format
        $contactFields = new CustomFieldsValuesCollection();

        if (!empty($data['contact']['phone'])) {
            $phoneField = new MultitextCustomFieldValuesModel();
            $phoneField->setFieldCode('PHONE');
            $phoneField->setValues(
                (new MultitextCustomFieldValueCollection())
                    ->add((new MultitextCustomFieldValueModel())->setValue($data['contact']['phone']))
            );
            $contactFields->add($phoneField);
        }

        if (!empty($data['contact']['email'])) {
            $emailField = new MultitextCustomFieldValuesModel();
            $emailField->setFieldCode('EMAIL');
            $emailField->setValues(
                (new MultitextCustomFieldValueCollection())
                    ->add((new MultitextCustomFieldValueModel())->setValue($data['contact']['email']))
            );
            $contactFields->add($emailField);
        }

        if ($contactFields->count() > 0) {
            $contact->setCustomFieldsValues($contactFields);
        }

        // UTM va comment note sifatida
        $noteText = $this->buildNoteText($data);
        if (!empty($noteText)) {
            // Note qo'shish API orqali alohida qilish kerak
            // Hozircha metadata'ga qo'shamiz yoki lead name'ga
            error_log('Note text: ' . $noteText);
        }

        // Unsorted form yaratish
        $unsortedForm = new FormUnsortedModel();
        $unsortedForm->setSourceName($data['source'] ?? 'gateway')
            ->setSourceUid(uniqid('gw_', true))
            ->setCreatedAt(time())
            ->setMetadata($metadata);

        // Pipeline ID
        if (!empty($data['pipeline_id'])) {
            $unsortedForm->setPipelineId((int)$data['pipeline_id']);
        }

        // Lead va contact'ni embedded qismiga qo'shish
        $unsortedForm->setLead($lead);
        
        // Contact collection yaratish
        $contactsCollection = new ContactsCollection();
        $contactsCollection->add($contact);
        $unsortedForm->setContacts($contactsCollection);

        return $unsortedForm;
    }

    /**
     * Custom fields yaratish
     */
    private function buildCustomFields(array $customFieldsData): CustomFieldsValuesCollection
    {
        $collection = new CustomFieldsValuesCollection();

        foreach ($customFieldsData as $fieldData) {
            if (empty($fieldData['field_id'])) {
                continue;
            }

            $fieldId = (int)$fieldData['field_id'];

            // Text field
            if (isset($fieldData['value']) && is_string($fieldData['value'])) {
                $field = new TextCustomFieldValuesModel();
                $field->setFieldId($fieldId);
                $field->setValues(
                    (new TextCustomFieldValueCollection())
                        ->add((new TextCustomFieldValueModel())->setValue($fieldData['value']))
                );
                $collection->add($field);
            }
            // Numeric field
            elseif (isset($fieldData['value']) && is_numeric($fieldData['value'])) {
                $field = new NumericCustomFieldValuesModel();
                $field->setFieldId($fieldId);
                $field->setValues(
                    (new NumericCustomFieldValueCollection())
                        ->add((new NumericCustomFieldValueModel())->setValue($fieldData['value']))
                );
                $collection->add($field);
            }
            // Multiselect field (enum_ids)
            elseif (isset($fieldData['enum_ids']) && is_array($fieldData['enum_ids'])) {
                $field = new MultiselectCustomFieldValuesModel();
                $field->setFieldId($fieldId);
                
                $valueCollection = new MultiselectCustomFieldValueCollection();
                foreach ($fieldData['enum_ids'] as $enumId) {
                    $valueCollection->add(
                        (new MultiselectCustomFieldValueModel())->setEnumId((int)$enumId)
                    );
                }
                
                $field->setValues($valueCollection);
                $collection->add($field);
                
                error_log('Added multiselect field: ' . $fieldId . ' with enums: ' . json_encode($fieldData['enum_ids']));
            }
            // Single select field (enum_id)
            elseif (isset($fieldData['enum_id']) && is_numeric($fieldData['enum_id'])) {
                $field = new SelectCustomFieldValuesModel();
                $field->setFieldId($fieldId);
                $field->setValues(
                    (new SelectCustomFieldValueCollection())
                        ->add((new SelectCustomFieldValueModel())->setEnumId((int)$fieldData['enum_id']))
                );
                $collection->add($field);
            }
        }

        return $collection;
    }

    /**
     * Tags yaratish
     */
    private function buildTags(array $tagsData): TagsCollection
    {
        $collection = new TagsCollection();

        foreach ($tagsData as $tag) {
            if (is_string($tag)) {
                $collection->add((new TagModel())->setName($tag));
            } elseif (is_array($tag) && !empty($tag['name'])) {
                $collection->add((new TagModel())->setName($tag['name']));
            } elseif (is_array($tag) && !empty($tag['id'])) {
                $collection->add((new TagModel())->setId((int)$tag['id']));
            }
        }

        return $collection;
    }

    /**
     * Note text yaratish (UTM va comment)
     */
    private function buildNoteText(array $data): string
    {
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

        return trim($noteText);
    }
}
