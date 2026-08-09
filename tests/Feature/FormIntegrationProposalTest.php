<?php

namespace Tests\Feature;

use App\Livewire\Forms\Builder;
use App\Models\Form;
use App\Models\FormIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormIntegrationProposalTest extends TestCase
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

    public function test_saving_a_new_webhook_url_creates_a_pending_integration(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->set('settings.webhook_url', 'https://example.com/hook')
            ->call('saveDraft');

        $integration = FormIntegration::where('form_id', $form->id)->where('type', 'webhook')->first();
        $this->assertNotNull($integration);
        $this->assertSame('pending_approval', $integration->status);
        $this->assertSame($owner->id, $integration->proposed_by);
    }

    public function test_resaving_an_unchanged_active_url_does_not_retrigger_review(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);
        $integration = FormIntegration::create([
            'form_id' => $form->id,
            'type' => 'webhook',
            'url' => 'https://example.com/hook',
            'status' => 'active',
            'proposed_by' => $owner->id,
            'reviewed_by' => $owner->id,
            'reviewed_at' => now()->subDay(),
        ]);
        $originalReviewedAt = $integration->reviewed_at;

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->set('settings.webhook_url', 'https://example.com/hook')
            ->call('saveDraft');

        $fresh = $integration->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertTrue($originalReviewedAt->equalTo($fresh->reviewed_at));
    }

    public function test_clearing_a_url_deletes_the_integration_without_approval(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);
        FormIntegration::create([
            'form_id' => $form->id,
            'type' => 'webhook',
            'url' => 'https://example.com/hook',
            'status' => 'active',
            'proposed_by' => $owner->id,
            'reviewed_by' => $owner->id,
            'reviewed_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->set('settings.webhook_url', '')
            ->call('saveDraft');

        $this->assertSame(0, FormIntegration::where('form_id', $form->id)->where('type', 'webhook')->count());
    }
}
