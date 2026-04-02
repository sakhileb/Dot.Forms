<button {{ $attributes->merge(['type' => 'submit', 'style' => 'padding: 12px 18px; background: var(--yellow); color: #1A1A1A; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background .15s, box-shadow .15s; font-family: "Inter", sans-serif;', 'onmouseover' => "this.style.background='#C9950A'", 'onmouseout' => "this.style.background='var(--yellow)'"]) }}>
    {{ $slot }}
</button>
