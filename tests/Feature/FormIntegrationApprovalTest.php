<?php

namespace Tests\Feature;

use App\Livewire\Forms\Builder;
use App\Models\Form;
use App\Models\FormIntegration;
use App\Models\FormUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FormIntegrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeForm(User $owner): Form
    {
        return Form::query()->create([
            'team_id' => $owner->currentTeam->id,
            'user_id' => $owner->id,
            'title' => 'Test Form',
            'slug' => 'test-form-'.uniqid(),
            'settings' => [],
            'is_published' => false,
        ]);
    }

    private function pendingIntegration(Form $form, User $owner): FormIntegration
    {
        return FormIntegration::create([
            'form_id' => $form->id,
            'type' => 'webhook',
            'url' => 'https://example.com/hook',
            'status' => 'pending_approval',
            'proposed_by' => $owner->id,
        ]);
    }

    public function test_owner_can_approve_a_pending_integration(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);
        $integration = $this->pendingIntegration($form, $owner);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->call('approveIntegration', 'webhook');

        $fresh = $integration->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertSame($owner->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
    }

    public function test_owner_can_reject_with_a_reason(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);
        $integration = $this->pendingIntegration($form, $owner);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->call('rejectIntegration', 'webhook', 'Unrecognized endpoint.');

        $fresh = $integration->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Unrecognized endpoint.', $fresh->rejected_reason);
    }

    public function test_rejecting_without_a_reason_is_blocked(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);
        $integration = $this->pendingIntegration($form, $owner);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->call('rejectIntegration', 'webhook', '')
            ->assertHasErrors('integrationRejectReason');

        $this->assertSame('pending_approval', $integration->fresh()->status);
    }

    public function test_editor_cannot_approve(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);
        $integration = $this->pendingIntegration($form, $owner);
        $editor = User::factory()->create();
        FormUserRole::create(['form_id' => $form->id, 'user_id' => $editor->id, 'role' => 'editor']);
        $owner->currentTeam->users()->attach($editor, ['role' => 'editor']);
        $editor->forceFill(['current_team_id' => $owner->currentTeam->id])->save();

        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        Livewire::actingAs($editor)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->call('approveIntegration', 'webhook');
    }

    public function test_approving_an_already_decided_integration_is_refused(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);
        $integration = $this->pendingIntegration($form, $owner);
        $integration->update(['status' => 'rejected', 'rejected_reason' => 'Already handled.']);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->call('approveIntegration', 'webhook');

        $this->assertSame('rejected', $integration->fresh()->status);
    }

    public function test_owners_own_newly_saved_url_still_requires_a_separate_approve_action(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->set('settings.webhook_url', 'https://example.com/hook')
            ->call('saveDraft');

        $integration = FormIntegration::where('form_id', $form->id)->where('type', 'webhook')->first();
        $this->assertSame('pending_approval', $integration->status);
    }
}
