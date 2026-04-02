<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 style="font-family: 'Sora', 'Inter', sans-serif; font-size: 1.6rem; font-weight: 800; color: #1A1A1A; margin: 0 0 6px;">Welcome back</h1>
        <p style="font-size: 14px; color: #6B7280; margin: 0 0 28px;">Sign in to your Dot Forms workspace.</p>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div style="background: #DCFCE7; border: 1px solid #86EFAC; color: #15803D; border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 500; margin-bottom: 20px;">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 18px;">
            @csrf

            <div>
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif;"
                    onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                    placeholder="you@example.com">
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" style="font-size: 13px; font-weight: 600; color: #374151;">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size: 12px; color: #C9950A; font-weight: 500; text-decoration: none;">Forgot password?</a>
                    @endif
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif;"
                    onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                    placeholder="••••••••">
            </div>

            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" id="remember_me" name="remember"
                    style="width: 16px; height: 16px; accent-color: #F5B800; cursor: pointer;">
                <span style="font-size: 13px; color: #6B7280;">Keep me signed in</span>
            </label>

            <button type="submit"
                style="width: 100%; height: 46px; background: #F5B800; color: #1A1A1A; font-weight: 700; font-size: 15px; border: none; border-radius: 10px; cursor: pointer; transition: background .15s, box-shadow .15s; font-family: 'Inter', sans-serif; box-shadow: 0 4px 12px rgba(245,184,0,.35);"
                onmouseover="this.style.background='#C9950A'" onmouseout="this.style.background='#F5B800'">
                Sign in
            </button>
        </form>

        @if (Route::has('register'))
            <p style="margin-top: 24px; text-align: center; font-size: 13px; color: #6B7280;">
                No account yet?
                <a href="{{ route('register') }}" style="color: #D32F2F; font-weight: 600; text-decoration: none;">Create one free</a>
            </p>
        @endif
    </x-authentication-card>
</x-guest-layout>
