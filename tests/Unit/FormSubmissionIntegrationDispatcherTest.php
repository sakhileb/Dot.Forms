<?php

namespace Tests\Unit;

use App\Models\Form;
use App\Models\FormIntegration;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\FormSubmissionIntegrationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FormSubmissionIntegrationDispatcherTest extends TestCase
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
            'is_published' => true,
        ]);
    }

    public function test_dispatch_only_reaches_active_integrations(): void
    {
        Http::fake();
        $owner = User::factory()->withPersonalTeam()->create();
        $form = $this->makeForm($owner);

        // SsrfGuard::isSafeUrl() does a real DNS lookup for hostnames, which
        // has no network access in this sandboxed test environment -- IP
        // literals bypass DNS resolution entirely (filter_var() validates
        // them directly), so these use real public IPs instead of hostnames
        // to stay a genuine SSRF-safe check rather than mocking the guard.
        FormIntegration::create([
            'form_id' => $form->id, 'type' => 'webhook', 'url' => 'https://93.184.216.34/hook',
            'status' => 'active', 'proposed_by' => $owner->id, 'reviewed_by' => $owner->id, 'reviewed_at' => now(),
        ]);
        FormIntegration::create([
            'form_id' => $form->id, 'type' => 'slack', 'url' => 'https://93.184.216.35/hook',
            'status' => 'pending_approval', 'proposed_by' => $owner->id,
        ]);
        FormIntegration::create([
            'form_id' => $form->id, 'type' => 'zapier', 'url' => 'https://93.184.216.36/hook',
            'status' => 'rejected', 'rejected_reason' => 'No.', 'proposed_by' => $owner->id,
            'reviewed_by' => $owner->id, 'reviewed_at' => now(),
        ]);

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => ['field' => 'value'],
            'submitted_at' => now(),
        ]);

        app(FormSubmissionIntegrationDispatcher::class)->dispatch($form, $submission);

        Http::assertSent(fn ($request) => $request->url() === 'https://93.184.216.34/hook');
        Http::assertNotSent(fn ($request) => $request->url() === 'https://93.184.216.35/hook');
        Http::assertNotSent(fn ($request) => $request->url() === 'https://93.184.216.36/hook');
    }
}
