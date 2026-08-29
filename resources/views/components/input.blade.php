@props([
    'label' => null,
    'type' => 'text',
    'name' => null,
    'id' => null,
])

@php
    $id = $id ?? $name ?? 'input-'.uniqid();
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex flex-col gap-1']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-medium text-ink">{{ $label }}</label>
    @endif
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted']) }}
    >
</div>
