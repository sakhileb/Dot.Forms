<?php

namespace App\Services;

use App\Services\Ai\AiCrmFieldMapper;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Http;
use Throwable;

class FormSubmissionIntegrationDispatcher
{
    public function __construct(protected AiCrmFieldMapper $crmMapper)
    {
    }

    public function dispatch(Form $form, FormSubmission $submission): void
    {
        $settings = is_array($form->settings) ? $form->settings : [];
        $crmProvider = (string) ($settings['crm_provider'] ?? 'generic');

        $targets = array_filter([
            'webhook' => $settings['webhook_url'] ?? null,
            'slack' => $settings['slack_webhook_url'] ?? null,
            'zapier' => $settings['zapier_webhook_url'] ?? null,
            'make' => $settings['make_webhook_url'] ?? null,
            'crm' => $settings['crm_webhook_url'] ?? null,
        ]);

        if ($targets === []) {
            return;
        }

        $payload = [
            'event' => 'form.submitted',
            'form' => [
                'id' => $form->id,
                'title' => $form->title,
                'slug' => $form->slug,
            ],
            'submission' => [
                'id' => $submission->id,
                'submitted_at' => optional($submission->submitted_at)?->toIso8601String(),
                'ip_address' => $submission->ip_address,
                'user_agent' => $submission->user_agent,
                'data' => $submission->data,
                'resolved_data' => $this->resolvedData($form, $submission),
            ],
            'crm' => $this->crmMapper->map($form, $submission, $crmProvider),
        ];

        foreach ($targets as $url) {
            try {
                Http::timeout(8)->post((string) $url, $payload);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    protected function resolvedData(Form $form, FormSubmission $submission): array
    {
        $resolved = [];

        foreach ($form->fields as $field) {
            $resolved[$field->label] = $submission->data[$field->id] ?? null;
        }

        return $resolved;
    }
}
