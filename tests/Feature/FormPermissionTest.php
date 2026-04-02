<?php

namespace Tests\Feature;

use App\Livewire\Forms\Builder;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_edit_role_cannot_modify_form(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $viewer = User::factory()->create();

        $team = $owner->currentTeam;
        $team->users()->attach($viewer, ['role' => 'viewer']);
        $viewer->forceFill(['current_team_id' => $team->id])->save();

        $form = Form::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'title' => 'Locked Form',
            'slug' => 'locked-form',
            'settings' => [],
            'is_published' => false,
        ]);

        $this->actingAs($viewer);

        Livewire::test(Builder::class, ['team' => $team, 'form' => $form])
            ->assertForbidden();
    }
}
