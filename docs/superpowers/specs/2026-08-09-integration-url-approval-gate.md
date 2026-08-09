# Dot.Forms: Integration URL Approval Gate

## Context

Dot.Forms' autonomy classification audit (`Dot.Brain/platforms/dot-forms.md`, 2026-08-08) found four real Level 1 processes but no Level 2 escalation flow anywhere in the codebase — no "pending approval" state exists for anything. The audit's own gap summary names a concrete, non-fabricated candidate: `FormSubmissionIntegrationDispatcher` automatically POSTs live submission data (name, email, every field value) to whichever webhook/Slack/Zapier/Make/CRM URL a team has configured, on every single form submission, with zero review of the URL before it starts receiving data.

Direct inspection of the real codebase confirms the gap: `app/Livewire/Forms/Builder.php` saves five URL fields (`webhook_url`, `slack_webhook_url`, `zapier_webhook_url`, `make_webhook_url`, `crm_webhook_url`) directly into `Form.settings` JSON, validated against SSRF at save time (`App\Rules\SafeWebhookUrl`) but with no approval step — any collaborator with `editor`-or-above access can point a form's submission stream at any external URL, and the very next submission dispatches to it.

Unlike Dot.Ehail/Dot.Emall/Dot.Files, this platform already has a real, existing per-form role hierarchy (`FormUserRole`: `owner`/`editor`/`viewer`) — this spec reuses it rather than introducing a new platform-operator concept. Directly analogous to Dot.docs' Webhook Approval Gate built earlier this session, but the reviewer is the form's own owner, not a new platform-wide role, and the URLs live as five fixed named slots (not an unbounded collection) — matching this platform's existing shape rather than Dot.docs' `document_webhooks` table.

## Goal

Saving a new or changed integration URL no longer takes effect immediately. It's staged as `pending_approval`; only the form's owner (the form's creator, the team owner, or an explicit `FormUserRole` of `owner`) can approve it before `FormSubmissionIntegrationDispatcher` will ever dispatch to it. Rejecting requires a reason. An owner's own change still requires a deliberate second confirm — not an automatic activation on save, matching Dot.docs' precedent that even the accountable party gets a real second step, not a rubber stamp. Existing forms with an already-configured URL are grandfathered as `active` so no live external integration breaks the moment this ships.

## Changes

### 1. `form_integrations` table + `FormIntegration` model

New migration, `database/migrations/<timestamp>_create_form_integrations_table.php`: `id`, `form_id` (FK `forms.id`, `cascadeOnDelete`), `type` (string — one of `webhook`, `slack`, `zapier`, `make`, `crm`), `url` (string), `status` (string, default `'pending_approval'` — `pending_approval`/`active`/`rejected`), `rejected_reason` (nullable text), `proposed_by` (FK `users.id`, `cascadeOnDelete`), `reviewed_by` (nullable FK `users.id`, `nullOnDelete`), `reviewed_at` (nullable timestamp), timestamps. Unique constraint on `(form_id, type)` — at most one row per integration slot per form, matching the current one-URL-per-type shape in `Form.settings`.

**Backfill, in the same migration's `up()`:** for every existing `Form` row with a non-null value at any of the five `settings.*_url` keys, create a `FormIntegration` row with `status: 'active'`, `proposed_by`/`reviewed_by` both set to the form's `user_id` (best-effort attribution — there's no real historical "who approved this" data to recover, and grandfathering is explicitly a one-time exception, not a precedent for future changes), `reviewed_at: now()`. This runs once, at migration time, over whatever forms exist in this environment today.

New model `app/Models/FormIntegration.php`: `$fillable` = `['form_id', 'type', 'url', 'status', 'rejected_reason', 'proposed_by', 'reviewed_by', 'reviewed_at']`, `form(): BelongsTo`, `proposer(): BelongsTo` (User via `proposed_by`), `reviewer(): BelongsTo` (User via `reviewed_by`).

### 2. `Form::isApprover(User $user): bool`

New method on `app/Models/Form.php`, factored out of the owner-equivalent branch already duplicated inside `editableBy()`: form creator (`(int) $this->user_id === (int) $user->id`), team owner (`$user->ownsTeam($this->team)`), or an explicit `FormUserRole` of `'owner'` — **not** `'editor'`. `editableBy()` itself is unchanged in behavior (still allows both `owner` and `editor` to edit); `isApprover()` is strictly narrower, matching the design decision that editors can propose an integration URL but only an owner-equivalent user can approve it.

### 3. `Builder.php` — proposing and reviewing

The existing settings-save method (currently writing `webhook_url` etc. directly into `$validated['settings']`) is changed to, for each of the five slots: if the submitted URL differs from the slot's current `FormIntegration->url` (or no row exists yet), create-or-update the row with `status: 'pending_approval'`, `url`, `proposed_by: auth()->id()`, and clear `reviewed_by`/`reviewed_at`/`rejected_reason`. If the submitted URL is unchanged from the current `active` row, no-op — resaving the same value doesn't re-trigger review. If the submitted value is empty/null and a row exists, the row is deleted (removing an integration is not something that needs approval — it can only reduce exposure, never increase it). `SafeWebhookUrl` validation is unchanged, still runs before any of this.

New methods:

```php
public function approveIntegration(string $type): void
{
    Gate::authorize('isApprover', $this->form); // see note below on how this is wired

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
    Gate::authorize('isApprover', $this->form);

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

`Gate::authorize('isApprover', $this->form)` is illustrative of the check's intent; the actual implementation calls `abort_unless($this->form->isApprover(auth()->user()), 403)` directly (this app's existing `editableBy()`/`viewableSubmissionsBy()` checks are called the same direct way elsewhere in `Builder.php`, not through Laravel's Gate/Policy system — this spec follows that established convention rather than introducing a new Policy class for one method).

### 4. View — `resources/views/livewire/forms/builder.blade.php`

The existing integrations settings section (wherever the five URL inputs currently render) gains, per slot: the current status (`Active`/`Pending approval`/`Rejected: {reason}`/nothing if no row exists), and — visible only when `$form->isApprover(auth()->user())` is true and the slot's status is `pending_approval` — an Approve button and a Reject-with-reason control, matching the confirm-then-act UI pattern already used in this session's Dot.Mines/Dot.docs work (a `rejectingIntegrationType`/`rejectReason` pair of component properties, prompt → confirm/cancel).

### 5. `FormSubmissionIntegrationDispatcher`

`dispatch()`'s `$targets` array, currently built from `$form->settings`'s five keys, is rebuilt from `$form->integrations()->where('status', 'active')->pluck('url', 'type')` instead. Everything downstream — the SSRF re-check at dispatch time, the `allow_redirects: false` HTTP call, the per-target try/catch — is unchanged. A `pending_approval` or `rejected` integration is never dispatched to, by construction (it's simply absent from the query result), matching Dot.docs' identical "the query itself is the enforcement point" pattern.

## Testing

New test files, matching this app's existing test conventions (checked against `tests/Feature/` for the house style before writing):

- `tests/Feature/FormIntegrationProposalTest.php` — an editor saving a new webhook URL creates a `pending_approval` `FormIntegration` row and does not dispatch to it on the next submission; saving an unchanged active URL doesn't re-trigger review (status stays `active`, `reviewed_at` unchanged); clearing a URL to empty deletes the row without requiring approval.
- `tests/Feature/FormIntegrationApprovalTest.php` — the form owner can approve a pending integration (status flips to `active`, `reviewed_by`/`reviewed_at` set); the owner can reject with a reason (status flips to `rejected`, `rejected_reason` set); rejecting without a reason is blocked; an editor (non-owner) cannot approve or reject (403); approving/rejecting an already-decided integration is refused; **the form owner's own newly-saved URL still requires a separate approve action — it is not automatically `active` after the owner's own save.**
- `tests/Unit/FormSubmissionIntegrationDispatcherTest.php` (or extending existing dispatcher tests if any exist — check first) — dispatch only reaches `active` integrations; a `pending_approval` or `rejected` row for the same form is never POSTed to, even alongside an `active` row for a different slot on the same form.
- `tests/Feature/FormIntegrationBackfillTest.php` — running the migration against a form with a pre-existing `settings.webhook_url` value creates an `active` `FormIntegration` row for it (verified via a migration-specific test or an inline assertion in the migration's own test harness, matching this app's existing convention for migration-behavior tests if one exists — otherwise a straightforward `RefreshDatabase` test that seeds a `Form` with `settings` set before migrating, if this app's test harness allows partial migration control; if it does not, this is verified via manual tinker inspection instead, documented as such, not skipped silently).

## Explicitly out of scope

- Notifying the proposer of the owner's decision — only the owner sees the outcome (inline, in the same Builder page they'd already be on); matches this session's identical exclusion in Dot.Ehail/Dot.Emall/Dot.Files.
- Re-review of an integration that's already `active` and unchanged — only a genuine URL change (or first-time set) enters the pending flow.
- A dedicated global operator review screen — unnecessary here since the reviewer (the form owner) already has full access to the exact page where the change happened; this is a deliberate departure from the Ehail/Emall/Files/ChartSense operator-dashboard pattern, justified by this platform's existing role hierarchy.
- Migrating the `crm_provider` setting or any other non-URL `Form.settings` key — untouched, only the five `*_url` keys move to `FormIntegration`.
- Registering this change in Dot.Brain's `platforms/dot-forms.md` or `platforms/autonomy-signals.json` — a separate, future re-audit pass, not part of building the feature.
