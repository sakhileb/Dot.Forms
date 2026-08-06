<x-guest-layout>
    <div style="width: 100%; max-width: 700px; background: var(--paper-deep); border-radius: 16px; border: 1px solid var(--line); padding: 40px; box-shadow: 0 1px 3px rgba(34, 27, 18, 0.05); margin: 0 auto;">
        <div style="margin-bottom: 32px;">
            <a href="{{ route('welcome') }}" style="display: flex; align-items: center; gap: 10px; text-decoration: none; margin-bottom: 24px;">
                <img src="{{ asset('images/dot_forms.png') }}" alt="Dot Forms" class="h-12 w-auto">
            </a>
        </div>
        <div style="font-size: 14px; color: #221b12; line-height: 1.8;">
            {!! $policy !!}
        </div>
    </div>
</x-guest-layout>
