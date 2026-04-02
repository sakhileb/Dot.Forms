<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 style="font-family: 'Sora', 'Inter', sans-serif; font-size: 1.6rem; font-weight: 800; color: #1A1A1A; margin: 0 0 6px;">Create your account</h1>
        <p style="font-size: 14px; color: #6B7280; margin: 0 0 28px;">Free forever on the starter plan. No card needed.</p>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}" style="display: flex; flex-direction: column; gap: 18px;">
            @csrf

            <div>
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                    style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif;"
                    onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                    placeholder="Jane Smith">
            </div>

            <div>
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                    style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif;"
                    onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                    placeholder="you@example.com">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div>
                    <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif;"
                        onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                        placeholder="Min 8 chars">
                </div>
                <div>
                    <label for="password_confirmation" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Confirm</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        style="width: 100%; height: 44px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: 'Inter', sans-serif;"
                        onfocus="this.style.borderColor='#F5B800'" onblur="this.style.borderColor='#E5E7EB'"
                        placeholder="Same again">
                </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="terms" id="terms" required
                        style="width: 16px; height: 16px; margin-top: 2px; accent-color: #F5B800; cursor: pointer; flex-shrink: 0;">
                    <span style="font-size: 13px; color: #6B7280; line-height: 1.5;">
                        {!! __('I agree to the :terms_of_service and :privacy_policy', [
                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" style="color: #C9950A; font-weight: 500; text-decoration: none;">'.__('Terms of Service').'</a>',
                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" style="color: #C9950A; font-weight: 500; text-decoration: none;">'.__('Privacy Policy').'</a>',
                        ]) !!}
                    </span>
                </label>
            @endif

            <button type="submit"
                style="width: 100%; height: 46px; background: #F5B800; color: #1A1A1A; font-weight: 700; font-size: 15px; border: none; border-radius: 10px; cursor: pointer; font-family: 'Inter', sans-serif; box-shadow: 0 4px 12px rgba(245,184,0,.35);"
                onmouseover="this.style.background='#C9950A'" onmouseout="this.style.background='#F5B800'">
                Create account
            </button>
        </form>

        <p style="margin-top: 24px; text-align: center; font-size: 13px; color: #6B7280;">
            Already have an account?
            <a href="{{ route('login') }}" style="color: #D32F2F; font-weight: 600; text-decoration: none;">Sign in</a>
        </p>
    </x-authentication-card>
</x-guest-layout>
