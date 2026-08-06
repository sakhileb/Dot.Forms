@props(['for'])

@error($for)
    <p {{ $attributes->merge(['style' => 'margin-top: 6px; font-size: 12px; color: #d2232a;']) }}>
        {{ $message }}
    </p>
@enderror
