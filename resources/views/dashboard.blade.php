<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="display" style="font-size: 28px; font-weight: 800; margin: 0; color: #1A1A1A;">Welcome back, {{ Auth::user()->name }}!</h1>
            <p style="font-size: 14px; color: #6B7280; margin: 6px 0 0;">Manage your team's forms, submissions, and analytics all in one place.</p>
        </div>
    </x-slot>

    <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
        <!-- Quick Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06);">
                <div style="font-size: 12px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px;">Total Forms</div>
                <div class="display" style="font-size: 32px; font-weight: 800; color: var(--yellow-dark); margin: 0;">{{ auth()->user()->currentTeam->forms()->count() ?? 0 }}</div>
            </div>
            <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06);">
                <div style="font-size: 12px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px;">Published</div>
                <div class="display" style="font-size: 32px; font-weight: 800; color: var(--yellow-dark); margin: 0;">{{ auth()->user()->currentTeam->forms()->where('is_published', true)->count() ?? 0 }}</div>
            </div>
            <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06);">
                <div style="font-size: 12px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px;">Drafts</div>
                <div class="display" style="font-size: 32px; font-weight: 800; color: var(--yellow-dark); margin: 0;">{{ auth()->user()->currentTeam->forms()->where('is_published', false)->whereNull('archived_at')->count() ?? 0 }}</div>
            </div>
        </div>

        <!-- Recent Forms Section -->
        <div style="background: white; border-radius: 16px; border: 1px solid #F0F0F0; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,.06);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <div>
                    <h2 class="display" style="font-size: 20px; font-weight: 800; margin: 0 0 6px;">Your Forms</h2>
                    <p style="font-size: 13px; color: #6B7280; margin: 0;">Manage all your forms in one place</p>
                </div>
                <a href="{{ Route::has('teams.forms') ? route('teams.forms', auth()->user()->currentTeam) : '#' }}" style="display: inline-flex; align-items: center; gap: 8px; background: var(--yellow); color: #1A1A1A; font-weight: 700; font-size: 14px; padding: 11px 22px; border-radius: 10px; text-decoration: none; transition: background .15s, transform .1s; box-shadow: 0 4px 12px rgba(245,184,0,.3);" onmouseover="this.style.background='var(--yellow-dark)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='var(--yellow)'; this.style.transform='translateY(0)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Create New Form
                </a>
            </div>

            @php
                $forms = auth()->user()->currentTeam->forms()->orderBy('updated_at', 'desc')->limit(5)->get();
            @endphp

            @if ($forms->count() > 0)
                <div style="display: grid; gap: 14px;">
                    @foreach ($forms as $form)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: #FAFAFA; border-radius: 12px; border: 1px solid #F0F0F0; transition: background .12s, border-color .12s;" onmouseover="this.style.background='#F3F4F6'; this.style.borderColor='#E5E7EB'" onmouseout="this.style.background='#FAFAFA'; this.style.borderColor='#F0F0F0'">
                            <div style="flex: 1; min-width: 0;">
                                <h3 style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin: 0 0 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $form->title }}</h3>
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #6B7280;">
                                    <span>{{ $form->fields()->count() }} fields</span>
                                    <span>·</span>
                                    @if ($form->archived_at)
                                        <span style="display: inline-block; padding: 3px 8px; background: #F3F4F6; color: #6B7280; border-radius: 6px; font-weight: 500;">Archived</span>
                                    @elseif ($form->is_published)
                                        <span style="display: inline-block; padding: 3px 8px; background: #DCFCE7; color: #15803D; border-radius: 6px; font-weight: 500;">Published</span>
                                    @else
                                        <span style="display: inline-block; padding: 3px 8px; background: var(--yellow-light); color: var(--yellow-dark); border-radius: 6px; font-weight: 500;">Draft</span>
                                    @endif
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-left: 16px;">
                                <a href="{{ route('teams.forms.builder', ['team' => auth()->user()->currentTeam, 'form' => $form]) }}" style="padding: 6px 12px; font-size: 12px; font-weight: 500; color: #374151; background: white; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; transition: border-color .12s; cursor: pointer;" onmouseover="this.style.borderColor='var(--yellow)'; this.style.backgroundColor='var(--yellow-light)'" onmouseout="this.style.borderColor='#E5E7EB'; this.style.backgroundColor='white'">Edit</a>
                                <a href="{{ route('teams.forms.submissions', ['team' => auth()->user()->currentTeam, 'form' => $form]) }}" style="padding: 6px 12px; font-size: 12px; font-weight: 500; color: #374151; background: white; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; transition: border-color .12s; cursor: pointer;" onmouseover="this.style.borderColor='var(--yellow)'; this.style.backgroundColor='var(--yellow-light)'" onmouseout="this.style.borderColor='#E5E7EB'; this.style.backgroundColor='white'">Submissions</a>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (auth()->user()->currentTeam->forms()->count() > 5)
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="{{ route('teams.forms', auth()->user()->currentTeam) }}" style="font-size: 13px; font-weight: 600; color: var(--red); text-decoration: none;">View all forms →</a>
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 48px 24px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📝</div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #1A1A1A; margin: 0 0 8px;">No forms yet</h3>
                    <p style="font-size: 14px; color: #6B7280; margin: 0 0 24px; max-width: 320px; margin-left: auto; margin-right: auto;">Create your first form to start collecting responses and analyzing data.</p>
                    <a href="{{ Route::has('teams.forms.ai-builder') ? route('teams.forms.ai-builder', auth()->user()->currentTeam) : '#' }}" style="display: inline-flex; align-items: center; gap: 8px; background: var(--yellow); color: #1A1A1A; font-weight: 700; font-size: 14px; padding: 11px 22px; border-radius: 10px; text-decoration: none; transition: background .15s, transform .1s; box-shadow: 0 4px 12px rgba(245,184,0,.3);" onmouseover="this.style.background='var(--yellow-dark)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='var(--yellow)'; this.style.transform='translateY(0)'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                        Create with AI
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
