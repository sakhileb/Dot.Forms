<?php

namespace App\Livewire\Forms;

use App\Jobs\AnalyzeFormSubmissionsJob;
use App\Models\AiSuggestion;
use App\Models\Form;
use App\Models\Team;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AiAnalytics extends Component
{
    public Team $team;

    public Form $form;

    public array $analysis = [];

    public int $limit = 100;

    public function mount(Team $team, Form $form): void
    {
        Gate::authorize('view', $team);
        Gate::authorize('canViewSubmissions', $team);

        abort_unless((int) $form->team_id === (int) $team->id, 404);

        $this->team = $team;
        $this->form = $form->load('fields');
        $this->analysis = [
            'summary' => null,
            'sentiment' => [
                'positive' => 0,
                'neutral' => 0,
                'negative' => 0,
            ],
            'recommendations' => [],
        ];
    }

    public function summarize(): void
    {
        /** @var array<string, mixed> $result */
        $result = app(Dispatcher::class)->dispatchSync(new AnalyzeFormSubmissionsJob($this->form, $this->limit));

        $this->analysis = $result;

        AiSuggestion::query()->create([
            'form_id' => $this->form->id,
            'field_id' => null,
            'suggestion_type' => 'submission_analysis',
            'content' => $result,
            'applied_at' => now(),
        ]);
    }

    public function render()
    {
        return view('livewire.forms.ai-analytics');
    }
}
