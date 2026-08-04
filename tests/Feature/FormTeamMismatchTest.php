<?php

namespace Tests\Feature;

use App\Livewire\Forms\AiAnalytics;
use App\Livewire\Forms\AiFieldSuggestion;
use App\Livewire\Forms\Builder;
use App\Livewire\Forms\Submissions;
use App\Models\Form;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression coverage for Form::assertBelongsToTeam(), which centralizes
 * what was previously an ad-hoc
 * `abort_unless((int) $form->team_id === (int) $team->id, 404)` duplicated
 * across Builder, Submissions, AiFieldSuggestion and AiAnalytics. A form
 * belonging to one team must 404 when accessed through another team's
 * route, even when the acting user legitimately belongs to both teams
 * (e.g. an owner of two teams navigating with a stale/tampered URL).
 */
class FormTeamMismatchTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithTwoTeams(): array
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $teamA = $owner->currentTeam;
        $teamB = Team::factory()->create(['user_id' => $owner->id]);
        $owner->teams()->attach($teamB, ['role' => 'admin']);

        return [$owner, $teamA, $teamB];
    }

    private function formForTeam(Team $team, User $owner): Form
    {
        return Form::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'title' => 'Team A Form',
            'slug' => 'team-a-form-'.$team->id,
            'settings' => [],
            'is_published' => false,
        ]);
    }

    public function test_builder_404s_when_form_belongs_to_a_different_team(): void
    {
        [$owner, $teamA, $teamB] = $this->ownerWithTwoTeams();
        $form = $this->formForTeam($teamA, $owner);

        $this->actingAs($owner);

        Livewire::test(Builder::class, ['team' => $teamB, 'form' => $form])
            ->assertNotFound();
    }

    public function test_submissions_404s_when_form_belongs_to_a_different_team(): void
    {
        [$owner, $teamA, $teamB] = $this->ownerWithTwoTeams();
        $form = $this->formForTeam($teamA, $owner);

        $this->actingAs($owner);

        Livewire::test(Submissions::class, ['team' => $teamB, 'form' => $form])
            ->assertNotFound();
    }

    public function test_ai_field_suggestion_404s_when_form_belongs_to_a_different_team(): void
    {
        [$owner, $teamA, $teamB] = $this->ownerWithTwoTeams();
        $form = $this->formForTeam($teamA, $owner);

        $this->actingAs($owner);

        Livewire::test(AiFieldSuggestion::class, ['team' => $teamB, 'form' => $form])
            ->assertNotFound();
    }

    public function test_ai_analytics_404s_when_form_belongs_to_a_different_team(): void
    {
        [$owner, $teamA, $teamB] = $this->ownerWithTwoTeams();
        $form = $this->formForTeam($teamA, $owner);

        $this->actingAs($owner);

        Livewire::test(AiAnalytics::class, ['team' => $teamB, 'form' => $form])
            ->assertNotFound();
    }
}
