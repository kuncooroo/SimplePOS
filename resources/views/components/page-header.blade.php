@props([
    'title' => null,
])

<div class="mb-4">
    <h1 class="text-lg font-semibold text-ink">{{ $title ?? $slot }}</h1>
    @isset($description)
        <p class="mt-1 text-sm text-muted">{{ $description }}</p>
    @endisset
</div>
