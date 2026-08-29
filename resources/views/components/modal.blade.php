@props([
    'open' => false,
    'title' => '',
])

@if ($open)
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="w-full max-w-md rounded-md border border-line bg-surface p-4 shadow-lg">
            @if ($title !== '')
                <h2 id="modal-title" class="mb-3 text-base font-semibold text-ink">{{ $title }}</h2>
            @endif
            {{ $slot }}
        </div>
    </div>
@endif
