@props(['value'])

<label {{ $attributes->merge(['style' => 'display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; font-family: "Inter", sans-serif;']) }}>
    {{ $value ?? $slot }}
</label>
