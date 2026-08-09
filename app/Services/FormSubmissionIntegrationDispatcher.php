<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Ai\AiCrmFieldMapper;
use App\Support\SsrfGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FormSubmissionIntegrationDispatcher
{
    public function __construct(protected AiCrmFieldMapper $crmMapper) {}

    public function dispatch(Form $form, FormSubmission $submission): void
    {
        $settings = is_array($form->settings) ? $form->settings : [];
        $crmProvider = (string) ($settings['crm_provider'] ?? 'generic');

        $targets = $form->integrations()
            ->where('status', 'active')
            ->pluck('url', 'type')
            ->all();

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
            $url = (string) $url;

            // Re-checked here, not just at settings-save time: DNS for a
            // saved hostname can change between save and dispatch (a classic
            // SSRF bypass via DNS rebinding), so a URL that was safe when a
            // team configured it must still be safe right before we use it.
            if (! SsrfGuard::isSafeUrl($url)) {
                Log::warning('Form webhook dispatch blocked: target resolves to a non-public address', [
                    'form_id' => $form->id,
                    'url' => $url,
                ]);

                continue;
            }

            try {
                Http::timeout(8)->withOptions(['allow_redirects' => false])->post($url, $payload);
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
