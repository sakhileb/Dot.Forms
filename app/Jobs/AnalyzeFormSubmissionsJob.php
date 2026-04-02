<?php

namespace App\Jobs;

use App\Models\Form;
use App\Services\Ai\AiSubmissionAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeFormSubmissionsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Form $form, public int $limit = 100)
    {
        $this->onQueue((string) config('dotforms.queues.ai', 'ai'));
    }

    public function handle(AiSubmissionAnalyzer $analyzer): array
    {
        $submissions = $this->form->submissions()->latest('submitted_at')->take($this->limit)->get();

        return $analyzer->analyze($this->form->fresh('fields'), $submissions);
    }
}
