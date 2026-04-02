<?php

namespace App\Jobs;

use App\Services\Ai\AiFormGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAiFormBlueprintJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $prompt)
    {
        $this->onQueue((string) config('dotforms.queues.ai', 'ai'));
    }

    public function handle(AiFormGenerator $generator): array
    {
        return $generator->generate($this->prompt);
    }
}
