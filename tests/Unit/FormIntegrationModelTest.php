<?php

namespace Tests\Unit;

use App\Models\Form;
use App\Models\FormIntegration;
use App\Models\FormUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormIntegrationModelTest extends TestCase
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

    public function test_form_integration_relations(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $reviewer = User::factory()->create();
        $form = $this->makeForm($owner);

        $integration = FormIntegration::create([
            'form_id' => $form->id,
            'type' => 'webhook',
            'url' => 'https://example.com/hook',
            'proposed_by' => $owner->id,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->assertSame('pending_approval', $integration->fresh()->status);
        $this->assertTrue($integration->form->is($form));
        $this->assertTrue($integration->proposer->is($owner));
        $this->assertTrue($integration->reviewer->is($reviewer));
    }

    public function test_form_creator_is_an_approver(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);

        $this->assertTrue($form->isApprover($owner));
    }

    public function test_form_user_role_owner_is_an_approver_but_editor_is_not(): void
    {
        $creator = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($creator);
        $roleOwner = User::factory()->create();
        $editor = User::factory()->create();

        FormUserRole::create(['form_id' => $form->id, 'user_id' => $roleOwner->id, 'role' => 'owner']);
        FormUserRole::create(['form_id' => $form->id, 'user_id' => $editor->id, 'role' => 'editor']);

        $this->assertTrue($form->isApprover($roleOwner));
        $this->assertFalse($form->isApprover($editor));
    }
}
