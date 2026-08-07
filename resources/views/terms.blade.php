<x-guest-layout>
    <div class="w-full max-w-2xl">
        <div class="flex justify-center mb-8 press">
            <x-authentication-card-logo />
        </div>

        <p class="font-mono text-[11px] tracking-[0.18em] uppercase text-[var(--red)] mb-2">Legal</p>
        <h1 class="font-display font-semibold text-2xl text-[var(--ink)] mb-4">Terms of Service</h1>

        <div class="rounded-2xl border p-6 sm:p-8 prose prose-headings:font-display prose-a:text-[var(--red)]" style="background: var(--paper-deep); border-color: var(--line); box-shadow: 0 1px 3px rgba(34, 27, 18, 0.05);">
            {!! $terms !!}
        </div>
    </div>
</x-guest-layout>
