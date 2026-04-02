<div style="min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; font-family: 'Inter', sans-serif;">

    {{-- ── LEFT BRAND PANEL ── --}}
    <div style="background: linear-gradient(155deg, #1A1A1A 0%, #2d1a00 60%, #1A1A1A 100%); display: flex; flex-direction: column; justify-content: space-between; padding: 48px; position: relative; overflow: hidden;">

        {{-- background accent circles --}}
        <div style="position: absolute; top: -80px; right: -80px; width: 320px; height: 320px; background: radial-gradient(circle, rgba(245,184,0,.18) 0%, transparent 70%); border-radius: 50%;"></div>
        <div style="position: absolute; bottom: -60px; left: -60px; width: 280px; height: 280px; background: radial-gradient(circle, rgba(211,47,47,.15) 0%, transparent 70%); border-radius: 50%;"></div>

        {{-- logo --}}
        <a href="{{ url('/') }}" style="display: flex; align-items: center; gap: 12px; text-decoration: none; position: relative; z-index: 1;">
            <img src="{{ asset('images/dot_forms.png') }}" alt="Dot Forms" style="height: 44px; width: 44px; object-fit: contain; background: rgba(255,255,255,.08); border-radius: 12px; padding: 6px;">
            <span style="font-family: 'Sora', 'Inter', sans-serif; font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -.01em;">dot<span style="color: #D32F2F;">.</span>forms</span>
        </a>

        {{-- main copy --}}
        <div style="position: relative; z-index: 1;">
            <h2 style="font-family: 'Sora', 'Inter', sans-serif; font-size: 2rem; font-weight: 800; color: #fff; line-height: 1.2; margin: 0 0 16px;">
                Your data.<br>
                <span style="color: #F5B800;">Your forms.</span><br>
                Your rules.
            </h2>
            <p style="font-size: 15px; color: rgba(255,255,255,.6); line-height: 1.7; margin: 0 0 32px; max-width: 340px;">
                Build branded forms, automate follow-ups, and analyse submissions — all without writing a single line of code.
            </p>

            {{-- feature bullets --}}
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px;">
                <li style="display: flex; align-items: center; gap: 12px; font-size: 14px; color: rgba(255,255,255,.75);">
                    <span style="width: 28px; height: 28px; background: rgba(245,184,0,.15); border: 1px solid rgba(245,184,0,.3); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;">🤖</span>
                    AI-generated forms from a sentence
                </li>
                <li style="display: flex; align-items: center; gap: 12px; font-size: 14px; color: rgba(255,255,255,.75);">
                    <span style="width: 28px; height: 28px; background: rgba(245,184,0,.15); border: 1px solid rgba(245,184,0,.3); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;">📊</span>
                    Real-time analytics & submission tracking
                </li>
                <li style="display: flex; align-items: center; gap: 12px; font-size: 14px; color: rgba(255,255,255,.75);">
                    <span style="width: 28px; height: 28px; background: rgba(245,184,0,.15); border: 1px solid rgba(245,184,0,.3); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;">🔗</span>
                    Webhooks, Slack, Zapier & CRM integrations
                </li>
            </ul>
        </div>

        <p style="font-size: 12px; color: rgba(255,255,255,.3); position: relative; z-index: 1;">© {{ date('Y') }} Dot Forms</p>
    </div>

    {{-- ── RIGHT FORM PANEL ── --}}
    <div style="background: #FAFAFA; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 48px 64px;">
        <div style="width: 100%; max-width: 400px;">
            {{-- mobile logo (hidden on desktop via panel) --}}
            <div style="margin-bottom: 32px;">
                {{ $logo }}
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
