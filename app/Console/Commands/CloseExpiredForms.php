<?php

namespace App\Console\Commands;

use App\Models\Form;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseExpiredForms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forms:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close published forms that have reached their configured close date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $closed = 0;

        $forms = Form::query()
            ->where('is_published', true)
            ->whereNull('archived_at')
            ->get();

        foreach ($forms as $form) {
            $closeAt = $form->settings['close_at'] ?? null;

            if (! is_string($closeAt) || $closeAt === '') {
                continue;
            }

            $closeAtTime = Carbon::parse($closeAt);

            if ($closeAtTime->lessThanOrEqualTo($now)) {
                $form->update([
                    'is_published' => false,
                ]);

                $closed++;
            }
        }

        $this->info('Closed '.$closed.' expired form(s).');

        return self::SUCCESS;
    }
}
