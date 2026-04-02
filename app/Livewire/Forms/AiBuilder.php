<?php

namespace App\Livewire\Forms;

use App\Jobs\GenerateAiFormBlueprintJob;
use App\Models\AiSuggestion;
use App\Models\Form;
use App\Models\Team;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AiBuilder extends Component
{
    public Team $team;

    public string $prompt = '';

    public string $title = 'Generated Form';

    public ?string $description = null;

    public array $fields = [];

    public bool $generated = false;

    public function mount(Team $team): void
    {
        Gate::authorize('view', $team);
        Gate::authorize('canCreateForm', $team);

        $this->team = $team;
    }

    public function generate(): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        /** @var array<string, mixed> $result */
        $result = app(Dispatcher::class)->dispatchSync(new GenerateAiFormBlueprintJob($this->prompt));

        $this->title = (string) ($result['title'] ?? 'Generated Form');
        $this->description = $result['description'] ?? null;
        $this->fields = collect($result['fields'] ?? [])->map(function ($field): array {
            return [
                'type' => (string) ($field['type'] ?? 'text'),
                'label' => (string) ($field['label'] ?? 'Untitled Field'),
                'placeholder' => $field['placeholder'] ?? null,
                'required' => (bool) ($field['required'] ?? false),
                'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
                'helper_text' => $field['helper_text'] ?? null,
            ];
        })->values()->all();
        $this->generated = true;
    }

    public function addField(): void
    {
        $this->fields[] = [
            'type' => 'text',
            'label' => 'New Field',
            'placeholder' => null,
            'required' => false,
            'options' => [],
            'helper_text' => null,
        ];
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function saveForm(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.required' => ['boolean'],
            'fields.*.helper_text' => ['nullable', 'string', 'max:500'],
        ]);

        $form = Form::query()->create([
            'team_id' => $this->team->id,
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title']),
            'description' => $validated['description'] ?? null,
            'settings' => [
                'confirmation_message' => 'Thanks for your submission.',
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
            ],
            'is_published' => false,
        ]);

        foreach ($validated['fields'] as $index => $field) {
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

            $form->fields()->create([
                'type' => $field['type'],
                'label' => $field['label'],
                'placeholder' => $field['placeholder'] ?: null,
                'options' => [
                    'choices' => $field['options'] ?? [],
                    'helper_text' => $field['helper_text'] ?? null,
                ],
                'validation_rules' => $rules,
                'order' => $index + 1,
            ]);
        }

        AiSuggestion::query()->create([
            'form_id' => $form->id,
            'field_id' => null,
            'suggestion_type' => 'form_blueprint',
            'content' => [
                'prompt' => $this->prompt,
                'title' => $validated['title'],
                'fields_count' => count($validated['fields']),
            ],
            'applied_at' => now(),
        ]);

        $this->redirectRoute('teams.forms.builder', ['team' => $this->team, 'form' => $form]);
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'form';
        $slug = $base;
        $counter = 1;

        while (Form::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.forms.ai-builder');
    }
}
