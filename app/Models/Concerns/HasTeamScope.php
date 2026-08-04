<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Dot.Forms is Jetstream-Teams based, not single-user (see Dot.Finance's
 * HasUserScope, which this trait is the direct team-scoped analog of, and
 * Dot.Mines' own HasTeamFilters, which this mirrors even more closely).
 * Every model that owns a literal team_id column applies this trait so a
 * query against it is scoped to the authenticated user's current team by
 * default -- the same reasoning as HasUserScope: a forgotten
 * where('team_id', ...) call in a future controller or Livewire component
 * can no longer leak another team's rows, because the model itself never
 * returns unscoped results while a user is authenticated.
 *
 * Only Form actually has a team_id column in this schema. FormField,
 * FormSubmission, FormUserRole, FormVersion and AiSuggestion are keyed by
 * form_id, not team_id, and are read exclusively through the already-
 * authorized $form->relation() (e.g. $form->fields(), $form->submissions())
 * in every controller/Livewire component -- never via a static ::query()
 * read -- so they inherit Form's protection without needing (and without
 * being able to correctly support, absent a join) this trait themselves.
 *
 * currentTeam is used, not the {team} route-bound model, to match the
 * pattern Dot.Mines' own HasTeamFilters uses (auth()->user()->current_team_id).
 * If a user has no current team (or is unauthenticated), the scope is
 * skipped rather than forced to zero rows: skipping matches HasUserScope's
 * "if Auth::check()" guard and avoids turning "no current team selected"
 * into a blanket 404/empty-result footgun for legitimate flows (e.g. public,
 * unauthenticated form submission in PublicView, which must still be able
 * to read the Form it is submitting to).
 */
trait HasTeamScope
{
    protected static function bootHasTeamScope(): void
    {
        static::addGlobalScope('team', function (Builder $builder): void {
            if (! Auth::check()) {
                return;
            }

            $teamId = Auth::user()?->currentTeam?->id;

            if ($teamId !== null) {
                $builder->where($builder->getModel()->getTable().'.team_id', $teamId);
            }
        });
    }

    /**
     * Query without the team global scope, for the handful of legitimately
     * cross-team lookups this schema needs: global slug-uniqueness checks
     * (forms.slug is unique across all teams, not per team) and the public,
     * unauthenticated-respondent form view (which must resolve a form by
     * slug regardless of the viewer's own current team, if any).
     */
    public static function withoutTeamScope(): Builder
    {
        return static::query()->withoutGlobalScope('team');
    }
}
