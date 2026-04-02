<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">AI Submission Analytics</h2>
            <p class="text-sm text-gray-600">{{ $form->title }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('teams.forms.submissions', ['team' => $team, 'form' => $form]) }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Back to Submissions</a>
            <button wire:click="summarize" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Summarize 100 Submissions</button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Positive</p>
            <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $analysis['sentiment']['positive'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Neutral</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $analysis['sentiment']['neutral'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Negative</p>
            <p class="mt-1 text-2xl font-bold text-red-900">{{ $analysis['sentiment']['negative'] ?? 0 }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Summary</h3>
        <p class="mt-2 text-sm text-gray-700">{{ $analysis['summary'] ?: 'Click "Summarize 100 Submissions" to generate insights.' }}</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Recommendations</h3>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700">
            @forelse (($analysis['recommendations'] ?? []) as $recommendation)
                <li>{{ $recommendation }}</li>
            @empty
                <li>No recommendations yet.</li>
            @endforelse
        </ul>
    </div>
</div>
