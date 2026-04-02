<x-guest-layout>
    <div style="width: 100%; max-width: 700px; background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,.06); margin: 40px auto;">
        <div style="margin-bottom: 32px;">
            <a href="{{ route('welcome') }}" style="display: flex; align-items: center; gap: 10px; text-decoration: none; margin-bottom: 24px;">
                <img src="{{ asset('images/dot_forms.png') }}" alt="Dot Forms" style="height: 32px; width: 32px; object-fit: contain;">
                <span style="font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: #1A1A1A;">dot<span style="color: var(--red);">.</span>forms</span>
            </a>
        </div>
        <div style="font-size: 14px; color: #374151; line-height: 1.8;">
            {!! $policy !!}
        </div>
    </div>
</x-guest-layout>
