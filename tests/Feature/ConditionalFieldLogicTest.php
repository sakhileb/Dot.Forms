<?php

namespace Tests\Feature;

use App\Livewire\Forms\Builder;
use App\Livewire\Forms\PublicView;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConditionalFieldLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hidden_field_is_not_required_and_its_answer_is_not_recorded(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();

        $form = Form::query()->create([
            'team_id' => $owner->currentTeam->id,
            'user_id' => $owner->id,
            'title' => 'Support Form',
            'slug' => 'support-form',
            'settings' => [],
            'is_published' => true,
        ]);

        $trigger = $form->fields()->create([
            'type' => 'radio',
            'label' => 'Need support?',
            'options' => ['choices' => ['yes', 'no'], 'field_key' => 'trigger-key'],
            'validation_rules' => ['required'],
            'order' => 1,
        ]);

        $dependent = $form->fields()->create([
            'type' => 'textarea',
            'label' => 'Describe the issue',
            'options' => [
                'field_key' => 'dependent-key',
                'visibility_rule' => ['trigger_field_key' => 'trigger-key', 'operator' => 'equals', 'value' => 'yes'],
            ],
            'validation_rules' => ['required'],
            'order' => 2,
        ]);

        $component = Livewire::test(PublicView::class, ['slug' => $form->slug]);

        // Trigger answered "no" -- the dependent field stays hidden and
        // must not block submission even though it's marked required.
        $component
            ->set('startedAt', now()->subSeconds(5)->timestamp)
            ->set('answers.'.$trigger->id, 'no')
            ->call('submit')
            ->assertHasNoErrors();

        $submission = FormSubmission::query()->first();
        $this->assertNotNull($submission);
        $this->assertArrayNotHasKey((string) $dependent->id, $submission->data);
        $this->assertArrayHasKey((string) $trigger->id, $submission->data);
    }

    public function test_a_visible_required_dependent_field_is_still_enforced(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();

        $form = Form::query()->create([
            'team_id' => $owner->currentTeam->id,
            'user_id' => $owner->id,
            'title' => 'Support Form',
            'slug' => 'support-form-2',
            'settings' => [],
            'is_published' => true,
        ]);

        $trigger = $form->fields()->create([
            'type' => 'radio',
            'label' => 'Need support?',
            'options' => ['choices' => ['yes', 'no'], 'field_key' => 'trigger-key'],
            'validation_rules' => ['required'],
            'order' => 1,
        ]);

        $dependent = $form->fields()->create([
            'type' => 'textarea',
            'label' => 'Describe the issue',
            'options' => [
                'field_key' => 'dependent-key',
                'visibility_rule' => ['trigger_field_key' => 'trigger-key', 'operator' => 'equals', 'value' => 'yes'],
            ],
            'validation_rules' => ['required'],
            'order' => 2,
        ]);

        Livewire::test(PublicView::class, ['slug' => $form->slug])
            ->set('startedAt', now()->subSeconds(5)->timestamp)
            ->set('answers.'.$trigger->id, 'yes')
            ->set('answers.'.$dependent->id, '')
            ->call('submit')
            ->assertHasErrors(['answers.'.$dependent->id => 'required']);
    }

    public function test_builder_save_persists_a_key_based_rule_that_survives_the_ids_changing(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $form = Form::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'title' => 'Builder Conditional Form',
            'slug' => 'builder-conditional-form',
            'settings' => [],
            'is_published' => false,
        ]);

        $this->actingAs($owner);

        $component = Livewire::test(Builder::class, ['team' => $team, 'form' => $form])
            ->call('addField', 'radio')
            ->call('addField', 'textarea')
            ->set('fields.0.label', 'Need support?')
            ->set('fields.0.options', 'yes, no')
            ->set('fields.1.label', 'Describe the issue');

        $triggerKey = $component->get('fields.0.key');

        $component
            ->set('fields.1.visibility_rule.trigger_field_key', $triggerKey)
            ->set('fields.1.visibility_rule.operator', 'equals')
            ->set('fields.1.visibility_rule.value', 'yes')
            ->call('saveDraft');

        $form->refresh();
        $dependentField = $form->fields()->where('label', 'Describe the issue')->first();
        $triggerField = $form->fields()->where('label', 'Need support?')->first();

        $rule = $dependentField->options['visibility_rule'] ?? null;
        $this->assertNotNull($rule);
        $this->assertSame($triggerField->options['field_key'], $rule['trigger_field_key']);
        $this->assertSame('equals', $rule['operator']);
        $this->assertSame('yes', $rule['value']);

        // Save again (fields are deleted and recreated with new DB ids on
        // every save) -- the rule must still resolve correctly afterward.
        $component->call('saveDraft');

        $form->refresh();
        $dependentField = $form->fields()->where('label', 'Describe the issue')->first();
        $triggerField = $form->fields()->where('label', 'Need support?')->first();
        $rule = $dependentField->options['visibility_rule'] ?? null;

        $this->assertSame($triggerField->options['field_key'], $rule['trigger_field_key']);
    }

    public function test_reverting_to_a_version_preserves_a_working_visibility_rule(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $form = Form::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'title' => 'Revert Conditional Form',
            'slug' => 'revert-conditional-form',
            'settings' => [],
            'is_published' => false,
        ]);

        $this->actingAs($owner);

        $component = Livewire::test(Builder::class, ['team' => $team, 'form' => $form])
            ->call('addField', 'radio')
            ->call('addField', 'textarea')
            ->set('fields.0.label', 'Need support?')
            ->set('fields.0.options', 'yes, no')
            ->set('fields.1.label', 'Describe the issue');

        $triggerKey = $component->get('fields.0.key');

        $component
            ->set('fields.1.visibility_rule.trigger_field_key', $triggerKey)
            ->set('fields.1.visibility_rule.operator', 'equals')
            ->set('fields.1.visibility_rule.value', 'yes')
            ->call('saveDraft');

        $versionId = $form->versions()->latest('version_number')->value('id');

        // A second, unrelated save (title change) to prove revert actually
        // restores the earlier rule rather than it just still being there.
        $component->set('title', 'Renamed Form')->call('saveDraft');

        $component->call('revertToVersion', $versionId);

        $form->refresh();
        $dependentField = $form->fields()->where('label', 'Describe the issue')->first();
        $triggerField = $form->fields()->where('label', 'Need support?')->first();
        $rule = $dependentField->options['visibility_rule'] ?? null;

        $this->assertNotNull($rule);
        $this->assertSame($triggerField->options['field_key'], $rule['trigger_field_key']);
    }
}
