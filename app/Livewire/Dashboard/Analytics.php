<?php

namespace App\Livewire\Dashboard;

use App\Models\Form;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Analytics extends Component
{
    public array $stats = [
        'total_submissions' => 0,
        'completion_rate' => 0,
        'avg_completion_seconds' => 0,
    ];

    public array $submissionLabels = [];

    public array $submissionData = [];

    public array $formLabels = [];

    public array $formData = [];

    public function mount(): void
    {
        $team = Auth::user()?->currentTeam;

        abort_unless($team, 403);

        $forms = Form::query()
            ->where('team_id', $team->id)
            ->withCount('submissions')
            ->get();

        $totalSubmissions = (int) $forms->sum('submissions_count');
        $totalViews = (int) $forms->sum('views_count');
        $completionRate = $totalViews > 0 ? round(($totalSubmissions / $totalViews) * 100, 1) : 0;

        $avgCompletionSeconds = (int) round(
            $forms
                ->flatMap(fn ($form) => $form->submissions()->pluck('completion_seconds'))
                ->filter(fn ($value) => $value !== null)
                ->avg() ?? 0
        );

        $this->stats = [
            'total_submissions' => $totalSubmissions,
            'completion_rate' => $completionRate,
            'avg_completion_seconds' => $avgCompletionSeconds,
        ];

        $byDay = collect(range(6, 0))
            ->mapWithKeys(function ($daysAgo) use ($forms) {
                $date = now()->subDays($daysAgo)->toDateString();
                $count = 0;

                foreach ($forms as $form) {
                    $count += $form->submissions()
                        ->whereDate('submitted_at', $date)
                        ->count();
                }

                return [$date => $count];
            });

        $this->submissionLabels = $byDay->keys()->map(fn ($d) => now()->parse($d)->format('M d'))->values()->all();
        $this->submissionData = $byDay->values()->all();

        $topForms = $forms
            ->sortByDesc('submissions_count')
            ->take(6)
            ->values();

        $this->formLabels = $topForms->map(fn ($form) => $form->title)->all();
        $this->formData = $topForms->map(fn ($form) => $form->submissions_count)->all();
    }

    public function render()
    {
        return view('livewire.dashboard.analytics');
    }
}
