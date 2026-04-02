<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiFormGenerator
{
    public function generate(string $prompt): array
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            return [
                'title' => 'Untitled Form',
                'description' => null,
                'fields' => [],
            ];
        }

        $openAiKey = (string) config('services.openai.key');

        if ($openAiKey !== '') {
            $generated = $this->generateWithOpenAi($prompt, $openAiKey);

            if ($generated !== null) {
                return $generated;
            }
        }

        return $this->generateFallback($prompt);
    }

    protected function generateWithOpenAi(string $prompt, string $key): ?array
    {
        try {
            $response = Http::withToken($key)
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Return only valid JSON with keys: title, description, fields. Each field must include type,label,placeholder,required,options,helper_text. Use simple form field types.',
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Generate a form blueprint for: '.$prompt,
                        ],
                    ],
                ]);

            $content = $response->json('choices.0.message.content');

            if (! is_string($content) || $content === '') {
                return null;
            }

            $decoded = $this->decodeJsonFromText($content);

            if (! is_array($decoded)) {
                return null;
            }

            return $this->normalizeBlueprint($decoded);
        } catch (Throwable) {
            return null;
        }
    }

    protected function generateFallback(string $prompt): array
    {
        $title = Str::title(Str::limit($prompt, 40, ''));
        $title = rtrim($title, '.');

        $fields = [];

        $fields[] = $this->field('text', 'Full Name', 'Enter your full name', true);

        if (Str::contains(Str::lower($prompt), ['email', 'contact', 'register', 'registration'])) {
            $fields[] = $this->field('email', 'Email Address', 'you@example.com', true);
        }

        if (Str::contains(Str::lower($prompt), ['phone', 'call', 'mobile'])) {
            $fields[] = $this->field('text', 'Phone Number', 'e.g. +1 555 123 4567', false);
        }

        if (Str::contains(Str::lower($prompt), ['date', 'schedule', 'appointment', 'event'])) {
            $fields[] = $this->field('date', 'Preferred Date', null, false);
        }

        if (Str::contains(Str::lower($prompt), ['feedback', 'comment', 'message', 'request'])) {
            $fields[] = $this->field('textarea', 'Message', 'Tell us more', false);
        }

        if (count($fields) < 3) {
            $fields[] = $this->field('textarea', 'Additional Notes', 'Share any extra details', false);
        }

        return [
            'title' => $title !== '' ? $title : 'Generated Form',
            'description' => 'AI-generated form blueprint based on your prompt.',
            'fields' => $fields,
        ];
    }

    protected function normalizeBlueprint(array $payload): array
    {
        $title = (string) ($payload['title'] ?? 'Generated Form');
        $description = $payload['description'] ?? null;

        $fields = collect($payload['fields'] ?? [])
            ->filter(fn ($field) => is_array($field))
            ->map(function (array $field): array {
                $type = (string) ($field['type'] ?? 'text');

                return [
                    'type' => in_array($type, ['text', 'email', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date', 'file'], true) ? $type : 'text',
                    'label' => (string) ($field['label'] ?? 'Untitled Field'),
                    'placeholder' => $field['placeholder'] !== null ? (string) $field['placeholder'] : null,
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => is_array($field['options'] ?? null) ? array_values($field['options']) : [],
                    'helper_text' => $field['helper_text'] !== null ? (string) $field['helper_text'] : null,
                ];
            })
            ->values()
            ->all();

        return [
            'title' => $title !== '' ? $title : 'Generated Form',
            'description' => is_string($description) ? $description : null,
            'fields' => $fields,
        ];
    }

    protected function decodeJsonFromText(string $text): ?array
    {
        $trimmed = trim($text);

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $text, $matches) === 1) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function field(string $type, string $label, ?string $placeholder, bool $required): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'placeholder' => $placeholder,
            'required' => $required,
            'options' => [],
            'helper_text' => null,
        ];
    }
}
