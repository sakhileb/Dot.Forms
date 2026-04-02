@props(['on'])

<div x-data="{ shown: false, timeout: null }"
    x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
    x-show.transition.out.opacity.duration.1500ms="shown"
    x-transition:leave.opacity.duration.1500ms
    style="display: none;"
    {{ $attributes->merge(['style' => 'font-size: 14px; color: #059669; background: #DCFCE7; border: 1px solid #86EFAC; border-radius: 8px; padding: 12px 16px;']) }}>
    {{ $slot->isEmpty() ? 'Saved.' : $slot }}
</div>
