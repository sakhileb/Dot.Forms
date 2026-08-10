<?php

namespace Tests\Unit;

use App\Models\Form;
use App\Models\User;
use App\Services\FormFieldVisibilityEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormFieldVisibilityEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private function formWithFields(): array
    {
        $owner = User::factory()->withPersonalTeam()->create();

        $form = Form::query()->create([
            'team_id' => $owner->currentTeam->id,
            'user_id' => $owner->id,
            'title' => 'Conditional Form',
            'slug' => 'conditional-form-'.uniqid(),
            'settings' => [],
            'is_published' => false,
        ]);

        $trigger = $form->fields()->create([
            'type' => 'radio',
            'label' => 'Need support?',
            'options' => ['choices' => ['yes', 'no'], 'field_key' => 'trigger-key'],
            'validation_rules' => [],
            'order' => 1,
        ]);

        $dependent = $form->fields()->create([
            'type' => 'textarea',
            'label' => 'Describe the issue',
            'options' => [
                'field_key' => 'dependent-key',
                'visibility_rule' => ['trigger_field_key' => 'trigger-key', 'operator' => 'equals', 'value' => 'yes'],
            ],
            'validation_rules' => [],
            'order' => 2,
        ]);

        return [$form, $trigger, $dependent];
    }

    public function test_a_field_with_no_rule_is_always_visible(): void
    {
        [$form, $trigger, $dependent] = $this->formWithFields();

        $evaluator = new FormFieldVisibilityEvaluator;

        $this->assertTrue($evaluator->isVisible($trigger, $form->fields, []));
    }

    public function test_equals_operator_shows_the_field_only_on_a_match(): void
    {
        [$form, $trigger, $dependent] = $this->formWithFields();
        $evaluator = new FormFieldVisibilityEvaluator;

        $this->assertFalse($evaluator->isVisible($dependent, $form->fields, [$trigger->id => null]));
        $this->assertFalse($evaluator->isVisible($dependent, $form->fields, [$trigger->id => 'no']));
        $this->assertTrue($evaluator->isVisible($dependent, $form->fields, [$trigger->id => 'yes']));
    }

    public function test_equals_comparison_is_case_insensitive(): void
    {
        [$form, $trigger, $dependent] = $this->formWithFields();
        $evaluator = new FormFieldVisibilityEvaluator;

        $this->assertTrue($evaluator->isVisible($dependent, $form->fields, [$trigger->id => 'YES']));
    }

    public function test_not_equals_operator(): void
    {
        [$form, $trigger, $dependent] = $this->formWithFields();
        $dependent->update(['options' => array_merge($dependent->options, [
            'visibility_rule' => ['trigger_field_key' => 'trigger-key', 'operator' => 'not_equals', 'value' => 'yes'],
        ])]);

        $evaluator = new FormFieldVisibilityEvaluator;

        $this->assertFalse($evaluator->isVisible($dependent->fresh(), $form->fields()->get(), [$trigger->id => 'yes']));
        $this->assertTrue($evaluator->isVisible($dependent->fresh(), $form->fields()->get(), [$trigger->id => 'no']));
    }

    public function test_contains_operator(): void
    {
        [$form, $trigger, $dependent] = $this->formWithFields();
        $dependent->update(['options' => array_merge($dependent->options, [
            'visibility_rule' => ['trigger_field_key' => 'trigger-key', 'operator' => 'contains', 'value' => 'supp'],
        ])]);

        $evaluator = new FormFieldVisibilityEvaluator;

        $this->assertTrue($evaluator->isVisible($dependent->fresh(), $form->fields()->get(), [$trigger->id => 'need support please']));
        $this->assertFalse($evaluator->isVisible($dependent->fresh(), $form->fields()->get(), [$trigger->id => 'nope']));
    }

    public function test_a_rule_referencing_a_deleted_trigger_field_fails_open(): void
    {
        [$form, $trigger, $dependent] = $this->formWithFields();
        $trigger->delete();

        $evaluator = new FormFieldVisibilityEvaluator;

        $this->assertTrue($evaluator->isVisible($dependent->fresh(), $form->fields()->get(), []));
    }

    public function test_checkbox_answers_which_can_be_arrays_are_handled(): void
    {
        [$form, $trigger, $dependent] = $this->formWithFields();
        $dependent->update(['options' => array_merge($dependent->options, [
            'visibility_rule' => ['trigger_field_key' => 'trigger-key', 'operator' => 'contains', 'value' => 'yes'],
        ])]);

        $evaluator = new FormFieldVisibilityEvaluator;

        $this->assertTrue($evaluator->isVisible($dependent->fresh(), $form->fields()->get(), [$trigger->id => ['yes', 'maybe']]));
    }
}
