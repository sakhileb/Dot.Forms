<?php

namespace Tests\Feature;

use App\Livewire\Forms\Builder;
use App\Livewire\Forms\PublicView;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FormSubmissionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_submission_handles_file_upload_and_validation(): void
    {
        config()->set('dotforms.forms.upload_disk', 'public');
        Storage::fake('public');
        Notification::fake();
        Http::fake();

        $owner = User::factory()->withPersonalTeam()->create();

        $form = Form::query()->create([
            'team_id' => $owner->currentTeam->id,
            'user_id' => $owner->id,
            'title' => 'Upload Form',
            'slug' => 'upload-form',
            'settings' => [
                'consent_required' => true,
                'consent_label' => 'I agree',
            ],
            'is_published' => true,
        ]);

        $emailField = $form->fields()->create([
            'type' => 'email',
            'label' => 'Email',
            'placeholder' => null,
            'options' => [],
            'validation_rules' => ['required', 'email'],
            'order' => 1,
        ]);

        $fileField = $form->fields()->create([
            'type' => 'file',
            'label' => 'Attachment',
            'placeholder' => null,
            'options' => [],
            'validation_rules' => ['required'],
            'order' => 2,
        ]);

        $component = Livewire::test(PublicView::class, ['slug' => $form->slug]);

        $component
            ->set('startedAt', now()->subSeconds(5)->timestamp)
            ->set('answers.'.$emailField->id, '')
            ->set('consentAccepted', false)
            ->call('submit')
            ->assertHasErrors(['answers.'.$emailField->id => 'required']);

        $component
            ->set('startedAt', now()->subSeconds(5)->timestamp)
            ->set('answers.'.$emailField->id, 'valid@example.com')
            ->set('uploads.'.$fileField->id, UploadedFile::fake()->create('proof.pdf', 50, 'application/pdf'))
            ->set('consentAccepted', true)
            ->call('submit')
            ->assertHasNoErrors();

        $submission = FormSubmission::query()->first();

        $this->assertNotNull($submission);
        $this->assertNotNull($submission->data[$fileField->id] ?? null);
        $this->assertTrue(Storage::disk('public')->exists($submission->data[$fileField->id]));
    }

    public function test_conditional_logic_suggestion_can_be_generated_in_builder(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $form = Form::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'title' => 'Conditional Form',
            'slug' => 'conditional-form',
            'settings' => [],
            'is_published' => false,
        ]);

        $form->fields()->create([
            'type' => 'email',
            'label' => 'Email',
            'placeholder' => null,
            'options' => [],
            'validation_rules' => ['required', 'email'],
            'order' => 1,
        ]);

        $form->fields()->create([
            'type' => 'textarea',
            'label' => 'Reason',
            'placeholder' => null,
            'options' => [],
            'validation_rules' => [],
            'order' => 2,
        ]);

        $this->actingAs($owner);

        Livewire::test(Builder::class, ['team' => $team, 'form' => $form])
            ->call('openFieldSettings', 1)
            ->call('suggestConditionalLogicForEditingField')
            ->assertSet('fields.1.conditional_logic', 'Show when Email is not empty.');
    }
}
