@props([
    'type' => 'info',
])

@php
    $classes = match ($type) {
        'success' => 'border-green-200 bg-green-50 text-success',
        'error' => 'border-red-200 bg-red-50 text-danger',
        'warning' => 'border-amber-200 bg-amber-50 text-warning',
        default => 'border-line bg-canvas text-ink',
    };
@endphp

<div role="status" {{ $attributes->merge(['class' => 'mb-4 rounded-md border px-3 py-2 text-sm '.$classes]) }}>
    {{ $slot }}
</div>
