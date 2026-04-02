<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Team Settings') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <div class="px-4 sm:px-0 mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6" aria-label="Team navigation">
                        <a href="{{ route('teams.show', $team) }}" class="whitespace-nowrap border-b-2 border-indigo-500 px-1 pb-3 text-sm font-medium text-indigo-600">
                            {{ __('Settings') }}
                        </a>
                        <a href="{{ route('teams.forms', $team) }}" class="whitespace-nowrap border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                            {{ __('Forms') }}
                        </a>
                    </nav>
                </div>
            </div>

            @livewire('teams.update-team-name-form', ['team' => $team])

            @livewire('teams.team-member-manager', ['team' => $team])

            @if (Gate::check('delete', $team) && ! $team->personal_team)
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('teams.delete-team-form', ['team' => $team])
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
