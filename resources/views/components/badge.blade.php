@props([
    'tone' => 'neutral',
])

@php
    $classes = match ($tone) {
        'success' => 'bg-green-50 text-success',
        'warning' => 'bg-amber-50 text-warning',
        'danger' => 'bg-red-50 text-danger',
        default => 'bg-canvas text-muted',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $slot }}
</span>
