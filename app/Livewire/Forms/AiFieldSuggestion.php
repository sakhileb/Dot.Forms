<?php

namespace App\Livewire\Forms;

use App\Models\AiSuggestion;
use App\Models\Form;
use App\Models\Team;
use App\Services\Ai\AiFieldSuggestionEngine;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AiFieldSuggestion extends Component
{
    public Team $team;

    public Form $form;

    public array $suggestions = [];

    /**
     * @var array<int, int>
     */
    public array $selectedSuggestionIds = [];

    public function mount(Team $team, Form $form): void
    {
        Gate::authorize('view', $team);
        Gate::authorize('canEditForm', $team);

        abort_unless((int) $form->team_id === (int) $team->id, 404);

        $this->team = $team;
        $this->form = $form->load('fields');

        $this->suggestions = app(AiFieldSuggestionEngine::class)->suggestForForm($this->form);
        $this->selectedSuggestionIds = array_keys($this->suggestions);
    }

    public function applySuggestions(): void
    {
        $selected = collect($this->suggestions)
            ->only($this->selectedSuggestionIds)
            ->values();

        $nextOrder = ((int) $this->form->fields()->max('order')) + 1;

        foreach ($selected as $suggestion) {
            $rules = [];
            if (! empty($suggestion['required'])) {
                $rules[] = 'required';
            }
            if (($suggestion['type'] ?? '') === 'email') {
                $rules[] = 'email';
            }

            $field = $this->form->fields()->create([
                'type' => $suggestion['type'],
                'label' => $suggestion['label'],
                'placeholder' => $suggestion['placeholder'] ?? null,
                'options' => [
                    'choices' => $suggestion['options'] ?? [],
                    'helper_text' => $suggestion['helper_text'] ?? null,
                ],
                'validation_rules' => $rules,
                'order' => $nextOrder,
            ]);

            $nextOrder++;

            AiSuggestion::query()->create([
                'form_id' => $this->form->id,
                'field_id' => $field->id,
                'suggestion_type' => 'field_suggestion',
                'content' => $suggestion,
                'applied_at' => now(),
            ]);
        }

        session()->flash('status', 'AI field suggestions applied.');

        $this->redirectRoute('teams.forms.builder', ['team' => $this->team, 'form' => $this->form]);
    }

    public function enhanceLabels(): void
    {
        $engine = app(AiFieldSuggestionEngine::class);

        foreach ($this->form->fields as $field) {
            $oldLabel = $field->label;
            $newLabel = $engine->enhanceLabel($oldLabel);

            if ($newLabel !== $oldLabel) {
                $field->update(['label' => $newLabel]);

                AiSuggestion::query()->create([
                    'form_id' => $this->form->id,
                    'field_id' => $field->id,
                    'suggestion_type' => 'enhance_label',
                    'content' => [
                        'before' => $oldLabel,
                        'after' => $newLabel,
                    ],
                    'applied_at' => now(),
                ]);
            }
        }

        session()->flash('status', 'Field labels enhanced.');

        $this->form->refresh();
    }

    public function render()
    {
        return view('livewire.forms.ai-field-suggestion');
    }
}
