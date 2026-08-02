<div align="center">

<img src="docs/logo.svg" alt="Dot.Forms" width="320" />

<br /><br />

**Build, publish, and analyse forms without writing code.**

<br />

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white) ![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white) ![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9?style=flat-square) ![PostgreSQL](https://img.shields.io/badge/PostgreSQL-pgsql-336791?style=flat-square&logo=postgresql&logoColor=white)

<br /><br />

**Part of the [InfoDot Ecosystem](https://github.com/sakhileb/InfoDot)** &nbsp;·&nbsp; `forms.infodot.app`

</div>

---

## What is Dot.Forms?

Dot.Forms is the team-scoped form-building and submission-management platform in the InfoDot ecosystem. Teams build forms with a field builder, publish them at a public slug, collect responses, and review or export submissions. An OpenAI-backed builder can generate a starting form from a plain-language prompt (falling back to genuine keyword-based heuristics if no API key is configured — never a fake result), and a rule-based analytics pass summarizes submissions without requiring an LLM at all.

See [`wiki.md`](wiki.md) for the full, code-verified breakdown of what's built versus aspirational.

## Core Features

- Field builder — text, email, number, textarea, select, radio, checkbox, date, and file fields, with per-field settings and drag-style reordering
- AI-powered form generation from a plain-language prompt (OpenAI, with an honest non-AI fallback)
- Conditional logic notes and AI-suggested validation rules
- Form versioning with one-click revert to a previous snapshot
- Submission dashboard with search, CSV/Excel export, and a GDPR-style per-user data export
- Webhook/Slack/Zapier/Make/CRM delivery of submissions to external systems
- Per-form collaborator roles (viewer/editor/owner) layered on top of team membership
- Ecosystem SSO from the InfoDot hub (`EcosystemAuthController`, verified against the ecosystem-wide Sanctum contract)

## Domain Models

- **Form** — a team-owned form with JSON `settings` (confirmation message, schedule, webhooks, theme, consent, quiz config)
- **FormField** — individual field definition
- **FormSubmission** — captured response, keyed by field ID
- **FormUserRole** — per-form collaborator role
- **FormVersion** — a snapshot of a form for revert
- **AiSuggestion** — log of AI actions applied to a form

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.3+ |
| Frontend | Livewire 3 · Tailwind CSS (plus Alpine.js via CDN in the legacy layout) |
| Database | PostgreSQL (shared `infodot` database across the ecosystem) |
| Auth | Laravel Sanctum 4 (InfoDot SSO) + Jetstream 5 Teams |
| AI | OpenAI Chat Completions (`OPENAI_API_KEY`), with a rule-based fallback when no key is set |
| Storage | Local/S3 via Flysystem (`config('filesystems')`) |
| Exports | Laravel Excel (`maatwebsite/excel`) for CSV/XLSX |
| Queue | Database queue driver by default; jobs are dispatched synchronously from Livewire today |

**Not currently implemented, despite related env vars or historical docs suggesting otherwise:** Laravel Reverb (real-time collaboration is Cache+polling, not broadcasting), Laravel Scout/Meilisearch (search is plain SQL), Redis/Laravel Horizon. See `wiki.md` §4 for details.

## Quick Start

```bash
git clone https://github.com/sakhileb/Dot.Forms.git
cd Dot.Forms
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate
php artisan serve
```

> **Ecosystem SSO:** Set `DB_*` env vars to the shared InfoDot PostgreSQL instance and `APP_URL=https://forms.infodot.app`. Users authenticated through InfoDot gain access automatically via Sanctum handoff tokens.

## Ecosystem

**Dot.Forms** is one of **21 platforms** in the InfoDot ecosystem, connected via shared PostgreSQL and Sanctum SSO. Visit [InfoDot](https://github.com/sakhileb/InfoDot) to explore the full platform map.

## License

MIT © [SK Digital / BluPin Incorporated](https://github.com/sakhileb)
