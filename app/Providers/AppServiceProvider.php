<?php

namespace App\Providers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('canCreateForm', fn (User $user, Team $team) => $user->can('canCreateForm', $team));
        Gate::define('canEditForm', fn (User $user, Team $team) => $user->can('canEditForm', $team));
        Gate::define('canViewSubmissions', fn (User $user, Team $team) => $user->can('canViewSubmissions', $team));
    }
}
