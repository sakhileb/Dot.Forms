<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public Team $team;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    public function mount(Team $team): void
    {
        Gate::authorize('view', $team);

        $this->team = $team;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function createForm(): void
    {
        Gate::authorize('canCreateForm', $this->team);

        $title = 'Untitled Form';

        $form = Form::query()->create([
            'team_id' => $this->team->id,
            'user_id' => Auth::id(),
            'title' => $title,
            'slug' => $this->uniqueSlug($title),
            'settings' => [
                'confirmation_message' => 'Thanks for your submission.',
                'limit_responses' => null,
                'open_at' => null,
                'close_at' => null,
                'theme' => 'light',
                'brand_color' => '#4f46e5',
                'custom_css' => null,
                'retention_days' => null,
                'consent_required' => false,
                'consent_label' => 'I consent to processing my submitted data.',
                'quiz_enabled' => false,
                'quiz_answer_key' => [],
                'conversational_mode' => false,
                'crm_provider' => 'none',
                'crm_webhook_url' => null,
            ],
            'is_published' => false,
        ]);

        $this->redirectRoute('teams.forms.builder', ['team' => $this->team, 'form' => $form]);
    }

    public function duplicateForm(int $formId): void
    {
        $form = $this->team->forms()->with('fields')->findOrFail($formId);

        Gate::authorize('canEditForm', $this->team);

        $copy = $form->replicate(['slug', 'is_published', 'published_at', 'archived_at']);
        $copy->title = $form->title.' Copy';
        $copy->slug = $this->uniqueSlug($copy->title);
        $copy->is_published = false;
        $copy->published_at = null;
        $copy->archived_at = null;
        $copy->save();

        foreach ($form->fields as $field) {
            $copy->fields()->create($field->only([
                'type',
                'label',
                'placeholder',
                'options',
                'validation_rules',
                'order',
            ]));
        }

        session()->flash('status', 'Form duplicated successfully.');
    }

    public function archiveForm(int $formId): void
    {
        $form = $this->team->forms()->findOrFail($formId);

        Gate::authorize('canEditForm', $this->team);

        $form->update([
            'is_published' => false,
            'archived_at' => now(),
        ]);

        session()->flash('status', 'Form archived.');
    }

    public function deleteForm(int $formId): void
    {
        $form = $this->team->forms()->findOrFail($formId);

        Gate::authorize('canEditForm', $this->team);

        $form->delete();

        session()->flash('status', 'Form deleted.');
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'form';
        $slug = $base;
        $counter = 1;

        while (Form::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function render()
    {
        $forms = $this->team
            ->forms()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($inner): void {
                    $inner
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status === 'published', fn ($query) => $query->where('is_published', true)->whereNull('archived_at'))
            ->when($this->status === 'draft', fn ($query) => $query->where('is_published', false)->whereNull('archived_at'))
            ->when($this->status === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->latest()
            ->paginate(10);

        return view('livewire.forms.index', [
            'forms' => $forms,
        ]);
    }
}
