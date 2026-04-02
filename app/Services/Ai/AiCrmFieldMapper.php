<?php

namespace App\Services\Ai;

use App\Models\Form;
use App\Models\FormSubmission;

class AiCrmFieldMapper
{
    public function map(Form $form, FormSubmission $submission, string $provider = 'generic'): array
    {
        $resolved = [];

        foreach ($form->fields as $field) {
            $label = strtolower($field->label);
            $value = $submission->data[$field->id] ?? null;
            $targetKey = $this->targetKey($label, $provider);
            $resolved[$targetKey] = $value;
        }

        return [
            'provider' => $provider,
            'mapped_fields' => $resolved,
        ];
    }

    protected function targetKey(string $label, string $provider): string
    {
        $map = [
            'name' => 'full_name',
            'full name' => 'full_name',
            'email' => 'email',
            'email address' => 'email',
            'phone' => 'phone',
            'phone number' => 'phone',
            'company' => 'company',
            'organization' => 'company',
            'message' => 'notes',
            'notes' => 'notes',
        ];

        foreach ($map as $source => $target) {
            if (str_contains($label, $source)) {
                return $provider.'_'.$target;
            }
        }

        return $provider.'_'.str_replace(' ', '_', trim($label));
    }
}
