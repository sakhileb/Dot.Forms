@props(['for'])

@error($for)
    <p {{ $attributes->merge(['style' => 'margin-top: 6px; font-size: 12px; color: var(--red);']) }}>
        {{ $message }}
    </p>
@enderror
