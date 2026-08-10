<?php

namespace App\Services;

use App\Models\FormField;
use Illuminate\Support\Collection;

/**
 * Evaluates a field's visibility_rule (see Builder::persist()) against the
 * current set of answers -- the real, functional counterpart to the
 * free-text "conditional_logic" note, which was only ever a description
 * shown to the form builder and never actually evaluated anywhere.
 *
 * A rule references its trigger field by a stable key (FormField's own
 * options['field_key']), not a DB id, because every field is deleted and
 * recreated on each Builder save -- ids don't survive that, keys do.
 */
class FormFieldVisibilityEvaluator
{
    private const OPERATORS = ['equals', 'not_equals', 'contains'];

    /**
     * @param  Collection<int, FormField>  $allFields  Every field on the form (to resolve the trigger by key).
     * @param  array<int, mixed>  $answers  Current answers keyed by field id.
     */
    public function isVisible(FormField $field, Collection $allFields, array $answers): bool
    {
        $rule = $field->options['visibility_rule'] ?? null;

        if (! is_array($rule) || empty($rule['trigger_field_key'])) {
            return true;
        }

        $operator = in_array($rule['operator'] ?? null, self::OPERATORS, true) ? $rule['operator'] : 'equals';
        $expected = (string) ($rule['value'] ?? '');

        $triggerField = $allFields->first(
            fn (FormField $candidate) => ($candidate->options['field_key'] ?? null) === $rule['trigger_field_key']
        );

        if (! $triggerField) {
            // The trigger field no longer exists (e.g. deleted since the
            // rule was set) -- fail open rather than permanently hide a
            // field the respondent can no longer make visible.
            return true;
        }

        $actual = $this->normalize($answers[$triggerField->id] ?? null);
        $expectedNormalized = $this->normalize($expected);

        return match ($operator) {
            'not_equals' => $actual !== $expectedNormalized,
            'contains' => $expectedNormalized !== '' && str_contains($actual, $expectedNormalized),
            default => $actual === $expectedNormalized, // equals
        };
    }

    private function normalize(mixed $value): string
    {
        if (is_array($value)) {
            // Checkbox fields can submit multiple values.
            return strtolower(implode(',', array_map('strval', $value)));
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return strtolower(trim((string) ($value ?? '')));
    }
}
