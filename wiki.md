---
title: Dot.Forms — Platform Wiki
version: 0.4.0
status: draft
owners: [Forms Platform Lead]
platform-id: dot-forms
last-review: 2026-08-03
---

# Dot.Forms

Purpose: this is Dot.Forms' own knowledge home — owned and maintained by the Dot.Forms team. It describes what this platform actually is, as implemented, and how it connects to the wider Dot Ecosystem. Dot.Brain never edits this file; it only reads what we choose to publish.

> **Related:** [Dot.Brain's ingested view of this platform](https://github.com/sakhilebhayi/Dot.Brain/blob/main/platforms/dot-forms.md) — this document does not exist yet; a separate Dot.Brain-side process creates it when Dot.Forms is registered/ingested.

---

## 1. What Dot.Forms Is

Dot.Forms is the team-scoped, dynamic form-builder platform in the InfoDot ecosystem: teams build forms with a drag-and-drop-style field editor, publish them at a public slug, collect responses, and review/export submissions. It is a real, substantially-built Laravel 13 / Jetstream 5 / Livewire 3 application — not a scaffold. Unlike some other platforms audited in this ecosystem pass, this one arrived with genuine per-team authorization already wired through every entry point (see §6).

**Status:** working application with a deep, real feature set — forms, fields, submissions, versioning, per-form collaborator roles, CSV/Excel export, webhook/CRM dispatch on submission, a rule-based (not LLM-backed by default) submission analyzer, and an optional OpenAI-backed AI form generator with an honest non-AI fallback. Some ecosystem-standard infrastructure (Reverb, Scout/Meilisearch, Horizon/Redis) is referenced in env config or historical docs but not actually wired into working code — see §4.

## 2. Architecture

| Layer | Technology | Notes |
|---|---|---|
| Framework | Laravel 13, PHP 8.3+ | `composer.json` pins `laravel/framework: ^13.0` — note the old README claimed Laravel 12; corrected in this pass |
| UI | Livewire 3.6, Alpine.js (CDN, in the legacy `layouts/app.blade.php`), Tailwind CSS | Two parallel layout systems both actively used — see §2.1 |
| Database | PostgreSQL (`DB_CONNECTION=pgsql`) | Shared InfoDot instance — `DB_DATABASE` was left commented out (defaulting to `laravel`) before this pass; corrected to `DB_DATABASE=infodot` in `.env.example` to match every other platform |
| Auth | Laravel Sanctum 4 + `App\Http\Controllers\Auth\EcosystemAuthController` | SSO handoff from the InfoDot hub at `/auth/ecosystem` — verified to match the ecosystem-wide contract exactly (see §5) |
| Teams | Jetstream 5.5 Teams | `Team hasMany Form`; every form-related route and Livewire `mount()` gates on team membership |
| Realtime | Env vars present (`REVERB_*`, `BROADCAST_CONNECTION=reverb`) | **Not actually implemented.** `laravel/reverb` is not a composer dependency, there are no broadcast/event classes. The "real-time cursor" / collaborative-editing feature in `Livewire/Forms/Builder` is implemented via `Cache` + `wire:poll.20s`, not Echo/Reverb broadcasting |
| Search | None | No Laravel Scout / Meilisearch dependency exists despite the old README's tech table claiming it. Form search (`Livewire/Forms/Index`) is a plain `where(...) orWhere(...)` SQL query |
| Queue | `QUEUE_CONNECTION=database` | No Redis/Horizon dependency in `composer.json` despite the old README claiming it. `AnalyzeFormSubmissionsJob` and `GenerateAiFormBlueprintJob` are real `ShouldQueue` jobs, dispatched synchronously (`dispatchSync`) from the Livewire components that use them |
| AI | OpenAI (`services.openai.key` / `OPENAI_API_KEY`), with an honest rule-based fallback | Not Anthropic Claude, despite the old README claiming `claude-sonnet-4-6`. See §4 |
| Exports | `maatwebsite/excel` | Real CSV/XLSX submission export, falls back to CSV if the `zip` PHP extension is missing for XLSX |
| Storage | `Illuminate\Support\Facades\Storage` via `config('dotforms.forms.upload_disk')`, default `public` | File-type field uploads and per-form logo uploads |

### 2.1 Two layout systems (real finding, not a bug — but worth flagging)

The repo ships **two independent Blade layout pairs**, both live:
- `resources/views/components/layouts/{app,guest}.blade.php` — used via Livewire's `#[Layout('components.layouts.app')]` attribute on every full-page Livewire component (`Builder`, `Submissions`, `AiBuilder`, `AiFieldSuggestion`, `AiAnalytics`, `PublicView`, `Dashboard\Analytics`).
- `resources/views/layouts/{app,guest}.blade.php` — Jetstream's stock layout, rendered through `<x-app-layout>` / `<x-guest-layout>` Blade components (`App\View\Components\AppLayout`/`GuestLayout`), used by `dashboard.blade.php`, all `auth/*` pages, `profile/show.blade.php`, `teams/*.blade.php`, and more.

Before this pass, only the first pair had the real `dot_forms.png` logo wired into its brand mark; the second (more widely used) pair rendered a generic Material Symbols icon (`edit_document`) in the sidebar brand slot and had **no favicon tag at all**. Both pairs are now consistent (see §3). This dual-layout situation itself is left as-is per the "extend, don't restructure" rule — consolidating them would be a larger, riskier change than this bounded pass should make.

## 3. Branding (verified and completed this pass)

`dot_forms.png` (2362×2362 PNG, root of repo — confirmed a genuine designed mark, not a placeholder) was already copied to `public/images/dot_forms.png` and referenced in `application-logo.blade.php`, `application-mark.blade.php`, and `authentication-card-logo.blade.php`. What was missing, and fixed in this pass:

- Proper multi-size favicons generated via `sips` (`public/apple-touch-icon.png` 180×180, `public/favicon-32x32.png`, `public/favicon-16x16.png`) — previously every `<head>` linked the *full 2362×2362 original* directly as the favicon, or (in the legacy layout pair) had no favicon link at all.
- `resources/views/components/layouts/app.blade.php`, `.../guest.blade.php`, `resources/views/layouts/app.blade.php`, `.../guest.blade.php`, and `resources/views/welcome.blade.php` all now use the same three-tag favicon block (`32x32`, `16x16`, `apple-touch-icon`) matching the pattern already established in Dot.Billing and the rest of the ecosystem.
- The legacy sidebar layout's brand-icon slot (`resources/views/layouts/app.blade.php`) was switched from a generic Material Symbols icon to the actual `dot_forms.png` mark.

## 4. What Exists Today vs. What's Aspirational

**Built and real:**
- Full form lifecycle: create, drag-style field builder, draft/publish/archive/delete, duplicate, version history with revert (`FormVersion`)
- Public form rendering at `/forms/{slug}` with honeypot + timing-based spam checks, rate limiting, optional consent checkbox, optional quiz scoring, file-upload fields
- Submissions table with search, CSV/XLSX export, per-submission detail view, bulk delete, and a GDPR-style per-user data export (`Submissions::exportUserData`)
- Per-form collaborator roles (`FormUserRole`: viewer/editor/owner) layered on top of team-level Jetstream roles
- Webhook/Slack/Zapier/Make/CRM dispatch on submission (`FormSubmissionIntegrationDispatcher`) — real HTTP calls, not stubs
- AI form generation (`AiFormGenerator`) — calls OpenAI's Chat Completions API when `OPENAI_API_KEY` is set, and falls back to genuine keyword-based field heuristics (not a canned "AI thinking" fake) when it isn't
- AI submission analysis (`AiSubmissionAnalyzer`) — this one is **not** LLM-backed at all, by design: it's a real statistical/keyword pass (top-option stats, naive positive/negative word-count sentiment, completion-rate recommendations) run synchronously in `AnalyzeFormSubmissionsJob`
- Scheduled command `forms:close-expired` closing published forms past their configured `close_at`

**Present in config/env or old docs but not actually implemented:**
- Laravel Reverb — env vars exist, package not installed, no broadcast classes; the "presence"/"real-time cursor" feature is Cache+polling, not broadcasting
- Laravel Scout / Meilisearch — no dependency, no scout config; search is plain SQL `LIKE`
- Redis / Laravel Horizon — no dependency; queue driver is `database`
- Anthropic Claude — the old README claimed `claude-sonnet-4-6` for AI features; the actual, only LLM integration in the codebase is OpenAI (`services.openai.key`), and it has a non-LLM fallback path

## 5. SSO Contract Verification

`app/Http/Controllers/Auth/EcosystemAuthController.php` was checked against the ecosystem-wide pattern and matches exactly:

```php
$accessToken = PersonalAccessToken::findToken($request->query('token'));
abort_if(
    ! $accessToken
    || ! $accessToken->can('ecosystem:read')
    || ($accessToken->expires_at && $accessToken->expires_at->isPast()),
    403
);
$user = $accessToken->tokenable;
$accessToken->delete();       // one-time use
Auth::login($user);
return redirect()->route('dashboard');   // this app's own home route
```

Sanctum `PersonalAccessToken` lookup, `ecosystem:read` ability check, expiry check, one-time-use delete via `$accessToken->delete()`, `Auth::login()`, redirect to `route('dashboard')` — all present, all correct, no changes needed.

`config/database.php` was already correct (`env('DB_DATABASE', ...)` pattern matching every other platform's config file, byte-for-byte identical structure to Dot.Billing's). The gap was in `.env.example`: `DB_DATABASE` was commented out (`# DB_DATABASE=laravel`), so a fresh checkout would default to a `laravel` database instead of the shared `infodot` one every other platform uses. `DB_USERNAME=infodot` was already set, which made the mismatch easy to miss. Fixed in this pass: `.env.example` now has `DB_DATABASE=infodot` uncommented.

## 6. Security & Tech-Debt Scan

The bounded, single-focus scan for this pass targeted the ecosystem's most common real bug class: cross-tenant/cross-user IDOR via an unscoped `Model::find($id)` or an unchecked Livewire method argument, specifically on form/submission access.

**Result: no fix was needed.** Every entry point that resolves a `Form`, `FormSubmission`, or `FormVersion` by ID was already properly scoped:

- Every full-page Livewire component taking `Team $team` (and often `Form $form`) via route binding calls `Gate::authorize('view', $team)` in `mount()`, and where a `Form` is also bound, follows it with `abort_unless((int) $form->team_id === (int) $team->id, 404)` before doing anything else — this closes the specific gap of "form ID belongs to a different team than the URL's team slug."
- Team-scoped write/edit gates (`canCreateForm`, `canEditForm`, `canViewSubmissions`) are defined once in `TeamPolicy` and registered as `Gate::define(...)` shims in `AppServiceProvider`, then checked consistently — `Livewire/Forms/Index`, `Builder`, `Submissions`, `AiBuilder`, `AiFieldSuggestion`, `AiAnalytics` all call the appropriate one.
- Every by-ID lookup for a submission or version is done through the parent `Form`'s relation, not a bare model lookup — e.g. `Submissions::viewSubmission()` uses `$this->form->submissions()->findOrFail($submissionId)`, `Builder::revertToVersion()` uses `$this->form->versions()->findOrFail($versionId)`, `Index::duplicateForm/archiveForm/deleteForm` use `$this->team->forms()->findOrFail($formId)`. None of these accept a bare ID and fetch the model globally.
- `Form.editableBy()` / `Form.viewableSubmissionsBy()` correctly layer per-form collaborator roles (`FormUserRole`) on top of team ownership, and both Builder and Submissions call these as an *additional* allow path alongside the team gate, not a replacement for it.
- `PublicView` (the only genuinely public, unauthenticated route) is properly restricted: it only resolves published, non-archived forms by slug, and preview mode (`?preview=1`) requires `Auth::check()` plus `Gate::authorize('canEditForm', $this->form->team)`.

This is a materially stronger starting position than the "single most common bug" framing in the task brief assumed — it did not apply here. No authorization code was changed.

**Fixed this pass (2026-08-02):**
- The webhook/CRM SSRF gap flagged below is closed. `App\Support\SsrfGuard::isSafeUrl()` rejects any URL whose scheme isn't `http`/`https`, whose host is `localhost`, or that resolves (directly or via DNS) to a loopback, private (RFC1918), link-local (including the `169.254.169.254` cloud metadata endpoint), or otherwise reserved IP — using PHP's built-in `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`, not a hand-rolled range list. It's enforced twice: `App\Rules\SafeWebhookUrl` at `Builder::persist()` validation time (rejects bad URLs before they're ever saved), and again in `FormSubmissionIntegrationDispatcher::dispatch()` immediately before each outbound request (defends against DNS rebinding — a hostname that resolved safely at save time can resolve differently by dispatch time). Redirect-following is also disabled on the outbound `Http::post()` call, closing the redirect-based SSRF bypass a resolved-safe URL could otherwise still enable.

**Flagged, not fixed (out of scope for this bounded pass):**
- `Builder::sanitizeCustomCss()` strips tags, `@import`, `expression()`, and `javascript:` from team-supplied custom CSS before it's presumably rendered somewhere for the public form theme. This is a reasonable but non-exhaustive CSS sanitizer (e.g. no defense against CSS exfiltration via `url()`-based attribute selectors). Not fixed inline — flagged for a dedicated CSS-sanitization review if custom CSS rendering expands.

## 7. Domain Entities

Source: `database/migrations/*` and `app/Models/*`.

| Model | Table | Purpose |
|---|---|---|
| `Form` | `forms` | Team-owned form — title, slug, description, JSON `settings` (confirmation message, response limits, schedule, webhooks, theme, GDPR retention/consent, quiz config, CRM config), publish state |
| `FormField` | `form_fields` | Field definition — type, label, placeholder, JSON `options` (choices/helper text/conditional logic), JSON `validation_rules`, order |
| `FormSubmission` | `form_submissions` | Captured response — JSON `data` keyed by field ID, timing, quiz score, IP/user agent |
| `FormUserRole` | `form_user_roles` | Per-form collaborator role (viewer/editor/owner), layered on top of Jetstream team membership |
| `FormVersion` | `form_versions` | Snapshot of a form's title/description/settings/fields at a point in time, for revert |
| `AiSuggestion` | `ai_suggestions` | Log of every AI-generated action applied to a form (field suggestion, label enhancement, submission analysis, blueprint generation) |

## 8. Events Emitted

**None.** There are no Laravel domain event/listener classes in this repository. The closest things to "events" are: a `mail`-only notification (`NewFormSubmissionNotification`, sent to the form owner on new submissions) and outbound webhook payloads (`form.submitted`) sent directly via HTTP to team-configured URLs — neither is a Dot.Brain-consumable domain event today. Any future Knowledge Pack publishing would need real event classes first, following the same gap Dot.Billing's wiki documents for that platform.

## 9. Connecting to Dot.Brain

Dot.Forms is not yet registered in Dot.Brain's platform map at the time of this writing. This wiki is the platform-owned knowledge document Dot.Brain would ingest to register it. Dot.Brain's ingested view — once created — will live at [`platforms/dot-forms.md`](https://github.com/sakhilebhayi/Dot.Brain/blob/main/platforms/dot-forms.md); that file does not exist yet and is created by a separate Dot.Brain-side process, not by this repository.

Until domain events (§8) exist, any Knowledge Pack this platform could publish would have to be generated by a manual/batch process reading `forms`, `form_submissions`, and `ai_suggestions` directly — not real-time event capture. Likely payload shapes once that exists:

| Payload type | Would contain |
|---|---|
| `observation` | Aggregated form/submission volume, completion-rate metrics per team — never individual submission content |
| `insight` | Patterns surfaced by `AiSubmissionAnalyzer` (completion drop-off by field, sentiment distribution), generalized across forms |
| `outcome` | Verification of any Dot.Brain recommendation (e.g., "reword field X" suggestions) |
| `incident` | Spam/abuse spikes caught by the rate limiter, integration dispatch failures |

Given that `form_submissions.data` can contain PII the form owner collected from respondents, any aggregation published outward should default to at least as strict an anonymity floor as Dot.Billing's settlement-domain proposal (n≥50), and individual submission content should never leave this platform's own database. No aggregation or publishing code exists yet — this is a requirement to design in before publishing begins, not a shipped guarantee.

## 10. Roadmap / Open Questions

- [ ] Decide whether to consolidate the two parallel Blade layout systems (§2.1) or keep both — flagged, not touched, in this pass
- [ ] Real-time collaborative editing: either install `laravel/reverb` and wire actual broadcasting, or stop implying real-time presence beyond the current Cache+polling mechanism in user-facing copy
- [ ] Domain events for form publish/submission/close (prerequisite for any Knowledge Pack publishing)
- [x] ~~SSRF hardening for team-configured webhook/CRM URLs in `FormSubmissionIntegrationDispatcher`~~ — fixed 2026-08-02, see §6
- [ ] Decide on the actual AI provider story: keep OpenAI + heuristic fallback as-is, or move to a shared ecosystem Anthropic client if/when one exists (the old README's Claude claim was aspirational/incorrect and has been corrected in docs, not in code)
- [ ] Aggregation-floor enforcement before any outward-facing Knowledge Pack publishing begins
- [ ] `tasklist.md` at the repo root marks nearly everything as done (`[x]`), including items this pass could not verify are true given the Reverb/Scout/Horizon gaps found — treat that file as an intent record, not a status record

## Change Log

| Version | Date | Author | Change |
|---|---|---|---|
| 0.4.0 | 2026-08-03 | Sakhile Bhayi | Redesigned `resources/views/welcome.blade.php`'s marketing surface. The nav/footer/mock-card brand marks already referenced the real `public/images/dot_forms.png` mark (Dot.Forms is the one platform in the ecosystem whose real logo file is *not* named `images/logo.png` — confirmed via `ls public/images/` before editing), so no logo change was needed there. What changed: the hero's abstract `.hero-bg` radial-gradient decoration and the CTA band's flat yellow-to-orange gradient are now real, licensed Unsplash photography, hotlinked via Unsplash's CDN with photographer credit as inline HTML comments — hero: a hand writing with a fountain pen on paper by Shutter Speed (@shutter_speed_), unsplash.com/photos/rz_0EzMAmis; CTA band: a clipboard checklist next to a cup of coffee by Testeur de CBD (@testeurdecbd), unsplash.com/photos/UFb4LPahwHQ — both chosen for relevance to Dot.Forms' actual domain (form/data-collection/paperwork, per §1), not generic tech imagery. Because this platform's welcome page is light-themed (white background, dark ink text, yellow/red accents) rather than Dot.Mines' dark theme, the overlay treatment was adapted rather than copied verbatim: the hero uses a white-to-transparent gradient overlay (not a dark one) so the existing dark-ink text keeps its original high contrast, and the CTA band uses a semi-opaque yellow/orange gradient overlay matching the platform's existing accent palette instead of Dot.Mines' amber theme. Both CDN URLs verified to resolve with `curl -sI ... | head -3` returning `HTTP/2 200` before committing. |
| 0.3.0 | 2026-08-02 | Sakhile Bhayi | **Real execution, twice.** First run against a real PHP/Postgres toolchain found `create_form_fields_table` and `create_forms_table` shared an identical migration timestamp, and Laravel's alphabetical tiebreak ran the FK-dependent one first — fixed by renaming `create_forms_table` a second earlier (would have fatally broken any fresh `php artisan migrate`). Second: found the six shared Jetstream-core migrations collide when a second platform migrates against the same real `infodot` database (Dot.Billing's `two_factor_secret` column already existed) — guarded all six with `Schema::hasTable`/`hasColumn` checks per Dot.Brain ADR-0013, then verified by running Dot.Billing → Dot.Forms → Dot.Tutor's migrations back-to-back against one database with zero errors. |
| 0.2.0 | 2026-08-02 | Forms Platform Lead | Second pass: closed the SSRF gap flagged in 0.1.0 — `App\Support\SsrfGuard` rejects webhook/CRM URLs that resolve to loopback/private/link-local addresses (incl. the cloud metadata endpoint), enforced at both settings-save time (`App\Rules\SafeWebhookUrl`) and dispatch time (`FormSubmissionIntegrationDispatcher`, which also now disables redirect-following). No other changes this pass. |
| 0.1.0 | 2026-08-02 | Forms Platform Lead | Initial platform-owned wiki, derived from the actual Laravel codebase (models, migrations, routes, Livewire components, policies). Verified the `EcosystemAuthController` SSO contract matches the ecosystem pattern exactly. Fixed `.env.example` `DB_DATABASE` (was commented out/defaulting to `laravel`, now `infodot`). Completed favicon/logo wiring across both parallel layout systems (`sips`-generated `apple-touch-icon.png`, `favicon-32x32.png`, `favicon-16x16.png`; replaced a generic Material Symbols brand icon in the legacy sidebar layout with the real `dot_forms.png` mark). Ran a focused IDOR/cross-tenant-access security scan on form/submission access — found the codebase already properly scoped everywhere checked, so no authorization code was changed; two lower-priority findings (webhook SSRF surface, non-exhaustive custom-CSS sanitizer) were flagged rather than fixed inline, per the bounded-pass rule. Corrected README's inaccurate Laravel 12 / Anthropic Claude / Reverb / Scout+Meilisearch / Redis+Horizon claims to match what's actually in `composer.json` and `app/`. |
