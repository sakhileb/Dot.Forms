<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div style="max-width: 1200px; margin: 0 auto; padding: 40px 24px;">
        <div style="margin-bottom: 40px;">
            <h1 class="display" style="font-size: 28px; font-weight: 800; margin: 0 0 8px; color: #1A1A1A;">Account Settings</h1>
            <p style="font-size: 14px; color: #6B7280; margin: 0;">Manage your profile and security preferences.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                    @livewire('profile.update-profile-information-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                    @livewire('profile.update-password-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                    @livewire('profile.two-factor-authentication-form')
                </div>
            @endif

            <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
