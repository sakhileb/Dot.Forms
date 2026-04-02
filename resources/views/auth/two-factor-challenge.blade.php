<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div x-data="{ recovery: false }">
            <h1 style="font-family: 'Sora', 'Inter', sans-serif; font-size: 1.6rem; font-weight: 800; color: #1A1A1A; margin: 0 0 6px;" x-show="! recovery">
                Two-factor authentication
            </h1>
            <h1 style="font-family: 'Sora', 'Inter', sans-serif; font-size: 1.6rem; font-weight: 800; color: #1A1A1A; margin: 0 0 6px;" x-cloak x-show="recovery">
                Enter recovery code
            </h1>

            <p style="font-size: 14px; color: #6B7280; margin: 0 0 28px;" x-show="! recovery">
                {{ __('Enter the code from your authenticator app to complete the login.') }}
            </p>
            <p style="font-size: 14px; color: #6B7280; margin: 0 0 28px;" x-cloak x-show="recovery">
                {{ __('Enter one of your emergency recovery codes to continue.') }}
            </p>

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('two-factor.login') }}" style="display: flex; flex-direction: column; gap: 18px;">
                @csrf

                <div x-show="! recovery">
                    <label for="code" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Authentication code</label>
                    <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code"
                        style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif; text-align: center; letter-spacing: .1em;"
                        onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                        placeholder="000000">
                </div>

                <div x-cloak x-show="recovery">
                    <label for="recovery_code" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Recovery code</label>
                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code"
                        style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif;"
                        onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                        placeholder="xxxx-xxxx-xxxx-xxxx">
                </div>

                <button type="submit"
                    style="width: 100%; height: 46px; background: #F5B800; color: #1A1A1A; font-weight: 700; font-size: 15px; border: none; border-radius: 10px; cursor: pointer; font-family: 'Inter', sans-serif; box-shadow: 0 4px 12px rgba(245,184,0,.35);"
                    onmouseover="this.style.background='#C9950A'" onmouseout="this.style.background='#F5B800'">
                    {{ __('Log in') }}
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <button type="button" style="font-size: 13px; color: #D32F2F; font-weight: 600; text-decoration: none; background: none; border: none; cursor: pointer; display: block; width: 100%;" x-show="! recovery" x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                    {{ __('Use a recovery code instead') }}
                </button>

                <button type="button" style="font-size: 13px; color: #D32F2F; font-weight: 600; text-decoration: none; background: none; border: none; cursor: pointer; display: block; width: 100%;" x-cloak x-show="recovery" x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                    {{ __('Use an authentication code') }}
                </button>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>
