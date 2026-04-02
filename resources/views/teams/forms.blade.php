<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Team Forms') }}
        </h2>
    </x-slot>

    <div style="max-width: 1200px; margin: 0 auto; padding: 40px 24px;">
        <!-- Navigation Tabs -->
        <div style="border-bottom: 1px solid #F0F0F0; display: flex; gap: 24px; margin-bottom: 40px;">
            <a href="{{ route('teams.show', $team) }}" style="padding: 16px 0 12px; font-size: 14px; font-weight: 500; color: #6B7280; border-bottom: 3px solid transparent; text-decoration: none; transition: color .15s, border-color .15s;" onmouseover="this.style.color='#1A1A1A'" onmouseout="this.style.color='#6B7280'">Settings</a>
            <a href="{{ route('teams.forms', $team) }}" style="padding: 16px 0 12px; font-size: 14px; font-weight: 600; color: var(--yellow-dark); border-bottom: 3px solid var(--yellow); text-decoration: none;">Forms</a>
        </div>

        <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
            @livewire('forms.index', ['team' => $team])
        </div>
    </div>
</x-app-layout>
