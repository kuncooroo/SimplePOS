@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'secondary' => 'border border-line bg-surface text-ink hover:bg-canvas',
        'danger' => 'bg-danger text-white hover:bg-red-800',
        default => 'bg-brand text-white hover:bg-brand-hover',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-md px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50 '.$classes]) }}
>
    {{ $slot }}
</button>
