<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Analytics;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression coverage confirming App\Livewire\Dashboard\Analytics::mount()
 * already guards Auth::user()->currentTeam being null (a user with no
 * current team, e.g. removed from their last team) rather than fatally
 * dereferencing it -- the same class of bug fixed in Dot.Mines'
 * Dashboard::loadDashboardData() (commit 0cc4362), confirmed here to
 * already be handled via abort_unless($team, 403).
 */
class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_no_current_team_gets_403_not_a_crash(): void
    {
        $user = User::factory()->create(['current_team_id' => null]);

        $this->actingAs($user);

        Livewire::test(Analytics::class)
            ->assertForbidden();
    }
}
