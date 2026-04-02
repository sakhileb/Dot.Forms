@props(['for'])\n\n@error($for)\n    <p {{ $attributes->merge(['style' => 'margin-top: 6px; font-size: 12px; color: var(--red);']) }}>\n        {{ $message }}\n    </p>\n@enderror
