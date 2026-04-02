<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">AI Field Suggestions</h2>
            <p class="text-sm text-gray-600">{{ $form->title }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('teams.forms.builder', ['team' => $team, 'form' => $form]) }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Back to Builder</a>
            <button wire:click="enhanceLabels" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Enhance Field Labels</button>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($suggestions as $index => $suggestion)
            <article class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ $suggestion['label'] }}</h3>
                        <p class="text-xs text-gray-500">{{ \Illuminate\Support\Str::headline($suggestion['type']) }}</p>
                        @if (! empty($suggestion['placeholder']))
                            <p class="mt-1 text-sm text-gray-600">Placeholder: {{ $suggestion['placeholder'] }}</p>
                        @endif
                        @if (! empty($suggestion['options']))
                            <p class="mt-1 text-sm text-gray-600">Options: {{ implode(', ', $suggestion['options']) }}</p>
                        @endif
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="selectedSuggestionIds" value="{{ $index }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        Select
                    </label>
                </div>
            </article>
        @empty
            <p class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">No suggestions available.</p>
        @endforelse
    </div>

    <button wire:click="applySuggestions" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply Selected Suggestions</button>
</div>
