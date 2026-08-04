<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormsTeamScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_alone_blocks_cross_team_access_even_without_an_explicit_where(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $outsider = User::factory()->withPersonalTeam()->create();

        $form = Form::create([
            'team_id' => $owner->currentTeam->id,
            'user_id' => $owner->id,
            'title' => 'Owner Form',
            'slug' => 'owner-form',
            'settings' => [],
            'is_published' => false,
        ]);

        $this->actingAs($outsider);

        $this->assertNull(Form::find($form->id));
        $this->assertSame(0, Form::query()->count());

        $this->actingAs($owner);

        $this->assertNotNull(Form::find($form->id));
        $this->assertSame(1, Form::query()->count());
    }
}
