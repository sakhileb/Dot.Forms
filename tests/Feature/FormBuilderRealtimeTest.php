<?php

namespace Tests\Feature;

use App\Broadcasting\FormBuilderChannelAuthorizer;
use App\Events\FormBuilderUpdated;
use App\Livewire\Forms\Builder;
use App\Models\Form;
use App\Models\FormUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class FormBuilderRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_the_builder_broadcasts_to_the_forms_presence_channel(): void
    {
        Event::fake([FormBuilderUpdated::class]);

        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $form = Form::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'title' => 'Realtime Form',
            'slug' => 'realtime-form',
            'settings' => [],
            'is_published' => false,
        ]);

        $this->actingAs($owner);

        Livewire::test(Builder::class, ['team' => $team, 'form' => $form])
            ->set('title', 'Renamed Form')
            ->call('saveDraft');

        Event::assertDispatched(FormBuilderUpdated::class, function (FormBuilderUpdated $event) use ($form, $owner) {
            return $event->formId === $form->id
                && $event->editedByUserId === $owner->id
                && $event->broadcastOn()[0]->name === 'presence-form-builder.'.$form->id;
        });
    }

    public function test_a_team_member_and_a_role_based_collaborator_can_authorize_the_channel_but_an_outsider_cannot(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;

        $teammate = User::factory()->create();
        $team->users()->attach($teammate, ['role' => 'editor']);

        $externalCollaborator = User::factory()->withPersonalTeam()->create();
        $outsider = User::factory()->withPersonalTeam()->create();

        $form = Form::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'title' => 'Realtime Form',
            'slug' => 'realtime-form-2',
            'settings' => [],
            'is_published' => false,
        ]);

        FormUserRole::create([
            'form_id' => $form->id,
            'user_id' => $externalCollaborator->id,
            'role' => 'viewer',
        ]);

        $this->assertNotFalse(FormBuilderChannelAuthorizer::authorize($owner, $form->id));
        $this->assertNotFalse(FormBuilderChannelAuthorizer::authorize($teammate, $form->id));
        $this->assertNotFalse(FormBuilderChannelAuthorizer::authorize($externalCollaborator, $form->id));
        $this->assertFalse(FormBuilderChannelAuthorizer::authorize($outsider, $form->id));
        $this->assertFalse(FormBuilderChannelAuthorizer::authorize($owner, $form->id + 999));
    }
}
