<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Team Settings') }}
        </h2>
    </x-slot>

    <div style="max-width: 1200px; margin: 0 auto; padding: 40px 24px;">
        <div style="margin-bottom: 40px;">
            <h1 class="display" style="font-size: 28px; font-weight: 800; margin: 0 0 8px; color: #1A1A1A;">{{ $team->name }}</h1>
            <p style="font-size: 14px; color: #6B7280; margin: 0;">Manage team settings and members.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
            <!-- Navigation Tabs -->
            <div style="border-bottom: 1px solid #F0F0F0; display: flex; gap: 24px;">
                <a href="{{ route('teams.show', $team) }}" style="padding: 16px 0 12px; font-size: 14px; font-weight: 600; color: var(--yellow-dark); border-bottom: 3px solid var(--yellow); text-decoration: none;">Settings</a>
                <a href="{{ route('teams.forms', $team) }}" style="padding: 16px 0 12px; font-size: 14px; font-weight: 500; color: #6B7280; border-bottom: 3px solid transparent; text-decoration: none; transition: color .15s, border-color .15s;" onmouseover="this.style.color='#1A1A1A'" onmouseout="this.style.color='#6B7280'">Forms</a>
            </div>

            <!-- Team Settings Card -->
            <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                @livewire('teams.update-team-name-form', ['team' => $team])
            </div>

            <!-- Team Members Card -->
            <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                @livewire('teams.team-member-manager', ['team' => $team])
            </div>

            @if (Gate::check('delete', $team) && ! $team->personal_team)
                <!-- Delete Team Card -->
                <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                    @livewire('teams.delete-team-form', ['team' => $team])
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
