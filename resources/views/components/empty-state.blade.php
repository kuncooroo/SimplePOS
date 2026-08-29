@props([
    'title' => 'Nothing here yet',
])

<div {{ $attributes->merge(['class' => 'rounded-md border border-dashed border-line bg-surface px-4 py-8 text-center']) }}>
    <p class="text-sm font-medium text-ink">{{ $title }}</p>
    <div class="mt-1 text-sm text-muted">{{ $slot }}</div>
</div>
