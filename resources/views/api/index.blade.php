<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('API Tokens') }}
        </h2>
    </x-slot>

    <div style="max-width: 1200px; margin: 0 auto; padding: 40px 24px;">
        <div style="margin-bottom: 40px;">
            <h1 class="display" style="font-size: 28px; font-weight: 800; margin: 0 0 8px; color: #1A1A1A;">API Tokens</h1>
            <p style="font-size: 14px; color: #6B7280; margin: 0;">Manage your API tokens for programmatic access.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
            <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);">
                @livewire('api.api-token-manager')
            </div>
        </div>
    </div>
</x-app-layout>
