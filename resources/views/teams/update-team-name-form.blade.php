<x-form-section submit="updateTeamName">
    <x-slot name="title">
        {{ __('Team Name') }}
    </x-slot>

    <x-slot name="description">
        {{ __('The team\'s name and owner information.') }}
    </x-slot>

    <x-slot name="form">
        <!-- Team Owner Information -->
        <div>
            <x-label value="{{ __('Team Owner') }}" />

            <div style="display: flex; align-items: center; margin-top: 12px;">
                <img style="height: 48px; width: 48px; border-radius: 50%; object-fit: cover;" src="{{ $team->owner->profile_photo_url }}" alt="{{ $team->owner->name }}">

                <div style="margin-left: 16px; line-height: 1.5;">
                    <div style="color: #1A1A1A; font-weight: 500;">{{ $team->owner->name }}</div>
                    <div style="color: #6B7280; font-size: 12px;">{{ $team->owner->email }}</div>
                </div>
            </div>
        </div>

        <!-- Team Name -->
        <div>
            <x-label for="name" value="{{ __('Team Name') }}" />

            <x-input id="name"
                        type="text"
                        wire:model="state.name"
                        :disabled="! Gate::check('update', $team)" />

            <x-input-error for="name" />
        </div>
    </x-slot>

    @if (Gate::check('update', $team))
        <x-slot name="actions">
            <x-action-message on="saved">
                {{ __('Saved.') }}
            </x-action-message>

            <x-button>
                {{ __('Save') }}
            </x-button>
        </x-slot>
    @endif
</x-form-section>
