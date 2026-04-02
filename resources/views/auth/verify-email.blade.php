<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 style="font-family: 'Sora', 'Inter', sans-serif; font-size: 1.6rem; font-weight: 800; color: #1A1A1A; margin: 0 0 6px;">Verify your email</h1>
        <p style="font-size: 14px; color: #6B7280; margin: 0 0 28px;">We've sent a verification link to your email address. Click the link to verify your account.</p>

        @if (session('status') == 'verification-link-sent')
            <div style="background: #DCFCE7; border: 1px solid #86EFAC; color: #15803D; border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 500; margin-bottom: 20px;">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                    style="width: 100%; height: 46px; background: #F5B800; color: #1A1A1A; font-weight: 700; font-size: 15px; border: none; border-radius: 10px; cursor: pointer; font-family: 'Inter', sans-serif; box-shadow: 0 4px 12px rgba(245,184,0,.35);"
                    onmouseover="this.style.background='#C9950A'" onmouseout="this.style.background='#F5B800'">
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <div style="display: flex; gap: 12px; align-items: center; justify-content: center;">
                <a href="{{ route('profile.show') }}" style="font-size: 13px; color: #D32F2F; font-weight: 600; text-decoration: none;">
                    {{ __('Edit Profile') }}
                </a>
                <span style="color: #E5E7EB;">·</span>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" style="font-size: 13px; color: #D32F2F; font-weight: 600; text-decoration: none; background: none; border: none; cursor: pointer; padding: 0;">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>
