<?php

namespace App\Broadcasting;

use App\Models\Form;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Authorization for the 'form-builder.{formId}' presence channel, factored
 * out of routes/channels.php so it can be unit tested directly -- the
 * "null" and "log" broadcast drivers (what tests and local dev use by
 * default) never actually invoke a channel's callback via the
 * /broadcasting/auth HTTP endpoint, so that endpoint can't be used to
 * verify this logic.
 *
 * Mirrors Builder::mount()'s own access check (team access, or a per-form
 * editor/viewer/owner FormUserRole) rather than just team membership -- a
 * form can be shared with someone outside its owning team.
 */
class FormBuilderChannelAuthorizer
{
    /**
     * @return array{id: int, name: string}|false
     */
    public static function authorize(User $user, int $formId): array|false
    {
        $form = Form::withoutTeamScope()->with('team')->find($formId);

        if (! $form) {
            return false;
        }

        $canAccess = Gate::forUser($user)->allows('view', $form->team)
            || $form->editableBy($user)
            || $form->viewableSubmissionsBy($user);

        if (! $canAccess) {
            return false;
        }

        return ['id' => $user->id, 'name' => $user->name];
    }
}
