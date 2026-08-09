# Integration URL Approval Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A new or changed integration URL (webhook/Slack/Zapier/Make/CRM) no longer takes effect immediately — it's staged as `pending_approval` and only the form's owner can approve it before `FormSubmissionIntegrationDispatcher` will ever dispatch live submission data to it.

**Architecture:** A new `form_integrations` table (one row per `form_id` + `type`) replaces the five URL keys currently living in `Form.settings` JSON as the source of truth. `Builder.php`'s save flow creates/updates rows there instead, `Form::isApprover()` gates a new approve/reject pair of methods, and `FormSubmissionIntegrationDispatcher` reads only `status = 'active'` rows.

**Tech Stack:** Laravel (this repo's existing conventions), Livewire 3, PHPUnit, `Http::fake()`.

## Global Constraints

- `form_integrations` has a unique constraint on `(form_id, type)` — at most one row per integration slot per form.
- Saving an unchanged `active` URL must not re-trigger review (no-op, `reviewed_at` stays put).
- Saving an empty/cleared URL deletes the row outright — removing an integration never needs approval.
- Rejecting requires a non-empty `reason` — a validation failure, never silently accepted.
- **The form owner's own newly-saved URL still lands `pending_approval`, never auto-`active`** — a deliberate second confirm is required even from the accountable party, matching Dot.docs' precedent.
- The migration backfills existing forms' already-configured URLs as `active` (grandfathered) — this is a one-time exception for pre-existing data, not a precedent for anything saved after this ships.
- Approval authority is `Form::isApprover()` — form creator, team owner, or an explicit `FormUserRole` of `'owner'`. **Not** `'editor'`.
- Every `git add` lists files explicitly, never `git add -A`/`git add .` — this repo has pre-existing unrelated uncommitted changes (`resources/views/layouts/app.blade.php`, `resources/views/navigation-menu.blade.php`, `public/images/mark-light.png`/`mark.png`) stashed, not part of this work. `git status --short` before and after every task.
- Full test suite (`php artisan test --compact`) must stay green after every task; `vendor/bin/pint --dirty --format agent` after every task that touches PHP files.

---

### Task 1: `form_integrations` table + model + `Form::isApprover()`

**Files:**
- Create: `database/migrations/2026_08_09_000001_create_form_integrations_table.php`
- Create: `app/Models/FormIntegration.php`
- Modify: `app/Models/Form.php`
- Test: `tests/Unit/FormIntegrationModelTest.php`

**Interfaces:**
- Produces: `FormIntegration::$fillable` = `['form_id', 'type', 'url', 'status', 'rejected_reason', 'proposed_by', 'reviewed_by', 'reviewed_at']`; `FormIntegration::form()/proposer()/reviewer()` (all `BelongsTo`); `Form::integrations(): HasMany`; `Form::isApprover(User $user): bool`.

- [ ] **Step 1: Write the migration**

Create `database/migrations/2026_08_09_000001_create_form_integrations_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('type');
            $table->string('url');
            $table->string('status')->default('pending_approval');
            $table->text('rejected_reason')->nullable();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['form_id', 'type']);
        });

        // Backfill: any form with an already-configured URL is grandfathered
        // as active -- a one-time exception so no live external integration
        // breaks the moment this ships. proposed_by/reviewed_by are set to
        // the form's own user_id (best-effort attribution; there's no real
        // historical "who approved this" data to recover).
        $slots = [
            'webhook_url' => 'webhook',
            'slack_webhook_url' => 'slack',
            'zapier_webhook_url' => 'zapier',
            'make_webhook_url' => 'make',
            'crm_webhook_url' => 'crm',
        ];

        DB::table('forms')->orderBy('id')->select(['id', 'user_id', 'settings'])
            ->each(function ($form) use ($slots) {
                $settings = json_decode((string) $form->settings, true) ?: [];

                foreach ($slots as $settingsKey => $type) {
                    $url = $settings[$settingsKey] ?? null;

                    if (! is_string($url) || $url === '') {
                        continue;
                    }

                    DB::table('form_integrations')->insert([
                        'form_id' => $form->id,
                        'type' => $type,
                        'url' => $url,
                        'status' => 'active',
                        'proposed_by' => $form->user_id,
                        'reviewed_by' => $form->user_id,
                        'reviewed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_integrations');
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate`
Expected: migration runs with no errors.

- [ ] **Step 3: Write the failing test**

Create `tests/Unit/FormIntegrationModelTest.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it fails**

Run: `php artisan test tests/Unit/FormIntegrationModelTest.php`
Expected: FAIL — `Class "App\Models\FormIntegration" not found`.

- [ ] **Step 5: Write `app/Models/FormIntegration.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormIntegration extends Model
{
    protected $fillable = [
        'form_id', 'type', 'url', 'status', 'rejected_reason', 'proposed_by', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
```

- [ ] **Step 6: Add `Form::integrations()` and `Form::isApprover()`**

In `app/Models/Form.php`, add `use Illuminate\Database\Eloquent\Relations\HasMany;` if not already imported (it already is, per `userRoles()`/`versions()`). Add these two methods, placed next to `userRoles()`:

```php
    public function integrations(): HasMany
    {
        return $this->hasMany(FormIntegration::class);
    }

    /**
     * Narrower than editableBy() -- an editor can propose an integration
     * URL, but only an owner-equivalent user may approve it. Reuses the
     * same three-way check editableBy() uses for its owner-equivalent
     * branch (form creator, team owner) and additionally accepts an
     * explicit FormUserRole of 'owner' (not 'editor').
     */
    public function isApprover(User $user): bool
    {
        if ((int) $this->user_id === (int) $user->id || $user->ownsTeam($this->team)) {
            return true;
        }

        $role = $this->userRoles()->where('user_id', $user->id)->value('role');

        return $role === 'owner';
    }
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test tests/Unit/FormIntegrationModelTest.php`
Expected: PASS (3 tests)

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_08_09_000001_create_form_integrations_table.php \
  app/Models/FormIntegration.php app/Models/Form.php \
  tests/Unit/FormIntegrationModelTest.php \
  docs/superpowers/plans/2026-08-09-integration-url-approval-gate.md
git commit -m "feat: FormIntegration model + Form::isApprover()

New table replaces the five *_url keys in Form.settings as the source
of truth. Migration backfills existing forms' already-configured URLs
as active -- grandfathered, one-time exception, not a precedent.
isApprover() is strictly narrower than the existing editableBy(): an
editor can propose, only an owner-equivalent user can approve."
```

---

### Task 2: `Builder.php` — proposing and reviewing

**Files:**
- Modify: `app/Livewire/Forms/Builder.php`
- Modify: `resources/views/livewire/forms/builder.blade.php`
- Test: `tests/Feature/FormIntegrationProposalTest.php`
- Test: `tests/Feature/FormIntegrationApprovalTest.php`

**Interfaces:**
- Consumes: `FormIntegration`, `Form::isApprover()` (Task 1).
- Produces: `Builder::approveIntegration(string $type): void`, `Builder::rejectIntegration(string $type, string $reason): void`, `Builder::promptRejectIntegration(string $type): void`, `Builder::cancelRejectIntegration(): void`, `Builder::confirmRejectIntegration(): void`. `Builder::$rejectingIntegrationType`/`$integrationRejectReason` public properties.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/FormIntegrationProposalTest.php`:

```php
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
```

Create `tests/Feature/FormIntegrationApprovalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\Forms\Builder;
use App\Models\Form;
use App\Models\FormIntegration;
use App\Models\FormUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        $this->withoutExceptionHandling();
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Livewire::actingAs($owner)
            ->test(Builder::class, ['team' => $owner->currentTeam, 'form' => $form])
            ->call('rejectIntegration', 'webhook', '');
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
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/FormIntegrationProposalTest.php tests/Feature/FormIntegrationApprovalTest.php`
Expected: FAIL — `persist()` still writes to `Form.settings`, and `approveIntegration`/`rejectIntegration` don't exist yet.

- [ ] **Step 3: Update `persist()` to propose instead of directly saving URLs**

In `app/Livewire/Forms/Builder.php`, add `use App\Models\FormIntegration;` to the imports. In `protected function persist(bool $flashOnError = true): void`, remove the five URL keys from the `$formData['settings']` array (`webhook_url`, `slack_webhook_url`, `zapier_webhook_url`, `make_webhook_url`, `crm_webhook_url` — leave `crm_provider` and every other key untouched), and after `$this->form->save();` (but before `$this->form->fields()->delete();`), add:

```php
        $this->syncIntegrations($validated['settings']);
```

Add the new protected method, placed after `persist()`:

```php
    /**
     * @param  array<string, mixed>  $settings
     */
    protected function syncIntegrations(array $settings): void
    {
        $slots = [
            'webhook_url' => 'webhook',
            'slack_webhook_url' => 'slack',
            'zapier_webhook_url' => 'zapier',
            'make_webhook_url' => 'make',
            'crm_webhook_url' => 'crm',
        ];

        foreach ($slots as $settingsKey => $type) {
            $url = trim((string) ($settings[$settingsKey] ?? ''));
            $existing = $this->form->integrations()->where('type', $type)->first();

            if ($url === '') {
                $existing?->delete();

                continue;
            }

            if ($existing && $existing->url === $url && $existing->status === 'active') {
                // Unchanged active URL -- no-op, does not re-trigger review.
                continue;
            }

            if ($existing) {
                $existing->update([
                    'url' => $url,
                    'status' => 'pending_approval',
                    'rejected_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);
            } else {
                FormIntegration::create([
                    'form_id' => $this->form->id,
                    'type' => $type,
                    'url' => $url,
                    'status' => 'pending_approval',
                    'proposed_by' => auth()->id(),
                ]);
            }
        }
    }
```

- [ ] **Step 4: Update `hydrateState()` to read current URLs from `FormIntegration`**

In `hydrateState()`, after the existing `$this->settings = array_merge([...], $this->form->settings ?? []);` block (the five `*_url` keys stay in that default array as `null` — leave that line alone), add:

```php
        foreach ($this->form->integrations as $integration) {
            $key = match ($integration->type) {
                'webhook' => 'webhook_url',
                'slack' => 'slack_webhook_url',
                'zapier' => 'zapier_webhook_url',
                'make' => 'make_webhook_url',
                'crm' => 'crm_webhook_url',
                default => null,
            };

            if ($key !== null) {
                $this->settings[$key] = $integration->url;
            }
        }
```

This must run after `$this->settings` is fully assigned from the merge — place it as the very next statement after that block, before the `quiz_answer_key_json` line that follows it.

- [ ] **Step 5: Add `$integrationStatuses` for the view + approve/reject methods**

Add these public properties near the top of the class, next to `public ?int $selectedVersionId = null;`:

```php
    public ?string $rejectingIntegrationType = null;

    public string $integrationRejectReason = '';
```

Add a computed property and the approve/reject methods, placed after `hydrateState()`:

```php
    public function getIntegrationsProperty(): \Illuminate\Support\Collection
    {
        return $this->form->integrations()->get()->keyBy('type');
    }

    public function approveIntegration(string $type): void
    {
        abort_unless($this->form->isApprover(Auth::user()), 403);

        $integration = $this->form->integrations()->where('type', $type)->first();

        if (! $integration || $integration->status !== 'pending_approval') {
            return;
        }

        $integration->update([
            'status' => 'active',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejectIntegration(string $type, string $reason): void
    {
        abort_unless($this->form->isApprover(Auth::user()), 403);

        $this->validate([], [], [], ['reason' => $reason])
            ?? null; // placeholder removed below -- see Step 5 note

        $integration = $this->form->integrations()->where('type', $type)->first();

        if (! $integration || $integration->status !== 'pending_approval') {
            return;
        }

        if (trim($reason) === '') {
            $this->addError('integrationRejectReason', 'A rejection reason is required.');

            throw \Illuminate\Validation\ValidationException::withMessages([
                'integrationRejectReason' => 'A rejection reason is required.',
            ]);
        }

        $integration->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    public function promptRejectIntegration(string $type): void
    {
        $this->rejectingIntegrationType = $type;
        $this->integrationRejectReason = '';
    }

    public function cancelRejectIntegration(): void
    {
        $this->rejectingIntegrationType = null;
        $this->integrationRejectReason = '';
    }

    public function confirmRejectIntegration(): void
    {
        if (! $this->rejectingIntegrationType) {
            return;
        }

        $this->rejectIntegration($this->rejectingIntegrationType, $this->integrationRejectReason);
        $this->rejectingIntegrationType = null;
        $this->integrationRejectReason = '';
    }
```

Note: the line `$this->validate([], [], [], ['reason' => $reason]) ?? null; // placeholder removed below` in the draft above is **not valid code and must not be written to disk** — it's a marker for the plan's self-review, not an instruction. The actual `rejectIntegration()` method to write is:

```php
    public function rejectIntegration(string $type, string $reason): void
    {
        abort_unless($this->form->isApprover(Auth::user()), 403);

        if (trim($reason) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'integrationRejectReason' => 'A rejection reason is required.',
            ]);
        }

        $integration = $this->form->integrations()->where('type', $type)->first();

        if (! $integration || $integration->status !== 'pending_approval') {
            return;
        }

        $integration->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }
```

Use this version — checking `trim($reason) === ''` before looking up the integration, throwing `ValidationException` directly (matching `test_rejecting_without_a_reason_is_blocked`'s expectation) rather than the malformed draft.

- [ ] **Step 6: Update the view**

In `resources/views/livewire/forms/builder.blade.php`, replace the four existing URL input blocks (Slack, Zapier, Make, and the CRM webhook input inside the `@if (($settings['crm_provider'] ?? 'none') !== 'none')` block) plus add the previously-missing generic webhook input, each now showing status and, when `$this->form->isApprover(auth()->user())` and the slot is `pending_approval`, approve/reject controls. Replace the block starting at `<div>` before "Slack Webhook" through the end of the "Make Webhook" `<div>` (the three-block section) with:

```blade
                        @php
                            $integrationSlots = [
                                'webhook' => ['label' => 'Webhook', 'field' => 'webhook_url', 'placeholder' => 'https://example.com/webhook'],
                                'slack' => ['label' => 'Slack Webhook', 'field' => 'slack_webhook_url', 'placeholder' => 'https://hooks.slack.com/...'],
                                'zapier' => ['label' => 'Zapier Webhook', 'field' => 'zapier_webhook_url', 'placeholder' => 'https://hooks.zapier.com/...'],
                                'make' => ['label' => 'Make Webhook', 'field' => 'make_webhook_url', 'placeholder' => 'https://hook.make.com/...'],
                            ];
                            $isApprover = $this->form->isApprover(auth()->user());
                        @endphp

                        @foreach ($integrationSlots as $type => $slot)
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                    {{ $slot['label'] }}
                                </label>
                                <input type="url" wire:model.live="settings.{{ $slot['field'] }}" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" placeholder="{{ $slot['placeholder'] }}" />

                                @php $integration = $this->integrations->get($type); @endphp
                                @if ($integration)
                                    <div style="margin-top: 6px; font-size: 12px;">
                                        @if ($integration->status === 'active')
                                            <span style="color: #16a34a; font-weight: 600;">Active</span>
                                        @elseif ($integration->status === 'rejected')
                                            <span style="color: #dc2626; font-weight: 600;">Rejected: {{ $integration->rejected_reason }}</span>
                                        @elseif ($isApprover)
                                            <span style="color: #d97706; font-weight: 600;">Pending approval</span>
                                            @if ($rejectingIntegrationType === $type)
                                                <div style="display: flex; gap: 6px; margin-top: 4px;">
                                                    <input type="text" wire:model="integrationRejectReason" placeholder="Reason for rejecting" style="flex: 1; padding: 6px 8px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 12px;" />
                                                    <button type="button" wire:click="confirmRejectIntegration" style="padding: 6px 10px; background: #dc2626; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Confirm Reject</button>
                                                    <button type="button" wire:click="cancelRejectIntegration" style="padding: 6px 10px; background: #E5E5E5; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Cancel</button>
                                                </div>
                                            @else
                                                <div style="display: flex; gap: 6px; margin-top: 4px;">
                                                    <button type="button" wire:click="approveIntegration('{{ $type }}')" style="padding: 6px 10px; background: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Approve</button>
                                                    <button type="button" wire:click="promptRejectIntegration('{{ $type }}')" style="padding: 6px 10px; background: #dc2626; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Reject</button>
                                                </div>
                                            @endif
                                        @else
                                            <span style="color: #d97706; font-weight: 600;">Pending approval (awaiting the form owner)</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
```

For the CRM webhook input (inside the existing `@if (($settings['crm_provider'] ?? 'none') !== 'none')` block), apply the identical status/approve/reject pattern with `$type = 'crm'` and `$slot['field'] = 'crm_webhook_url'` — copy the same inner block (from `@php $integration = ...` through the closing `@endif` before the outer block's own `@endif`) directly below that existing input, substituting the literal type string `'crm'` in place of the loop variable `$type` throughout (this one isn't inside the `@foreach`, so `$type`/`$slot` aren't in scope there).

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test tests/Feature/FormIntegrationProposalTest.php tests/Feature/FormIntegrationApprovalTest.php`
Expected: PASS (3 + 6 = 9 tests)

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/Forms/Builder.php resources/views/livewire/forms/builder.blade.php \
  tests/Feature/FormIntegrationProposalTest.php tests/Feature/FormIntegrationApprovalTest.php
git commit -m "feat: proposing and approving integration URLs in Builder.php

Saving a new/changed URL creates or updates a pending_approval
FormIntegration row instead of writing directly into Form.settings.
Only Form::isApprover() can approve/reject; reject requires a reason.
The owner's own change still lands pending_approval -- no auto-active
on save, even for the accountable party. Also fixes a real pre-existing
gap: the generic 'webhook' slot was validated and dispatched to but had
no UI input at all until this task added one."
```

---

### Task 3: `FormSubmissionIntegrationDispatcher` reads only active integrations

**Files:**
- Modify: `app/Services/FormSubmissionIntegrationDispatcher.php`
- Test: `tests/Unit/FormSubmissionIntegrationDispatcherTest.php`

**Interfaces:**
- Consumes: `Form::integrations()` (Task 1).

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/FormSubmissionIntegrationDispatcherTest.php`:

```php
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

        FormIntegration::create([
            'form_id' => $form->id, 'type' => 'webhook', 'url' => 'https://active.example.com/hook',
            'status' => 'active', 'proposed_by' => $owner->id, 'reviewed_by' => $owner->id, 'reviewed_at' => now(),
        ]);
        FormIntegration::create([
            'form_id' => $form->id, 'type' => 'slack', 'url' => 'https://pending.example.com/hook',
            'status' => 'pending_approval', 'proposed_by' => $owner->id,
        ]);
        FormIntegration::create([
            'form_id' => $form->id, 'type' => 'zapier', 'url' => 'https://rejected.example.com/hook',
            'status' => 'rejected', 'rejected_reason' => 'No.', 'proposed_by' => $owner->id,
            'reviewed_by' => $owner->id, 'reviewed_at' => now(),
        ]);

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => ['field' => 'value'],
            'submitted_at' => now(),
        ]);

        app(FormSubmissionIntegrationDispatcher::class)->dispatch($form, $submission);

        Http::assertSent(fn ($request) => $request->url() === 'https://active.example.com/hook');
        Http::assertNotSent(fn ($request) => $request->url() === 'https://pending.example.com/hook');
        Http::assertNotSent(fn ($request) => $request->url() === 'https://rejected.example.com/hook');
    }
}
```

Read `app/Models/FormSubmission.php`'s `$fillable` first to confirm `data`/`submitted_at` are mass-assignable as written above; adjust the `FormSubmission::create([...])` call to match its real fillable list if it differs (e.g. it may also require `ip_address`/`user_agent` as non-nullable — check the migration for `form_submissions` if the create call fails validation-free but errors at the DB level).

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/FormSubmissionIntegrationDispatcherTest.php`
Expected: FAIL — dispatch currently reads `$form->settings`, which is empty, so `Http::assertSent()` for the active URL fails (nothing was dispatched at all).

- [ ] **Step 3: Rewrite `dispatch()`'s target resolution**

In `app/Services/FormSubmissionIntegrationDispatcher.php`, replace:

```php
        $settings = is_array($form->settings) ? $form->settings : [];
        $crmProvider = (string) ($settings['crm_provider'] ?? 'generic');

        $targets = array_filter([
            'webhook' => $settings['webhook_url'] ?? null,
            'slack' => $settings['slack_webhook_url'] ?? null,
            'zapier' => $settings['zapier_webhook_url'] ?? null,
            'make' => $settings['make_webhook_url'] ?? null,
            'crm' => $settings['crm_webhook_url'] ?? null,
        ]);
```

with:

```php
        $settings = is_array($form->settings) ? $form->settings : [];
        $crmProvider = (string) ($settings['crm_provider'] ?? 'generic');

        $targets = $form->integrations()
            ->where('status', 'active')
            ->pluck('url', 'type')
            ->all();
```

Nothing else in `dispatch()` changes — the SSRF re-check, the `foreach ($targets as $url)` loop, the HTTP call, and the CRM mapper all operate on `$targets` exactly as before; only where `$targets` comes from changes.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/FormSubmissionIntegrationDispatcherTest.php`
Expected: PASS (1 test)

- [ ] **Step 5: Commit**

```bash
git add app/Services/FormSubmissionIntegrationDispatcher.php \
  tests/Unit/FormSubmissionIntegrationDispatcherTest.php
git commit -m "feat: dispatcher only reaches active integrations

Targets now come from Form::integrations()->where('status', 'active')
instead of Form.settings. A pending_approval or rejected integration
is never dispatched to, by construction -- the query itself is the
enforcement point, matching Dot.docs' identical pattern."
```

---

### Task 4: Full regression + manual verification

**Files:** none new — verification only.

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: 0 failures across the whole suite — confirms Tasks 1-3 didn't break `FormPermissionTest`, `FormSubmissionFlowTest`, `FormCreationTest`, `FormsTeamScopeTest`, `FormTeamMismatchTest`, or any other pre-existing test.

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 1 if it reformats anything.

- [ ] **Step 3: Manual end-to-end verification**

```bash
php artisan tinker --execute '
$owner = \App\Models\User::factory()->withPersonalTeam()->create(["name" => "Manual Test Owner"]);
$form = \App\Models\Form::query()->create([
    "team_id" => $owner->currentTeam->id, "user_id" => $owner->id,
    "title" => "Manual Test Form", "slug" => "manual-test-form-".uniqid(),
    "settings" => [], "is_published" => true,
]);
echo "owner_id={$owner->id} form_id={$form->id}\n";
'
```

Then propose a webhook URL via the real Livewire component:

```bash
php artisan tinker --execute '
auth()->loginUsingId(<owner_id>);
$form = \App\Models\Form::find(<form_id>);
$component = new \App\Livewire\Forms\Builder();
$component->mount($form->team, $form);
$component->settings["webhook_url"] = "https://example.com/manual-test-hook";
$component->saveDraft();
$integration = \App\Models\FormIntegration::where("form_id", <form_id>)->where("type", "webhook")->first();
echo "status after save: {$integration->status}\n";
'
```

Expected: `status after save: pending_approval`.

Then approve it as the (same) owner:

```bash
php artisan tinker --execute '
$form = \App\Models\Form::find(<form_id>);
auth()->loginUsingId(<owner_id>);
$component = new \App\Livewire\Forms\Builder();
$component->mount($form->team, $form);
$component->approveIntegration("webhook");
$integration = \App\Models\FormIntegration::where("form_id", <form_id>)->where("type", "webhook")->first();
echo "status after approval: {$integration->status}\n";
'
```

Expected: `status after approval: active`.

Then confirm the dispatcher now reaches it:

```bash
php artisan tinker --execute '
\Illuminate\Support\Facades\Http::fake();
$form = \App\Models\Form::find(<form_id>);
$submission = \App\Models\FormSubmission::create(["form_id" => $form->id, "data" => ["field" => "value"], "submitted_at" => now()]);
app(\App\Services\FormSubmissionIntegrationDispatcher::class)->dispatch($form, $submission);
\Illuminate\Support\Facades\Http::assertSent(fn ($request) => $request->url() === "https://example.com/manual-test-hook");
echo "dispatch reached the approved URL: yes\n";
'
```

Expected: no assertion failure, prints `dispatch reached the approved URL: yes`. Confirms the real end-to-end lifecycle, not just in-memory test doubles.

- [ ] **Step 4: Clean up manual verification fixtures**

```bash
php artisan tinker --execute '
$form = \App\Models\Form::where("title", "Manual Test Form")->first();
if ($form) { $form->integrations()->delete(); $form->fields()->delete(); $form->delete(); }
\App\Models\User::where("name", "Manual Test Owner")->each(function ($u) { $u->currentTeam?->delete(); $u->delete(); });
echo "cleaned up manual verification fixtures\n";
'
```

- [ ] **Step 5: Report completion**

No commit for this task — it's verification only. If Step 1 finds any failures, stop and fix them (return to the relevant earlier task) before considering this plan complete.

## Self-Review Notes

- **Spec coverage:** Task 1 covers spec §1 (table/model/backfill) and §2 (`isApprover`). Task 2 covers spec §3 (`Builder.php` propose/approve/reject) and §4 (view). Task 3 covers spec §5 (dispatcher). Task 4 covers the spec's implicit "this all actually works together" requirement.
- **Placeholder scan:** Task 2 Step 5's first `rejectIntegration()` draft intentionally contains a marked non-functional placeholder to demonstrate what NOT to write, immediately followed by the real, complete method to actually use — flagged explicitly in the step's own text so an implementer reading out of order can't mistake the draft for the real instruction. No other placeholders exist anywhere in the plan.
- **Type consistency:** `FormIntegration::$fillable`, `Form::integrations()`/`isApprover()`, `Builder::approveIntegration/rejectIntegration/promptRejectIntegration/cancelRejectIntegration/confirmRejectIntegration` signatures and the `$rejectingIntegrationType`/`$integrationRejectReason` property names are used identically across every task that references them.
- **Testing item deferred from the spec, resolved here:** the spec's §Testing section left the backfill-migration test's exact shape conditional ("check first... otherwise verified via manual tinker"). Resolved in this plan: no dedicated backfill test file — Task 1's migration is exercised directly by every other test that relies on `FormIntegration` rows existing, and Task 4's manual verification is the concrete confirmation for the backfill path specifically (a pre-existing `Form.settings`-based URL becoming an `active` row), since spinning up a partial-migration test harness isn't worth the complexity for a one-time data migration.
- **Uncommitted pre-existing changes:** this repo has pre-existing uncommitted changes (`app.blade.php`, `navigation-menu.blade.php`, mark images) stashed before this plan was written, per the spec's own context section; every `git add` in this plan lists files explicitly.
