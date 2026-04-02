<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Dot Forms') }} — Build forms that work</title>
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/dot_forms.png') }}">
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@700;800&display=swap" rel="stylesheet">
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            :root {
                --yellow: #F5B800;
                --yellow-dark: #C9950A;
                --yellow-light: #FFF4C2;
                --red: #D32F2F;
                --red-light: #FFEBEB;
                --ink: #1A1A1A;
                --muted: #6B7280;
                --surface: #FFFFFF;
                --bg: #FAFAFA;
            }
            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg);
                color: var(--ink);
                margin: 0;
                min-height: 100vh;
            }
            .hero-bg {
                background: linear-gradient(135deg, #fff9e6 0%, #ffffff 50%, #fff0f0 100%);
                position: relative;
                overflow: hidden;
            }
            .hero-bg::before {
                content: '';
                position: absolute;
                top: -120px; right: -120px;
                width: 500px; height: 500px;
                background: radial-gradient(circle, rgba(245,184,0,0.18) 0%, transparent 70%);
                border-radius: 50%;
                pointer-events: none;
            }
            .hero-bg::after {
                content: '';
                position: absolute;
                bottom: -80px; left: -80px;
                width: 380px; height: 380px;
                background: radial-gradient(circle, rgba(211,47,47,0.10) 0%, transparent 70%);
                border-radius: 50%;
                pointer-events: none;
            }
            .display { font-family: 'Sora', sans-serif; }
            .badge {
                display: inline-flex; align-items: center; gap: 6px;
                background: var(--yellow-light); color: var(--yellow-dark);
                font-size: 12px; font-weight: 600; letter-spacing: .06em;
                text-transform: uppercase; padding: 5px 14px; border-radius: 999px;
                border: 1px solid rgba(245,184,0,.35);
            }
            .badge-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--yellow-dark); }
            .btn-primary {
                display: inline-flex; align-items: center; gap: 8px;
                background: var(--yellow); color: #1A1A1A;
                font-weight: 700; font-size: 15px;
                padding: 13px 28px; border-radius: 12px; border: none;
                text-decoration: none; cursor: pointer;
                transition: background .15s, transform .1s, box-shadow .15s;
                box-shadow: 0 4px 14px rgba(245,184,0,.4);
            }
            .btn-primary:hover { background: var(--yellow-dark); box-shadow: 0 6px 20px rgba(245,184,0,.5); transform: translateY(-1px); }
            .btn-secondary {
                display: inline-flex; align-items: center; gap: 8px;
                background: white; color: var(--ink);
                font-weight: 600; font-size: 15px;
                padding: 13px 28px; border-radius: 12px;
                border: 1.5px solid #E5E7EB; text-decoration: none;
                transition: border-color .15s, background .15s;
            }
            .btn-secondary:hover { border-color: var(--yellow); background: var(--yellow-light); }
            .card {
                background: white; border-radius: 20px;
                border: 1px solid #F0F0F0;
                box-shadow: 0 2px 12px rgba(0,0,0,.06);
                padding: 28px;
            }
            .stat-number { font-family: 'Sora', sans-serif; font-size: 2.4rem; font-weight: 800; }
            .feature-icon {
                width: 52px; height: 52px; border-radius: 14px;
                display: flex; align-items: center; justify-content: center;
                font-size: 22px; margin-bottom: 14px;
            }
            .nav-link {
                font-size: 14px; font-weight: 500; color: #374151;
                text-decoration: none; padding: 8px 16px; border-radius: 8px;
                transition: background .12s, color .12s;
            }
            .nav-link:hover { background: #F3F4F6; color: #111; }
            .pill { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
            @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
            .fade-up { animation: fadeUp .6s ease both; }
            .fade-up-delay { animation: fadeUp .8s ease .15s both; }
            .fade-up-delay2 { animation: fadeUp .8s ease .3s both; }
        </style>
    </head>
    <body>

        {{-- ── NAVBAR ── --}}
        <header style="background: rgba(255,255,255,.92); backdrop-filter: blur(12px); border-bottom: 1px solid #F0F0F0; position: sticky; top: 0; z-index: 50;">
            <div style="max-width: 1200px; margin: 0 auto; padding: 0 24px; height: 64px; display: flex; align-items: center; justify-content: space-between;">
                <a href="{{ url('/') }}" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="{{ asset('images/dot_forms.png') }}" alt="Dot Forms" style="height: 38px; width: 38px; object-fit: contain;">
                    <span style="font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: #1A1A1A; letter-spacing: -.01em;">dot<span style="color: var(--red);">.</span>forms</span>
                </a>

                <nav style="display: flex; align-items: center; gap: 4px;">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="nav-link">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="nav-link">Sign in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-primary" style="padding: 9px 22px; font-size: 14px;">
                                    Get started free
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                            @endif
                        @endauth
                    @endif
                </nav>
            </div>
        </header>

        {{-- ── HERO ── --}}
        <section class="hero-bg" style="padding: 80px 24px 96px;">
            <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center;">
                <div class="fade-up">
                    <div class="badge" style="margin-bottom: 24px;">
                        <span class="badge-dot"></span>
                        AI-powered form builder
                    </div>
                    <h1 class="display" style="font-size: clamp(2rem, 4vw, 3.5rem); line-height: 1.1; margin: 0 0 24px; color: #1A1A1A;">
                        Build smarter forms<br>
                        <span style="color: var(--red);">in minutes,</span>
                        <span style="color: var(--yellow-dark);"> not hours.</span>
                    </h1>
                    <p style="font-size: 18px; line-height: 1.7; color: #4B5563; margin: 0 0 36px; max-width: 480px;">
                        Dot Forms combines a no-code builder with AI assistance, analytics, and team collaboration — so your forms convert, not just collect.
                    </p>
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <a href="{{ Route::has('register') ? route('register') : route('login') }}" class="btn-primary">
                            Start for free
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="btn-secondary">Sign in to workspace</a>
                    </div>
                    <p style="margin-top: 20px; font-size: 13px; color: #9CA3AF;">No credit card required &nbsp;·&nbsp; Free tier available</p>
                </div>

                <div class="fade-up-delay" style="position: relative;">
                    {{-- Mock form card --}}
                    <div style="background: white; border-radius: 24px; box-shadow: 0 24px 60px rgba(0,0,0,.13); padding: 32px; border: 1px solid #F0F0F0;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 24px;">
                            <img src="{{ asset('images/dot_forms.png') }}" alt="" style="height: 32px; width: 32px; object-fit: contain;">
                            <span style="font-weight: 700; font-size: 15px; color: #1A1A1A;">Customer Feedback Form</span>
                            <span class="pill" style="background: #DCFCE7; color: #15803D; margin-left: auto;">Live</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div>
                                <div style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px;">Your name</div>
                                <div style="height: 38px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 0 12px; display: flex; align-items: center;">
                                    <span style="font-size: 13px; color: #9CA3AF;">e.g. Jane Smith</span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px;">How would you rate us?</div>
                                <div style="display: flex; gap: 8px;">
                                    @foreach(['😠','😕','😐','😊','🤩'] as $i => $emoji)
                                        @if($i === 4)
                                            <div style="flex: 1; height: 40px; background: #FFF4C2; border: 1.5px solid #F5B800; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">{{ $emoji }}</div>
                                        @else
                                            <div style="flex: 1; height: 40px; background: #F9FAFB; border: 1.5px solid #E5E7EB; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">{{ $emoji }}</div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px;">Comments</div>
                                <div style="height: 64px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px;"></div>
                            </div>
                            <div style="background: var(--yellow); color: #1A1A1A; font-weight: 700; font-size: 14px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">Submit Response</div>
                        </div>
                    </div>
                    {{-- floating stat pills --}}
                    <div style="position: absolute; top: -16px; right: -16px; background: white; border-radius: 12px; padding: 10px 16px; box-shadow: 0 8px 24px rgba(0,0,0,.12); display: flex; align-items: center; gap: 8px; border: 1px solid #F0F0F0;">
                        <span style="font-size: 20px;">⚡</span>
                        <div>
                            <div style="font-size: 11px; color: #6B7280; font-weight: 600;">AI Gen</div>
                            <div style="font-size: 14px; font-weight: 800; color: #1A1A1A;">2.3s</div>
                        </div>
                    </div>
                    <div style="position: absolute; bottom: -16px; left: -16px; background: white; border-radius: 12px; padding: 10px 16px; box-shadow: 0 8px 24px rgba(0,0,0,.12); display: flex; align-items: center; gap: 8px; border: 1px solid #F0F0F0;">
                        <span style="font-size: 20px;">📬</span>
                        <div>
                            <div style="font-size: 11px; color: #6B7280; font-weight: 600;">Submissions today</div>
                            <div style="font-size: 14px; font-weight: 800; color: #1A1A1A;">247</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── STATS STRIP ── --}}
        <section style="background: var(--ink); padding: 40px 24px;">
            <div style="max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; text-align: center;">
                <div style="padding: 0 24px; border-right: 1px solid rgba(255,255,255,.1);">
                    <div class="display stat-number" style="color: var(--yellow);">10x</div>
                    <p style="margin: 6px 0 0; font-size: 14px; color: #9CA3AF;">Faster than coding forms from scratch</p>
                </div>
                <div style="padding: 0 24px; border-right: 1px solid rgba(255,255,255,.1);">
                    <div class="display stat-number" style="color: var(--yellow);">+34%</div>
                    <p style="margin: 6px 0 0; font-size: 14px; color: #9CA3AF;">Completion rate on conversational flows</p>
                </div>
                <div style="padding: 0 24px;">
                    <div class="display stat-number" style="color: var(--yellow);">24/7</div>
                    <p style="margin: 6px 0 0; font-size: 14px; color: #9CA3AF;">Automated webhook + notification routing</p>
                </div>
            </div>
        </section>

        {{-- ── FEATURES ── --}}
        <section style="padding: 96px 24px; background: var(--bg);">
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: 56px;" class="fade-up">
                    <h2 class="display" style="font-size: clamp(1.6rem, 3vw, 2.5rem); margin: 0 0 16px; color: #1A1A1A;">
                        Everything your team needs
                    </h2>
                    <p style="font-size: 17px; color: #6B7280; max-width: 520px; margin: 0 auto; line-height: 1.7;">All tools in one place — from building to analysis.</p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;" class="fade-up-delay">
                    <div class="card">
                        <div class="feature-icon" style="background: var(--yellow-light);">🤖</div>
                        <h3 style="font-size: 17px; font-weight: 700; margin: 0 0 10px;">AI Form Builder</h3>
                        <p style="font-size: 14px; color: #6B7280; line-height: 1.65; margin: 0;">Describe your form in plain English and let AI generate the full structure, fields, and logic in seconds.</p>
                    </div>
                    <div class="card">
                        <div class="feature-icon" style="background: #FFEBEB;">🎨</div>
                        <h3 style="font-size: 17px; font-weight: 700; margin: 0 0 10px;">Drag-and-Drop Builder</h3>
                        <p style="font-size: 14px; color: #6B7280; line-height: 1.65; margin: 0;">Reorder fields, set conditional logic, configure validation and theming all from a clean visual editor.</p>
                    </div>
                    <div class="card">
                        <div class="feature-icon" style="background: #E0F2FE;">📊</div>
                        <h3 style="font-size: 17px; font-weight: 700; margin: 0 0 10px;">Built-in Analytics</h3>
                        <p style="font-size: 14px; color: #6B7280; line-height: 1.65; margin: 0;">Track views, completions, drop-off rates, and average completion time with interactive dashboards.</p>
                    </div>
                    <div class="card">
                        <div class="feature-icon" style="background: #F0FDF4;">🔗</div>
                        <h3 style="font-size: 17px; font-weight: 700; margin: 0 0 10px;">Webhooks & Integrations</h3>
                        <p style="font-size: 14px; color: #6B7280; line-height: 1.65; margin: 0;">Push submissions to Slack, Zapier, Make, or any CRM via configurable webhooks — no code needed.</p>
                    </div>
                    <div class="card">
                        <div class="feature-icon" style="background: #FFF7ED;">👥</div>
                        <h3 style="font-size: 17px; font-weight: 700; margin: 0 0 10px;">Team Collaboration</h3>
                        <p style="font-size: 14px; color: #6B7280; line-height: 1.65; margin: 0;">Invite teammates, assign roles, and see who's working on which form live with presence indicators.</p>
                    </div>
                    <div class="card">
                        <div class="feature-icon" style="background: var(--yellow-light);">🔒</div>
                        <h3 style="font-size: 17px; font-weight: 700; margin: 0 0 10px;">GDPR & Security</h3>
                        <p style="font-size: 14px; color: #6B7280; line-height: 1.65; margin: 0;">Consent checkboxes, data retention rules, rate-limiting, honeypot protection and CSRF built in by default.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── CTA BAND ── --}}
        <section style="background: linear-gradient(135deg, var(--yellow) 0%, #F97316 100%); padding: 80px 24px;">
            <div style="max-width: 640px; margin: 0 auto; text-align: center;">
                <h2 class="display" style="font-size: clamp(1.8rem, 3.5vw, 2.8rem); color: #1A1A1A; margin: 0 0 18px;">Ready to build your first form?</h2>
                <p style="font-size: 17px; color: rgba(0,0,0,.65); margin: 0 0 32px; line-height: 1.7;">Join teams already using Dot Forms to capture better data, faster.</p>
                <a href="{{ Route::has('register') ? route('register') : route('login') }}" style="display: inline-flex; align-items: center; gap: 8px; background: #1A1A1A; color: white; font-weight: 700; font-size: 16px; padding: 15px 36px; border-radius: 14px; text-decoration: none; transition: background .15s; box-shadow: 0 8px 24px rgba(0,0,0,.2);">
                    Create free account
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </section>

        {{-- ── FOOTER ── --}}
        <footer style="background: #111; color: #6B7280; padding: 32px 24px; text-align: center;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 12px;">
                <img src="{{ asset('images/dot_forms.png') }}" alt="Dot Forms" style="height: 28px; width: 28px; object-fit: contain; opacity: .75;">
                <span style="font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #ccc;">dot<span style="color: var(--red);">.</span>forms</span>
            </div>
            <p style="margin: 0; font-size: 13px;">© {{ date('Y') }} Dot Forms. Built with Laravel & Livewire.</p>
        </footer>

    </body>
</html>
