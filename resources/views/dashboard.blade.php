<x-app-layout>

<div style="padding:2rem 2.5rem 3rem;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;">
        <div>
            <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:#f4f4f5;margin:0 0 0.2rem;letter-spacing:-0.01em;">Form Builder</h1>
            <p style="font-size:0.78rem;color:#52525b;margin:0;">Collect responses, analyse submissions, and build workflows</p>
        </div>
        <a href="{{ Route::has('teams.forms.ai-builder') ? route('teams.forms.ai-builder', auth()->user()->currentTeam) : '#' }}" class="dot-btn dot-btn-primary">
            <span class="material-symbols-rounded" style="font-size:15px;">add</span>
            New Form
        </a>
    </div>

    {{-- KPI Strip --}}
    @php
        $team = auth()->user()->currentTeam;
        $totalForms = $team ? $team->forms()->count() : 0;
        $published  = $team ? $team->forms()->where('is_published', true)->count() : 0;
        $drafts     = $team ? $team->forms()->where('is_published', false)->whereNull('archived_at')->count() : 0;
    @endphp
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
        <div class="dot-card" style="padding:1.25rem 1.5rem;">
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.09em;color:#52525b;margin-bottom:0.75rem;">Total Forms</div>
            <div class="metric-val" style="font-size:2rem;font-weight:600;color:var(--accent);">{{ $totalForms }}</div>
        </div>
        <div class="dot-card" style="padding:1.25rem 1.5rem;">
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.09em;color:#52525b;margin-bottom:0.75rem;">Published</div>
            <div class="metric-val" style="font-size:2rem;font-weight:600;color:#22c55e;">{{ $published }}</div>
        </div>
        <div class="dot-card" style="padding:1.25rem 1.5rem;">
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.09em;color:#52525b;margin-bottom:0.75rem;">Drafts</div>
            <div class="metric-val" style="font-size:2rem;font-weight:600;color:#f59e0b;">{{ $drafts }}</div>
        </div>
    </div>

    {{-- Recent Forms --}}
    <div class="dot-card" style="padding:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h2 style="font-family:'Syne',sans-serif;font-size:0.875rem;font-weight:700;color:#f4f4f5;margin:0;">Recent Forms</h2>
            @if($team)
            <a href="{{ Route::has('teams.forms') ? route('teams.forms', $team) : '#' }}" class="dot-btn dot-btn-ghost" style="padding:5px 12px;font-size:12px;">View all</a>
            @endif
        </div>

        @php
            $forms = $team ? $team->forms()->orderBy('updated_at', 'desc')->limit(6)->get() : collect();
        @endphp

        @if($forms->count() > 0)
        <div style="display:grid;gap:0.5rem;">
            @foreach($forms as $form)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 1rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:8px;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:#d4d4d8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:3px;">{{ $form->title }}</div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:#52525b;">
                        <span>{{ $form->fields()->count() }} fields</span>
                        <span>·</span>
                        @if($form->archived_at)
                            <span style="padding:2px 7px;background:rgba(255,255,255,0.06);color:#71717a;border-radius:100px;font-weight:600;">Archived</span>
                        @elseif($form->is_published)
                            <span style="padding:2px 7px;background:rgba(34,197,94,0.1);color:#22c55e;border-radius:100px;font-weight:600;">Published</span>
                        @else
                            <span style="padding:2px 7px;background:rgba(245,158,11,0.1);color:#f59e0b;border-radius:100px;font-weight:600;">Draft</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;margin-left:1rem;">
                    @if(Route::has('teams.forms.builder'))
                    <a href="{{ route('teams.forms.builder', ['team' => $team, 'form' => $form]) }}" class="dot-btn dot-btn-ghost" style="padding:4px 10px;font-size:11px;">Edit</a>
                    @endif
                    @if(Route::has('teams.forms.submissions'))
                    <a href="{{ route('teams.forms.submissions', ['team' => $team, 'form' => $form]) }}" class="dot-btn dot-btn-ghost" style="padding:4px 10px;font-size:11px;">Responses</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align:center;padding:3rem 0;">
            <span class="material-symbols-rounded" style="font-size:40px;color:#3f3f46;display:block;margin-bottom:1rem;">edit_document</span>
            <h3 style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:#f4f4f5;margin:0 0 0.5rem;">No forms yet</h3>
            <p style="font-size:0.8rem;color:#52525b;margin:0 0 1.5rem;max-width:280px;margin-left:auto;margin-right:auto;">Build your first form with our AI-powered builder. Drag, drop, and publish in minutes.</p>
            <a href="{{ Route::has('teams.forms.ai-builder') ? route('teams.forms.ai-builder', $team) : '#' }}" class="dot-btn dot-btn-primary">
                <span class="material-symbols-rounded" style="font-size:15px;">auto_awesome</span>
                Create with AI
            </a>
        </div>
        @endif
    </div>

</div>

</x-app-layout>
