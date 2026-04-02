<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Models\FormUserRole;
use App\Models\FormVersion;
use App\Models\Team;
use App\Services\Ai\AiFieldSuggestionEngine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Builder extends Component
{
    use WithFileUploads;

    public Team $team;

    public Form $form;

    public string $title = '';

    public ?string $description = null;

    public array $settings = [];

    public array $fields = [];

    public $logoUpload;

    public array $formRoles = [];

    public ?int $selectedMemberId = null;

    public string $selectedMemberRole = 'viewer';

    public array $activeEditors = [];

    public array $versions = [];

    public bool $showFieldSettingsModal = false;

    public ?int $editingFieldIndex = null;

    public ?int $selectedVersionId = null;

    /**
     * @var array<int, string>
     */
    public array $fieldTypes = [
        'text',
        'email',
        'number',
        'textarea',
        'select',
        'radio',
        'checkbox',
        'date',
        'file',
    ];

    public function mount(Team $team, Form $form): void
    {
        Gate::authorize('view', $team);

        abort_unless((int) $form->team_id === (int) $team->id, 404);

        $user = Auth::user();

        if (! $user || (! Gate::forUser($user)->allows('canEditForm', $team) && ! $form->editableBy($user))) {
            abort(403);
        }

        $this->team = $team;
        $this->form = $form;
        $this->hydrateState();
        $this->refreshPresence();
    }

    public function addField(string $type): void
    {
        Gate::authorize('canEditForm', $this->team);

        if (! in_array($type, $this->fieldTypes, true)) {
            return;
        }

        $this->fields[] = [
            'id' => null,
            'key' => (string) Str::uuid(),
            'type' => $type,
            'label' => Str::headline($type).' Field',
            'placeholder' => null,
            'options' => '',
            'required' => false,
            'helper_text' => null,
            'conditional_logic' => null,
        ];
    }

    public function removeField(int $index): void
    {
        Gate::authorize('canEditForm', $this->team);

        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveFieldUp(int $index): void
    {
        if ($index < 1) {
            return;
        }

        [$this->fields[$index - 1], $this->fields[$index]] = [$this->fields[$index], $this->fields[$index - 1]];
        $this->fields = array_values($this->fields);
    }

    public function moveFieldDown(int $index): void
    {
        if ($index >= count($this->fields) - 1) {
            return;
        }

        [$this->fields[$index + 1], $this->fields[$index]] = [$this->fields[$index], $this->fields[$index + 1]];
        $this->fields = array_values($this->fields);
    }

    /**
     * @param  array<int, string>  $orderedKeys
     */
    public function reorderFields(array $orderedKeys): void
    {
        $lookup = [];

        foreach ($this->fields as $field) {
            $lookup[$field['key']] = $field;
        }

        $reordered = [];

        foreach ($orderedKeys as $key) {
            if (isset($lookup[$key])) {
                $reordered[] = $lookup[$key];
                unset($lookup[$key]);
            }
        }

        foreach ($lookup as $remaining) {
            $reordered[] = $remaining;
        }

        $this->fields = $reordered;
    }

    public function openFieldSettings(int $index): void
    {
        $this->editingFieldIndex = $index;
        $this->showFieldSettingsModal = true;
    }

    public function closeFieldSettings(): void
    {
        $this->showFieldSettingsModal = false;
        $this->editingFieldIndex = null;
    }

    public function saveDraft(): void
    {
        $this->persist();

        $this->form->update([
            'is_published' => false,
            'published_at' => null,
            'archived_at' => null,
        ]);

        session()->flash('status', 'Draft saved.');
    }

    public function publish(): void
    {
        $this->persist();

        $this->form->update([
            'is_published' => true,
            'published_at' => now(),
            'archived_at' => null,
        ]);

        session()->flash('status', 'Form published.');
    }

    public function autoSave(): void
    {
        $this->persist(false);
        $this->refreshPresence();
    }

    public function assignMemberRole(): void
    {
        $this->validate([
            'selectedMemberId' => ['required', 'integer'],
            'selectedMemberRole' => ['required', 'in:viewer,editor,owner'],
        ]);

        FormUserRole::query()->updateOrCreate(
            [
                'form_id' => $this->form->id,
                'user_id' => $this->selectedMemberId,
            ],
            [
                'role' => $this->selectedMemberRole,
            ]
        );

        $this->form->refresh();
        $this->hydrateState();
        session()->flash('status', 'Form role updated.');
    }

    public function removeMemberRole(int $userId): void
    {
        $this->form->userRoles()->where('user_id', $userId)->delete();
        $this->form->refresh();
        $this->hydrateState();
        session()->flash('status', 'Form role removed.');
    }

    public function revertToVersion(int $versionId): void
    {
        $version = $this->form->versions()->findOrFail($versionId);

        $this->form->update([
            'title' => $version->title,
            'description' => $version->description,
            'settings' => $version->settings,
            'current_version' => $version->version_number,
        ]);

        $this->form->fields()->delete();

        foreach (($version->fields_snapshot ?? []) as $index => $field) {
            $this->form->fields()->create([
                'type' => (string) ($field['type'] ?? 'text'),
                'label' => (string) ($field['label'] ?? 'Untitled Field'),
                'placeholder' => $field['placeholder'] ?? null,
                'options' => $field['options'] ?? [],
                'validation_rules' => $field['validation_rules'] ?? [],
                'order' => $index + 1,
            ]);
        }

        $this->form->refresh();
        $this->hydrateState();
        session()->flash('status', 'Form reverted to version #'.$version->version_number.'.');
    }

    public function suggestValidationRules(): void
    {
        foreach ($this->fields as $index => $field) {
            $label = Str::lower((string) ($field['label'] ?? ''));
            $type = (string) ($field['type'] ?? 'text');

            if (str_contains($label, 'email') && $type !== 'email') {
                $this->fields[$index]['type'] = 'email';
            }

            if (str_contains($label, 'phone') && $type === 'number') {
                $this->fields[$index]['type'] = 'text';
            }

            if (str_contains($label, 'name') || str_contains($label, 'email')) {
                $this->fields[$index]['required'] = true;
            }
        }

        session()->flash('status', 'AI suggested validation updates applied.');
    }

    public function suggestConditionalLogicForEditingField(): void
    {
        if ($this->editingFieldIndex === null) {
            return;
        }

        $index = $this->editingFieldIndex;
        $current = $this->fields[$index] ?? null;
        $previous = $index > 0 ? ($this->fields[$index - 1] ?? null) : null;

        if (! is_array($current)) {
            return;
        }

        $this->fields[$index]['conditional_logic'] = app(AiFieldSuggestionEngine::class)
            ->suggestConditionalLogic($current, $previous);
    }

    protected function persist(bool $flashOnError = true): void
    {
        $user = Auth::user();

        if (! $user || (! Gate::forUser($user)->allows('canEditForm', $this->team) && ! $this->form->editableBy($user))) {
            abort(403);
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'settings.confirmation_message' => ['nullable', 'string', 'max:1000'],
            'settings.limit_responses' => ['nullable', 'integer', 'min:1'],
            'settings.open_at' => ['nullable', 'date'],
            'settings.close_at' => ['nullable', 'date', 'after_or_equal:settings.open_at'],
            'settings.webhook_url' => ['nullable', 'url', 'max:2048'],
            'settings.slack_webhook_url' => ['nullable', 'url', 'max:2048'],
            'settings.zapier_webhook_url' => ['nullable', 'url', 'max:2048'],
            'settings.make_webhook_url' => ['nullable', 'url', 'max:2048'],
            'settings.theme' => ['nullable', 'in:light,dark,brand'],
            'settings.brand_color' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'settings.custom_css' => ['nullable', 'string', 'max:8000'],
            'settings.retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'settings.consent_required' => ['boolean'],
            'settings.consent_label' => ['nullable', 'string', 'max:255'],
            'settings.quiz_enabled' => ['boolean'],
            'settings.quiz_answer_key_json' => ['nullable', 'string', 'max:10000'],
            'settings.conversational_mode' => ['boolean'],
            'settings.crm_provider' => ['nullable', 'in:none,hubspot,pipedrive,generic'],
            'settings.crm_webhook_url' => ['nullable', 'url', 'max:2048'],
            'fields' => ['array'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.options' => ['nullable', 'string'],
            'fields.*.required' => ['boolean'],
            'fields.*.helper_text' => ['nullable', 'string', 'max:500'],
            'fields.*.conditional_logic' => ['nullable', 'string', 'max:1000'],
        ]);

        $formData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'logo_path' => $this->form->logo_path,
            'settings' => [
                'confirmation_message' => $validated['settings']['confirmation_message'] ?? 'Thanks for your submission.',
                'limit_responses' => $validated['settings']['limit_responses'] ?? null,
                'open_at' => $validated['settings']['open_at'] ?? null,
                'close_at' => $validated['settings']['close_at'] ?? null,
                'webhook_url' => $validated['settings']['webhook_url'] ?? null,
                'slack_webhook_url' => $validated['settings']['slack_webhook_url'] ?? null,
                'zapier_webhook_url' => $validated['settings']['zapier_webhook_url'] ?? null,
                'make_webhook_url' => $validated['settings']['make_webhook_url'] ?? null,
                'theme' => $validated['settings']['theme'] ?? 'light',
                'brand_color' => $validated['settings']['brand_color'] ?? '#4f46e5',
                'custom_css' => $this->sanitizeCustomCss((string) ($validated['settings']['custom_css'] ?? '')),
                'retention_days' => $validated['settings']['retention_days'] ?? null,
                'consent_required' => (bool) ($validated['settings']['consent_required'] ?? false),
                'consent_label' => $validated['settings']['consent_label'] ?? 'I consent to processing my submitted data.',
                'quiz_enabled' => (bool) ($validated['settings']['quiz_enabled'] ?? false),
                'quiz_answer_key' => $this->parseAnswerKey((string) ($validated['settings']['quiz_answer_key_json'] ?? '')),
                'conversational_mode' => (bool) ($validated['settings']['conversational_mode'] ?? false),
                'crm_provider' => $validated['settings']['crm_provider'] ?? 'none',
                'crm_webhook_url' => $validated['settings']['crm_webhook_url'] ?? null,
            ],
        ];

        if ($this->logoUpload) {
            $formData['logo_path'] = $this->logoUpload->store('form-logos', config('dotforms.forms.upload_disk', 'public'));
        }

        $this->form->fill($formData);
        $this->form->save();

        $this->form->fields()->delete();

        foreach ($validated['fields'] as $index => $field) {
            $options = [];

            if (in_array($field['type'], ['select', 'radio', 'checkbox'], true)) {
                $options = collect(explode(',', (string) ($field['options'] ?? '')))
                    ->map(fn (string $option) => trim($option))
                    ->filter()
                    ->values()
                    ->all();
            }

            $rules = [];
            if (! empty($field['required'])) {
                $rules[] = 'required';
            }
            if ($field['type'] === 'email') {
                $rules[] = 'email';
            }
            if ($field['type'] === 'number') {
                $rules[] = 'numeric';
            }
            if ($field['type'] === 'date') {
                $rules[] = 'date';
            }

            $this->form->fields()->create([
                'type' => $field['type'],
                'label' => $field['label'],
                'placeholder' => $field['placeholder'] ?: null,
                'options' => [
                    'choices' => $options,
                    'helper_text' => $field['helper_text'] ?? null,
                    'conditional_logic' => $field['conditional_logic'] ?? null,
                ],
                'validation_rules' => $rules,
                'order' => $index + 1,
            ]);
        }

        $this->form->refresh();
        $this->createVersionSnapshot();
        $this->hydrateState();
        $this->logoUpload = null;

        if ($flashOnError) {
            session()->flash('status', 'Changes saved.');
        }
    }

    protected function hydrateState(): void
    {
        $this->title = $this->form->title;
        $this->description = $this->form->description;
        $this->settings = array_merge([
            'confirmation_message' => 'Thanks for your submission.',
            'limit_responses' => null,
            'open_at' => null,
            'close_at' => null,
            'webhook_url' => null,
            'slack_webhook_url' => null,
            'zapier_webhook_url' => null,
            'make_webhook_url' => null,
            'theme' => 'light',
            'brand_color' => '#4f46e5',
            'custom_css' => null,
            'retention_days' => null,
            'consent_required' => false,
            'consent_label' => 'I consent to processing my submitted data.',
            'quiz_enabled' => false,
            'quiz_answer_key' => [],
            'conversational_mode' => false,
            'crm_provider' => 'none',
            'crm_webhook_url' => null,
        ], $this->form->settings ?? []);

        $this->settings['quiz_answer_key_json'] = json_encode($this->settings['quiz_answer_key'] ?? [], JSON_PRETTY_PRINT);

        $this->formRoles = $this->form
            ->userRoles()
            ->with('user')
            ->get()
            ->map(fn (FormUserRole $role) => [
                'user_id' => $role->user_id,
                'user_name' => $role->user?->name,
                'role' => $role->role,
            ])
            ->values()
            ->all();

        $this->versions = $this->form
            ->versions()
            ->latest('version_number')
            ->take(10)
            ->get(['id', 'version_number', 'created_at'])
            ->map(fn (FormVersion $version) => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'created_at' => optional($version->created_at)?->toDateTimeString(),
            ])
            ->all();

        $this->fields = $this->form->fields()
            ->orderBy('order')
            ->get()
            ->map(function ($field): array {
                $choices = $field->options['choices'] ?? [];

                return [
                    'id' => $field->id,
                    'key' => (string) Str::uuid(),
                    'type' => $field->type,
                    'label' => $field->label,
                    'placeholder' => $field->placeholder,
                    'options' => is_array($choices) ? implode(', ', $choices) : '',
                    'required' => in_array('required', $field->validation_rules ?? [], true),
                    'helper_text' => $field->options['helper_text'] ?? null,
                    'conditional_logic' => $field->options['conditional_logic'] ?? null,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.forms.builder', [
            'previewUrl' => route('forms.public', ['slug' => $this->form->slug]).'?preview=1',
            'submissionsUrl' => route('teams.forms.submissions', ['team' => $this->team, 'form' => $this->form]),
            'aiSuggestionsUrl' => route('teams.forms.ai-suggestions', ['team' => $this->team, 'form' => $this->form]),
            'aiAnalyticsUrl' => route('teams.forms.ai-analytics', ['team' => $this->team, 'form' => $this->form]),
            'logoUrl' => $this->form->logo_path ? asset('storage/'.$this->form->logo_path) : null,
            'collaborators' => $this->form->availableCollaborators(),
        ]);
    }

    protected function sanitizeCustomCss(string $css): string
    {
        $sanitized = preg_replace('/<[^>]*>/', '', $css) ?? '';
        $sanitized = preg_replace('/@import\s+[^;]+;?/i', '', $sanitized) ?? '';
        $sanitized = preg_replace('/expression\s*\([^)]*\)/i', '', $sanitized) ?? '';
        $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized) ?? '';

        return trim($sanitized);
    }

    protected function parseAnswerKey(string $json): array
    {
        $trimmed = trim($json);

        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function createVersionSnapshot(): void
    {
        $nextVersion = (int) $this->form->versions()->max('version_number') + 1;

        $fields = $this->form->fields()
            ->orderBy('order')
            ->get(['type', 'label', 'placeholder', 'options', 'validation_rules', 'order'])
            ->map(fn ($field) => [
                'type' => $field->type,
                'label' => $field->label,
                'placeholder' => $field->placeholder,
                'options' => $field->options,
                'validation_rules' => $field->validation_rules,
                'order' => $field->order,
            ])
            ->values()
            ->all();

        $this->form->versions()->create([
            'user_id' => Auth::id(),
            'version_number' => $nextVersion,
            'title' => $this->form->title,
            'description' => $this->form->description,
            'settings' => $this->form->settings,
            'fields_snapshot' => $fields,
        ]);

        $this->form->update([
            'current_version' => $nextVersion,
        ]);
    }

    public function refreshPresence(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $cacheKey = 'form:presence:'.$this->form->id;
        $presence = Cache::get($cacheKey, []);

        if (! is_array($presence)) {
            $presence = [];
        }

        $presence[$user->id] = [
            'name' => $user->name,
            'last_seen' => now()->timestamp,
        ];

        $cutoff = now()->subMinute()->timestamp;

        $presence = collect($presence)
            ->filter(fn ($editor) => (int) ($editor['last_seen'] ?? 0) >= $cutoff)
            ->all();

        Cache::put($cacheKey, $presence, now()->addMinutes(5));

        $this->activeEditors = collect($presence)
            ->map(fn ($editor, $id) => [
                'id' => (int) $id,
                'name' => (string) ($editor['name'] ?? 'Unknown'),
            ])
            ->values()
            ->all();
    }
}
