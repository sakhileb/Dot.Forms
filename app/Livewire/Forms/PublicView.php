<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Notifications\NewFormSubmissionNotification;
use App\Services\FormSubmissionIntegrationDispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.guest')]
class PublicView extends Component
{
    use WithFileUploads;

    public Form $form;

    public array $answers = [];

    public array $uploads = [];

    #[Validate('nullable|string|max:0')]
    public string $website = '';

    public int $startedAt = 0;

    public bool $submitted = false;

    public bool $consentAccepted = false;

    public ?int $submittedQuizScore = null;

    public ?int $submittedQuizMax = null;

    public function mount(string $slug): void
    {
        $preview = request()->boolean('preview');

        $cacheKey = 'form:public:'.$slug.':'.($preview ? 'preview' : 'live');

        $this->form = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($preview, $slug) {
            $query = Form::query()->where('slug', $slug)->whereNull('archived_at');

            if (! $preview) {
                $query->where('is_published', true);
            }

            return $query->with('fields')->firstOrFail();
        });

        if ($preview) {
            abort_unless(Auth::check(), 403);
            Gate::authorize('canEditForm', $this->form->team);
        }

        $this->startedAt = now()->timestamp;

        if (! $preview) {
            $this->form->increment('views_count');
        }

        foreach ($this->form->fields as $field) {
            $this->answers[$field->id] = null;
        }
    }

    public function submit(): void
    {
        $limiterKey = 'form-submit:'.$this->form->id.':'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 10)) {
            $this->addError('website', 'Too many submission attempts. Please try again later.');

            return;
        }

        $this->validate($this->rules());

        if ($this->website !== '') {
            $this->addError('website', 'Spam detected.');

            return;
        }

        $minSeconds = (int) config('dotforms.forms.min_submit_seconds', 2);

        if ((now()->timestamp - $this->startedAt) < $minSeconds) {
            $this->addError('website', 'Please wait a moment before submitting.');

            return;
        }

        if (($this->form->settings['consent_required'] ?? false) && ! $this->consentAccepted) {
            $this->addError('consentAccepted', 'Consent is required to submit this form.');

            return;
        }

        $submissionData = $this->answers;

        foreach ($this->form->fields as $field) {
            if ($field->type === 'file' && isset($this->uploads[$field->id])) {
                $submissionData[$field->id] = $this->uploads[$field->id]->store(
                    'forms/'.$this->form->id,
                    config('dotforms.forms.upload_disk', 'public')
                );
            }
        }

        $submission = FormSubmission::query()->create([
            'form_id' => $this->form->id,
            'user_id' => Auth::id(),
            'data' => $submissionData,
            'submitted_at' => now(),
            'completion_seconds' => max(0, now()->timestamp - $this->startedAt),
            'quiz_score' => $this->quizScore($submissionData)[0],
            'quiz_max_score' => $this->quizScore($submissionData)[1],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        [$this->submittedQuizScore, $this->submittedQuizMax] = $this->quizScore($submissionData);

        RateLimiter::hit($limiterKey, 60);

        $this->form->loadMissing('user', 'fields');

        if ($this->form->user) {
            $this->form->user->notify(new NewFormSubmissionNotification($this->form, $submission));
        }

        app(FormSubmissionIntegrationDispatcher::class)->dispatch($this->form, $submission);

        $this->submitted = true;
        $this->reset('uploads', 'website', 'consentAccepted');
    }

    protected function rules(): array
    {
        $rules = [
            'website' => ['nullable', 'string', 'max:0'],
            'consentAccepted' => ['boolean'],
        ];

        foreach ($this->form->fields as $field) {
            $fieldRules = [];
            $storedRules = is_array($field->validation_rules) ? $field->validation_rules : [];

            if (in_array('required', $storedRules, true)) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($field->type === 'email') {
                $fieldRules[] = 'email';
            }
            if ($field->type === 'number') {
                $fieldRules[] = 'numeric';
            }
            if ($field->type === 'date') {
                $fieldRules[] = 'date';
            }

            if ($field->type === 'file') {
                $rules['uploads.'.$field->id] = array_merge($fieldRules, ['file', 'max:10240']);
            } else {
                $rules['answers.'.$field->id] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    protected function quizScore(array $submissionData): array
    {
        if (! ($this->form->settings['quiz_enabled'] ?? false)) {
            return [null, null];
        }

        $answerKey = $this->form->settings['quiz_answer_key'] ?? [];

        if (! is_array($answerKey) || $answerKey === []) {
            return [0, 0];
        }

        $score = 0;
        $max = count($answerKey);

        foreach ($answerKey as $fieldId => $expected) {
            $actual = $submissionData[(int) $fieldId] ?? null;

            if ((string) $actual === (string) $expected) {
                $score++;
            }
        }

        return [$score, $max];
    }

    #[Layout('components.layouts.guest')]
    public function render()
    {
        return view('livewire.forms.public-view');
    }
}
