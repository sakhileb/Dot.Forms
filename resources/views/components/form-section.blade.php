@props(['submit'])

<div style="display: grid; gap: 24px;">
    <x-section-title>
        <x-slot name="title">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
    </x-section-title>

    <div>
        <form wire:submit="{{ $submit }}">
            <div style="padding: 24px; background: white; border-radius: 12px; border: 1px solid #F0F0F0; display: flex; flex-direction: column; gap: 18px;">
                {{ $form }}
            </div>

            @if (isset($actions))
                <div style="margin-top: 12px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 16px; background: #F9FAFB; border-radius: 0 0 12px 12px; border: 1px solid #F0F0F0; border-top: none;">
                    {{ $actions }}
                </div>
            @endif
        </form>
    </div>
</div>
