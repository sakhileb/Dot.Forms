<?php

namespace App\Services\Ai;

use App\Models\Form;
use Illuminate\Support\Str;

class AiFieldSuggestionEngine
{
    public function suggestForForm(Form $form): array
    {
        $context = Str::lower(trim(($form->title ?? '').' '.($form->description ?? '')));

        $suggestions = [];

        if (Str::contains($context, ['event', 'registration', 'register'])) {
            $suggestions[] = $this->suggestion('text', 'Organization', 'Your organization name', false);
            $suggestions[] = $this->suggestion('select', 'Dietary Restrictions', null, false, ['None', 'Vegetarian', 'Vegan', 'Halal', 'Kosher', 'Other']);
        }

        if (Str::contains($context, ['feedback', 'survey', 'review'])) {
            $suggestions[] = $this->suggestion('radio', 'Overall Satisfaction', null, true, ['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied']);
            $suggestions[] = $this->suggestion('textarea', 'What can we improve?', null, false);
        }

        if (Str::contains($context, ['job', 'application', 'candidate'])) {
            $suggestions[] = $this->suggestion('file', 'Resume Upload', null, true);
            $suggestions[] = $this->suggestion('textarea', 'Cover Letter', null, false);
        }

        if ($suggestions === []) {
            $suggestions[] = $this->suggestion('email', 'Work Email', 'name@company.com', true);
            $suggestions[] = $this->suggestion('text', 'Phone Number', 'Optional contact number', false);
        }

        return $suggestions;
    }

    public function enhanceLabel(string $label): string
    {
        $value = preg_replace('/[_\-.]+/', ' ', $label) ?? $label;
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', $value) ?? $value;

        return Str::title(trim($value));
    }

    public function suggestConditionalLogic(array $field, ?array $previousField = null): string
    {
        if (! $previousField) {
            return 'Show when relevant based on prior answers.';
        }

        $prevLabel = $previousField['label'] ?? 'the previous field';

        if (($field['type'] ?? '') === 'textarea') {
            return 'Show when '.$prevLabel.' is not empty.';
        }

        if (($field['type'] ?? '') === 'file') {
            return 'Show when '.$prevLabel.' is answered with Yes.';
        }

        return 'Show when '.$prevLabel.' matches a specific option.';
    }

    protected function suggestion(string $type, string $label, ?string $placeholder, bool $required, array $options = []): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'placeholder' => $placeholder,
            'required' => $required,
            'options' => $options,
            'helper_text' => null,
        ];
    }
}
