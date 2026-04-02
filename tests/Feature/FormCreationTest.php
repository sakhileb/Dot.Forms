<?php

namespace Tests\Feature;

use App\Livewire\Forms\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_can_be_created_from_forms_index(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user);

        Livewire::test(Index::class, ['team' => $user->currentTeam])
            ->call('createForm');

        $this->assertDatabaseCount('forms', 1);
        $this->assertDatabaseHas('forms', [
            'team_id' => $user->currentTeam->id,
            'user_id' => $user->id,
            'title' => 'Untitled Form',
            'is_published' => false,
        ]);
    }
}
