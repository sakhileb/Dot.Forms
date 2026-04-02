<?php

use App\Livewire\Dashboard\Analytics as DashboardAnalytics;
use App\Livewire\Forms\Builder;
use App\Livewire\Forms\AiAnalytics;
use App\Livewire\Forms\AiBuilder;
use App\Livewire\Forms\AiFieldSuggestion;
use App\Livewire\Forms\PublicView;
use App\Livewire\Forms\Submissions;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dashboard/analytics', DashboardAnalytics::class)
        ->name('dashboard.analytics');

    Route::get('/teams/{team}/forms', function (Team $team) {
        Gate::authorize('view', $team);

        return view('teams.forms', [
            'team' => $team,
        ]);
    })->name('teams.forms');

    Route::get('/teams/{team}/forms/{form}/builder', Builder::class)
        ->name('teams.forms.builder');

    Route::get('/teams/{team}/forms/{form}/submissions', Submissions::class)
        ->name('teams.forms.submissions');

    Route::get('/teams/{team}/forms/ai-builder', AiBuilder::class)
        ->name('teams.forms.ai-builder');

    Route::get('/teams/{team}/forms/{form}/ai-suggestions', AiFieldSuggestion::class)
        ->name('teams.forms.ai-suggestions');

    Route::get('/teams/{team}/forms/{form}/ai-analytics', AiAnalytics::class)
        ->name('teams.forms.ai-analytics');
});

Route::get('/forms/{slug}', PublicView::class)->name('forms.public');
